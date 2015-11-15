<?php

// ini_set('display_errors',0);
// error_reporting(E_ERROR);

require dirname(__FILE__)."/language.inc.php";

// rel_jmp range 极限保留字节数 (小于此数,所有单位character.Rate清除)
define ('MIN_REL_JMP_RANGE_RESERVE',5);

// 用户可设置变量部分
$user_option['del_last_nop'] = true; //自动去掉节表末尾用来对齐的 nop 以及 0xcch
// 
define ('TRACK_COMMENT_ON',1); // 
// data width define
// define('YWORD_BITS',256);
// define('OWORD_BITS',128);
define('TWORD_BITS',80);
// TODO: not match (up & down)
define('QWORD_BITS',16);
define('DWORD_BITS',8);
define('WORD_BITS' ,4);
define('BYTE_BITS' ,2);
// 单指令最大 字节数
define('SINGLE_INST_MAX',15);
///////////////////////////////////////////
// 当前系统命令行 最长 字符 长度 见readme  2013/04/19
define ('ARG_MAX',5000);
//////////////////////////////////////////
define ('UNEST.ORG', TRUE);
////////////////////////////////////////////
// ready -> generate 中间文件版本号,避免ready生成的rdy文件被不匹配的generat处理
define ('ENGIN_VER',9);                  
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
// operate access
define ('R' , 1);  //只读
define ('W' , 2);  //只写
define ('RW', 3);  //读写 R|W
define ('NP',-1);  //无操作 ? 应改为0 ?
//////////////////////////////////////////
// SIB
define ('SIB_BASE',1);
define ('SIB_INDEX',2);
define ('SIB_SCALE',3);
define ('SIB_DISPLACEMENT',4);
define ('ADDR_BITS',5);
define ('OP_BITS',6);
define ('DISPLACEMENT_IS_RELOC',7);
//////////////////////////////////////////
// Operand Types
define ('T_GPR',1);
define ('T_MEM',2);
define ('T_IMM',3);
define ('T_ORS',4);  // other regs
define ('T_EFS',5);  // eflags
define ('T_IGN',99); // not support,ignore it Now!

//////////////////////////////////////////
// 方位 可用位操作符连接
define ('PREV',1);
define ('NEXT',2);
//////////////////////////////////////////
// stack effects extra
define ('STACK_EFFECT_1',-1);
//////////////////////////////////////////
// prefix group
define ('PREFIX_GROUP_0',0);
define ('PREFIX_GROUP_1',1);
define ('PREFIX_GROUP_2',2);
define ('PREFIX_GROUP_3',3);
define ('PREFIX_GROUP_4',4);
//////////////////////////////////////////
// inst array index
define ('PREFIX',           1);
define ('INST',             2);
define ('OPERATION',        2); // todo: deprecated
define ('P_TYPE',           3);
define ('P_BITS',           4);
define ('OPERAND',          5);
define ('PARAMS',           5); // todo: deprecated
define ('REL',              6);
define ('STACK',            7);
define ('P_M_REG',          8);
define ('IMM_IS_RELOC',     9); // immediate is a reloc value
define ('IMM_IS_LABEL',    10); // immediate is a label
define ('DISP_IS_RELOC',   11); // diplacement is a reloc value
define ('LABEL_FROM',      12);
define ('ORIGINAL_LEN',    13); // optional
define ('EIP_INST',        14); // absolutly | condition jmp
define ('ATTACH_TAG',      15); // |NEAR,|FAR,|SHORT,|TO
define ('SEG_PREFIX',      16); //
define ('O_SIZE_ATTR',     17); // operand-size attribute
define ('VIRT_UNIT',       18); // virtual unit
// reloc section
define ('RELOC_VIRTUALADDRESS',  1);
define ('RELOC_SYMBOLTABLEINDEX',2);
define ('RELOC_TYPE',            3);
// define ('REL_SECT',1);
// define ('REL_NUMB',2);
// define ('REL_COPY',3);
// direct
define ('P',                  1);
define ('N',                  2);
// ret usable
define ('FLAG_WRITE_ABLE',   11);
define ('NORMAL_WRITE_ABLE', 12); // todo: deprecated
define ('GPR_WRITE_ABLE',    12);
define ('MEM_OPT_ABLE',      13); // todo: deprecated
define ('MEM_WRITE_ABLE',    51);
define ('MEM_READ_ABLE',     52);
define ('STACK_USABLE',      53);
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

// get insts' len
define ('OPERAND_BITS',20); // 参数 位数
define ('OPERAND_TYPE',21); // 参数 类型
define ('OPLEN',11);        // 二进制代码字节数
define ('IMM_BIT',12);      // 立即数长度
define ('MODRM',14);        // ModR/M 位
define ('RELATIVE_ADDR',15);// 相对地址
define ('O_PREFIX',31);   // SIZE_OVERRIDE_PREFIX    0x67
define ('A_PREFIX',32);   // ADDRESS_OVERRIDE_PREFIX 0x67
define ('MUST_PREFIX',33); // 强制前缀,如前缀已存在则不处理,不存在则加上长度 1
define ('UA',99);   // 不可用
define ('WARN',98); // 警告

// general opcode structure (length units)
define ('GOS_PREFIX',       1);
define ('GOS_OPCODE',       2);
define ('GOS_ADDRMODE',     3);
define ('GOS_SIB',          4);
define ('GOS_DISPLACEMENT', 5);
define ('GOS_IMM',          6);
define ('GOS_REL',          7);


// tpl // todo: refactor poly tpl to fit future
define ('TPL_OP_T_IMM',1);
define ('TPL_OP_T_REG',2);
define ('TPL_OP_T_MEM',4);
define ('TPL_OP_T_REL',8);
define ('TPL_OP_T_EXCEPT_SP',16); // except STACK POINTS

define ('TPL_OP_B_8'  ,1);
define ('TPL_OP_B_16' ,2);
define ('TPL_OP_B_32' ,4);
define ('TPL_OP_B_64' ,8);
define ('TPL_OP_B_ALL',1|2|4|8);  // support all bits
define ('TPL_OP_B_20' ,1024);     // rel's property
define ('TPL_OP_B_CRR',2048);     // current bits

?>