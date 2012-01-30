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
define(CACHE_DIR,dirname(__FILE__).'/cache');

class WPCacheDB extends wpdb {
	
	public $cache_key;
	public $cache_log_file = 'log.txt';
	public $cache_dir = CACHE_DIR;
	public $cache_file;
	public $cache_file_name;
	public $cache_time = 26000;
	public $result;
	public $caching;
	
	function __construct($dbuser,$dbpassword,$dbname,$dbhost) {
		parent::__construct($dbuser,$dbpassword,$dbname,$dbhost);
		$this->set_prefix('wp_');
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
		if($time != '') {
			$this->cache_time = $time; 
		} 
		
		if(isset($flag) AND !is_dir($this->cache_dir.'/'.$flag)) {
			mkdir($this->cache_dir.'/'.$flag,0755);

		}
		
		$this->cache_log_file = $this->cache_dir."/log.txt";
		if(!is_dir($this->cache_dir)) { mkdir($this->cache_dir, 0755); }
		
		if(file_exists($this->cache_log_file)) {
			$this->cache_log = unserialize(file_get_contents($this->cache_log_file));
		} else {
			$this->cache_log = array();
		}
		
		if(!$this->cache_log[$query]) {
			$this->cache_log[$query] = md5($query);
		}
		
		$this->cache_file = $this->cache_log[$query];
		if(isset($flag)) {
			$this->cache_file_name = $this->cache_dir.'/'.$flag.'/'.$this->cache_file.'.txt';
		} else {
			$this->cache_file_name = $this->cache_dir.'/default/'.$this->cache_file.'.txt';
		}
		

		
		if( file_exists($this->cache_file_name) AND ( time() - filemtime($this->cache_file_name) < $this->cache_time) ) {
					$result = unserialize(file_get_contents($this->cache_file_name));
					return $result;
					exit();	
		} else {
				if('get_var'==$method)
				{
					$result = call_user_func_array(array($this,$method),array($query));	
				} else {
					$result = call_user_func_array(array($this,$method),array($query,$output));
				}
				if(!empty($result)) {
					file_put_contents($this->cache_file_name,serialize($result)); 
					return $result;
				}	
		}
		
		file_put_contents($this->cache_log_file,serialize($this->cache_log));
	}
	
	/**
	 * Clears the cache
	 *
	 * @since 0.1
	 * @param string $flag Flag to clear
	 */
	
	public static function clear($flag = '') {
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
	}
	
	
	
}
?>