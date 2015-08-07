<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

// 捕获退出(输出log日志)
function shutdown_except(){
    global $complete_finished;

	global $exetime_record;
    if ((!$complete_finished)&&(!GeneralFunc::LogHasErr())){
		GeneralFunc::LogInsert('unexpected shutdown, maximum execution time exceeded or other errors');
	}
	$output = GeneralFunc::LogRead();
    //输出$output[] 到日志文件,jason格式
	file_put_contents(CfgParser::params('log'),json_encode($output));  

	var_dump ($output);
	var_dump ($exetime_record);
	echo "<br>memory_get_usage: ";
    var_dump (memory_get_usage());
    // OrgansOperator::show();
}
// ***
// 通用 函数 集
class GeneralFunc{
    // 日志记录 操作函数s
	private static $_error   = array();
	private static $_warning = array();
	private static $_notice  = array();
	// 写记录日志 $type 1: error  2:warning  3:notice
	public static function LogInsert($log,$type=1){
		if (1 === $type){
		    self::$_error[] = $log;   
		}elseif (2 === $type){
		    self::$_warning[] = $log;   
		}else{
		    self::$_notice[]  = $log;   
		}	    
	}
	public static function LogRead(){
	    $ret['error']   = self::$_error;
	    $ret['warning'] = self::$_warning;
	    $ret['notice']  = self::$_notice;
		return $ret;
	}
	public static function LogHasErr(){
	    if (empty(self::$_error)){
		    return false;
		}
		return true;
	}
	// replace php array_rand (for rand() -> mt_rand())
	public static function my_array_rand($arr){
		if ((is_array($arr)) and (!empty($arr))){
			$keys = array_keys($arr);
			$i = mt_rand(0,count($keys) - 1);
			return $keys[$i];
		}
		return false;
	}
    // 
    public static function check_value($value,$func = false){
		if (false === $func){
			return ((isset($value)) and ($value));
		}else{
			return ((isset($value)) and ($func($value)));
		}
	}
	// 统计运行时间
	private static $_stime = 0;
	public static function exetime_record(){
		/*       记录函数运行时间              */
		$etime=microtime(true);//获取程序执行结束的时间  
		$total=$etime - self::$_stime;   //计算差值  
		$str_total = var_export($total, TRUE);  
		if(substr_count($str_total,"E")){  
			$float_total = floatval(substr($str_total,5));  
			$total = $float_total/100000;  				
		}
		self::$_stime = microtime(true); //获取程序开始执行的时间
		return $total;
		/**************************************/
	}	
	// 获取文件行数(失败返回false,成功返回行数)
	// TODO：超长汇编指令(换行) 未考虑
	public static function get_file_line($filename){
		$line = 0;
		@$fp = fopen($filename , 'r');  
		if($fp){  
			//获取文件的一行内容，注意：需要php5才支持该函数；  
			while(stream_get_line($fp,8192,"\n")){  
				$line++;  
			}
			fclose($fp);//关闭文件  
			return $line;
		}
		return false;
	}
	// 内部错误 日志 保存(保存到文件 or 发送到邮件)
	public static function internal_log_save($title,$contents=false){

		$log_path = dirname(__FILE__)."/../../log/ENGIN_VER/";

		if (!is_dir($log_path)){
			if (!mkdir($log_path)){
				error_log("fail to mkdir: $log_path",1,"1094566308@qq.com","From: internal_fail@unest.org");
				return false;
			}
		}

		$log_file = $log_path."log.txt";
		
		if(!flock($fp=fopen($log_file,'a+'), LOCK_NB | LOCK_EX)){//无法取得锁就退出
			return false;	
		}
		
		$header  = "\r\n\r\n";
		$header .= date("Y-m-d H:i:s",time());
		$header .= "\r\n";
		$header .= "[".$log_path."]";
		$header .= "\r\nTitle:".$title; 	
		if (false !== $contents){
			$header .= "\r\n";
			if (is_array($contents)){
				$header .= "===array start===\r\n";
				$header .= serialize($contents);
				$header .= "\r\n===array end===";
			}else{
				$header .= $contents;
			}
		}
		$header .= "\r\n --------- end ---------\r\n";


		fseek($fp,23);
		fwrite ($fp,$header,strlen($header));
		
		flock($fp,LOCK_UN);
		fclose($fp);
		
		return true;
	}

	// 多维数组去掉value === empty 的单位(s)
	public static function multi_array_filter($array){
		$ret = array();
		foreach ($array as $key => $value){
			if (is_array($value)){
				$tmp = self::multi_array_filter($value);
				if (!empty($tmp)){
					$ret[$key] = $tmp;
				}
			}else{
				if (!empty($value)){
					$ret[$key] = $value;
				}
			}
		}
		return $ret;
	}
	
	// 确定 POST or Get 传递进来的动态插入数据
	public static function get_dynamic_insert_value (&$dynamic_insert){

		global $language;


		$new_dynamic_insert = CfgParser::params('di');
		if (isset($new_dynamic_insert)){
			if (is_array($new_dynamic_insert)){
				foreach ($new_dynamic_insert as $key => $value){
					if (isset($dynamic_insert[$key])){					
						$tmp = GenerateFunc::get_bit_from_inter($value);
						if ($tmp){
							if ($tmp <= $dynamic_insert[$key][BITS]){
								$dynamic_insert[$key]['new'] = $value;
							}else{
								GeneralFunc::LogInsert($language['toobig_dynamci_insert_value'].'di['.$key.'] : '.$tmp.' > '.$dynamic_insert[$key][BITS]);	
							}
						}else{
							GeneralFunc::LogInsert($language['illegal_dynamci_insert_value'].$value);	
						}
					}else{
						GeneralFunc::LogInsert($language['none_dynamic_insert_key'].$key);
					}
				}
			}else{
				GeneralFunc::LogInsert($language['dynamic_insert_not_array']);
			}
		}
		//var_dump ($new_dynamic_insert);
		//var_dump ($dynamic_insert);
		//exit;
	}
	// 获取 链表单位编号 获取 CODE 数组
	// public static function getCode_from_DlinkedList($ListID){	
	//     //HIRO return OrgansOperator::getCode($ListID);
	// } 	

}