<?php
// include env configures
require dirname(__FILE__)."/../env.conf.php";
// processor bits
define('OPT_BITS',32);
define('IO_PARSER',false);
define ('STACK_POINTER_REG','ESP');
class IOFormatParser{
	public static function in_file_format(){}

	public static function out_file_buff_head($sec){}

	public static function out_file_format_gen(){}

	public static function out_file_gen_name(){}

	public static function output_begin($sec){}

	public static function output_insert($inst,$operand,$reloc){}

}
?>