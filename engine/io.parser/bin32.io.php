<?php
// include env configures
require dirname(__FILE__)."/../env.conf.php";
// processor bits
define('OPT_BITS',32);
define('IO_PARSER','bin32');
define('STACK_POINTER_REG','ESP');

class IOFormatParser{

	private static $_output_asm;
	private static $_c_sec;
	private static $_fail_flag;
	private static $_reloc_arr;
    
	public static function in_file_format(){
		global $myTables;

		global $input_filesize;
		
			$myTables['CodeSectionArray'][1]['PointerToRawData'] = 0;
			$myTables['CodeSectionArray'][1]['name'] = '.text$unest_binary';
			$myTables['CodeSectionArray'][1]['SizeOfRawData']    = $input_filesize;
	}

	public static function out_file_buff_head($sec){
		return false;
	}

	public static function out_file_format_gen(){
		global $newCodeSection;
		global $binary_filename;

		$newCodeSection[1]['size'] = filesize($binary_filename);
	}

	public static function out_file_gen_name(){
		global $outputfile;
		return $outputfile;
	}

	public static function output_begin($sec){
		self::$_output_asm[$sec] = array();		
		self::$_fail_flag[$sec] = false;		
		self::$_reloc_arr[$sec]  = array();	
		self::$_c_sec = $sec;
	}

	public static function output_insert($line){
		self::$_output_asm[self::$_c_sec][] = $line;
	}

	public static function get_reloc_num($relType,$relArr,$value){
		self::$_fail_flag[self::$_c_sec] = true;
		return false;
	}

	private static function gen_asm_file($asmFilename){
		@$file = fopen($asmFilename, "w");
		if ($file){
			$c = '[bits 32]'.PHP_EOL;
			foreach (self::$_output_asm[self::$_c_sec] as $asm){
				$c .= $asm.PHP_EOL;
			}
			fwrite($file, $c);
			fclose($file);
			return true;
		}
		return false;
	}

	public static function output_commit($basePath,$filename,$dstFilename,$sec_array){
		$asmFilename    = $basePath.$filename.'.result.out.asm';
		$reportFilename = $basePath.$filename.'.result.out.report';
		$dstFilename    = $basePath.$dstFilename;
		if (self::gen_asm_file($asmFilename)){
			if (file_exists($dstFilename)){
				if (!unlink($dstFilename)){
					return false;
				}
			}
			exec (ASM_CMD." -f bin \"$asmFilename\" -o \"$dstFilename\" -Z \"$reportFilename\" -Xvc");
			if (file_exists($dstFilename)){
				return true;
			}
		}
		return false;
	}

	public static function show(){
		var_dump(self::$_output_asm[self::$_c_sec]);
	}
}


?>