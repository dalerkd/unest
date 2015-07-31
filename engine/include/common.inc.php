<?php

// ini_set('display_errors',0);
// error_reporting(E_ERROR);

require dirname(__FILE__)."/language.inc.php";

// rel_jmp range 极限保留字节数 (小于此数,所有单位character.Rate清除)
define ('MIN_REL_JMP_RANGE_RESERVE',5);

// 用户可设置变量部分
$user_option['del_last_nop'] = true; //自动去掉节表末尾用来对齐的 nop 以及 0xcch
// processor bits
define('OPT_BITS',32);
// data width define
define('QWORD_BITS',16);
define('DWORD_BITS',8);
define('WORD_BITS' ,4);
define('BYTE_BITS' ,2);
///////////////////////////////////////////
// 当前系统命令行 最长 字符 长度 见readme  2013/04/19
define ('ARG_MAX',5000);
//////////////////////////////////////////
define ('UNEST.ORG', TRUE);
////////////////////////////////////////////
// ready -> generate 中间文件版本号,避免ready生成的rdy文件被不匹配的generat处理
define ('ENGIN_VER',8);                  
//////////////////////////////////////////
// organs Templates Version
define('BONE_TPL_VER',1);
define('MEAT_TPL_VER',1);
define('POLY_TPL_VER',1);
//////////////////////////////////////////
// default DList start number
define ('DEFAULT_DLIST_FIRST_NUM',1);
//////////////////////////////////////////
// log level
define ('ERROR'   ,1);
define ('WARNING' ,2);
define ('NOTICE'  ,3);

//////////////////////////////////////////
// character types
  define ('CTPL_OPT',1); //指令 
//define ('CTPL_POS',2); //位置 (如：前中后)
//define ('CTPL_PRM',3); //参数 (如：有无整数)
  define ('CTPL_INI',4); //单位初始化 character

//////////////////////////////////////////
// 操作权限
define ('R' , 1);  //只读
define ('W' , 2);  //只写
define ('RW', 3);  //读写 R|W
define ('NP',-1);  //无操作 ? 应改为0 ?

//////////////////////////////////////////
// 方位 可用位操作符连接
define ('PREV',1);
define ('NEXT',2);
//////////////////////////////////////////
// stack effects extra
define ('STACK_EFFECT_1',-1);
//////////////////////////////////////////
// inst array index
define ('PREFIX',           1);
define ('OPERATION',        2);
define ('P_TYPE',           3);
define ('P_BITS',           4);
define ('PARAMS',           5);
define ('REL',              6);
define ('STACK',            7);
define ('P_M_REG',          8);
// soul usable
define ('P',                  9);
define ('N',                 10);
define ('FLAG_WRITE_ABLE',   11);
define ('NORMAL_WRITE_ABLE', 12);
define ('MEM_OPT_ABLE',      13);
// $soul_forbid
define ('FLAG',              14);
define ('NORMAL',            15);

// inst struction
define ('CODE',16);
define ('OPT', 17);
define ('BITS',18);
define ('REG', 19);

// List
define ('C',    20);
define ('LABEL',21);
//////////////////////////////////////////
// organs 
define ('MEAT',22);
define ('BONE',23);
define ('POLY',24);
define ('SOUL',25);

// others
define ('USABLE',26);

// poly templates
define ('FAT',            27);
define ('OOO',            28);
define ('DRAND',          29);
define ('RAND_PRIVILEGE', 30);
define ('R_FORBID',       31);
define ('P_FORBID',       32);
define ('NEW_REGS',       33);
define ('SPECIFIC_USABLE',34);
define ('FLAG_FORBID',    35);
define ('REL_RESET',      36);

?>