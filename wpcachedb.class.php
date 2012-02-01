<?php
/**
 * Simple WordPress Database Caching for Developers
 *
 * @package WordPress
 * @since 3.0
 *
 * ... instructions comming soon.
 *
 */

define("CACHE_DIR", ABSPATH.'/wp-content/themes/twentyten/cache');
define("CACHE_DRIVER", 'APC');
class WPCacheDB extends wpdb {
	
	public $cache_key;
	public $cache_log_file = 'log.txt';
	public $cache_dir = CACHE_DIR;
	public $cache_file;
	public $cache_file_name;
	public $cache_time = 26000;
	public $result;
	public $caching;
	public $flag;
	
	function __construct($dbuser,$dbpassword,$dbname,$dbhost) {
		parent::__construct($dbuser,$dbpassword,$dbname,$dbhost);
		$this->set_prefix('wp_');
		if(!is_dir(CACHE_DIR)) {
			mkdir(CACHE_DIR,0755,true);
		}
		
		//setup default directory
		if(!is_dir($this->cache_dir.'/default/')) {
			mkdir($this->cache_dir.'/default/',755,true);
		}
		
		$this->cache_log_file = $this->cache_dir."/log.txt";
		if(!is_dir($this->cache_dir)) { mkdir($this->cache_dir, 0755,true); }
		
		if(file_exists($this->cache_log_file)) {
			$this->cache_log = unserialize(file_get_contents($this->cache_log_file));
		} else {
			$this->cache_log = array();
		}
		
		add_action('wp_dashboard_setup', array($this,'control_widget') );
	}
	
	
	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.1
	 *	
	 * @param string $method WPDB method to use
	 * @param string $query Database query
	 * @param string $output WPDB output option ( OBJECT, ARRAY_A, ARRAY_N, etc... )
	 * @param string $flag Used for creating cache groups
	 * @param int number of seconds to store the cache. Will override class default 
	 * @return object/array of data rerieve via the wpdb method.
	 */
	
	function cache($method, $query,$output,$flag ='',$time = '') {
		if($time != '') 
			$this->cache_time = $time; 
		
		if($flag != '')
			$this->flag = $flag;
		else
			$this->flag = 'default';
		
		if(isset($this->flag) AND !is_dir($this->cache_dir.'/'.$flag)) {
			mkdir($this->cache_dir.'/'.$flag,0755,true);
		}
		
		if(!isset($this->cache_log[$query])) {
			$this->cache_log[$query] = md5($query);
		}
		
		$this->cache_file = $this->cache_log[$query];
		if(isset($this->flag)) {
			$this->cache_file_name = $this->cache_dir.'/'.$this->flag.'/'.$this->cache_file.'.txt';
		} else {
			$this->cache_file_name = $this->cache_dir.'/default/'.$this->cache_file.'.txt';
		}	
		
		if( $this->cache_exists() ) {
					$this->result = $this->get();
					return $this->result;
					exit();	
		} else {
				if('get_var'==$method)
				{
					$result = call_user_func_array(array($this,$method),array($query));	
				} else {
					$result = call_user_func_array(array($this,$method),array($query,$output));
				}
				
				if(!empty($result)) {
					$this->set($result);
					return $result;
				}	
		}
		
		file_put_contents($this->cache_log_file,serialize($this->cache_log));
	}
	
	function cache_exists() {
		switch (CACHE_DRIVER) {
			case 'DEFAULT':
				if( file_exists($this->cache_file_name) AND ( time() - filemtime($this->cache_file_name) < $this->cache_time))
				{
					$return = 1;
				} else {
					return false;
				}
			break;
			
			case 'APC':
				$return = apc_exists($this->cache_file);
			break;
		}
		return $return;
	}
	
	function set($data) {
		switch (CACHE_DRIVER) {
			case 'DEFAULT':
				return file_put_contents($this->cache_file_name,serialize($data)); 
			break;
			
			case 'APC':
				return apc_add($this->cache_file,$data,$this->cache_time);
			break;
		}

	}
	
	function get() {
		switch (CACHE_DRIVER) {
			case 'DEFAULT':
				return unserialize(file_get_contents($this->cache_file_name));
			break;
			
			case 'APC':
				return apc_fetch($this->cache_file);
			break;
		}

	}
	
	/**
	 * Clears the cache
	 *
	 * @since 0.1
	 * @param string $flag Flag to clear
	 */
	
	public static function clear($flag = '') {
		if(CACHE_DRIVER == 'DEFAULT')
		{
			if($flag != '') {
				$flag = trim($flag,'/').'/';
			} 
			
			$dir = CACHE_DIR.'/'.$flag;
			if(is_dir($dir)) {
			$mydir = opendir(CACHE_DIR.'/'.$flag);
		    while(false !== ($file = readdir($mydir))) {
		        if($file != "." && $file != "..") {
		            chmod($dir.$file, 0777);
		           	unlink($dir.$file) or DIE("couldn't delete $dir$file<br />");
		        }
		    }
		    closedir($mydir);
		  }
		} else {
			apc_clear_cache('user');
		}
	}
	
	public static function get_flags() {
			//using the opendir function
		$path = CACHE_DIR;
		$dir_handle = @opendir($path) or die("Unable to open $path");
		
		$flags = array();
		while (false !== ($file = readdir($dir_handle))) 
		{
		  if($file!="." && $file!="..")
		  {
		      if (is_dir($path."/".$file))
		      {
		          $flags[] = $file;		         
		      }
		  }
		}

		//closing the directory
		closedir($dir_handle);
		return $flags;
	}	
	
	static public function control_widget() {
		wp_add_dashboard_widget('example_dashboard_widget', 'Example Dashboard Widget','wpcachedb_widget');
	}
	
	static public function widget() {
		echo 'test';
	}

	
}

function wpcachedb_widget() {
	if(isset($_GET['flag']) AND wp_verify_nonce($_GET['_wpnonce'])) 
	{
		WPCacheDB::clear($_GET['flag']);
		echo '<div id="message" class="info" style="background-color:#ffffe0;border-color:#e6db55;">Cache cleared</div>';
	}
	?>
	<style>
		#cache-flag {
			margin: 10px;
			border-bottom: 1px dashed #CCC;
			padding: 10px 0;
		}
		
		#cache-flag label {
			width: 85%;
			display: block;
			float: left;
		}
	</style>
	<?php foreach(WPCacheDB::get_flags() as $flag) { ?>
		<form id="cache_clear" action="" method="get">
		<div id="cache-flag"><label><strong><?php echo ucwords($flag); ?></strong></label>
		<input type="hidden" name="flag" value="<?php echo $flag; ?>" />
		<input type="hidden" name="action" value="clear" />
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce(-1); ?>" />
		<input type="submit" class="button-secondary" value="Clear" />
		</div>
		</form>
		<?php
	}
}
?>