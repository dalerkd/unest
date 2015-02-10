<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

class ValidMemAddr{
	private static $_valid_mem_addr;
	private static $_index;

	public static function init ($mem,$idx){
		self::$_valid_mem_addr = $mem;
		self::$_index          = $idx;// + 1;
	}
	// 是否有写入权限
	public static function is_writable($id){
		return (self::$_valid_mem_addr[$id][OPT] > 1)?true:false;
	}
	// 设置
	public static function set($id,$value){
		self::$_valid_mem_addr[$id] = $value;
	}	
	// 读取
	public static function get($id,$subkey = false){
		if (false === $subkey){
			return isset(self::$_valid_mem_addr[$id])?self::$_valid_mem_addr[$id]:null;
		}else{
			return isset(self::$_valid_mem_addr[$id][$subkey])?self::$_valid_mem_addr[$id][$subkey]:null;
		}
	}
	// 新建
	public static function append($value){
		self::$_valid_mem_addr[self::$_index] = $value;
		$i = self::$_index;
		self::$_index ++;
		return $i;
	}



}
?>