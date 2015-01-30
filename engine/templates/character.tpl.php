<?php
//
//copyright www.unest.org


//////////////////////////////////////////
//organ 常数定义
define ('BONE_MULTI_MAX_SIZE', 50);// 多通道 骨架 最大包含单位 (指令条数)
define ('MEAT_MAX_SINGLE_UNIT',50);// 单插入meat最大指令条数 (见 /readme/readme.meat.txt)
define ('MEAT_TPL_PER_TASK',3);    // 每次gen任务随机使用meat模板个数(>= 1)

define ('CHARA_MEAT_DIRTY',0);          // 血肉生成 容许脏构建 1; 不容许 0 (默认);
define ('CHARA_MEAT_MEM_PREFER_MAX',3); // 血肉生成 内存操作 相对于普通指令的优先权重 - MAX
define ('CHARA_MEAT_MEM_PREFER_MIN',1); // 血肉生成 内存操作 相对于普通指令的优先权重 - MIN

define ('CHARA_RELOC_INT',10); // 各organ生成时，重定位数占整数(即生成一个重定位数来作为整数)的比例 = (1/CHARA_RELOC_INT)

// character 优先级加(减)权级 - CTPL_OPT 根据指令

$character_tpl[CTPL_OPT]['RET'][POLY]  = 1; //遇操作指令'RET'，多态加权 1
$character_tpl[CTPL_OPT]['CALL'][POLY] = 2;

//$character_tpl[CTPL_PRM][POLY]['I'] = 1;

// character 优先级加(减)权级 - CTPL_TRA 根据类型 增加权重
$character_tpl[CTPL_INI][MEAT][MEAT]  = 1;
$character_tpl[CTPL_INI][MEAT][BONE]  = 1; 
$character_tpl[CTPL_INI][MEAT][POLY]  = 1; // MEAT 单位's POLY 初始 增加 +0

$character_tpl[CTPL_INI][POLY][MEAT]  = 2;
$character_tpl[CTPL_INI][POLY][BONE]  = 2;
$character_tpl[CTPL_INI][POLY][POLY]  = 1;

$character_tpl[CTPL_INI][BONE][MEAT]  = 2;
$character_tpl[CTPL_INI][BONE][BONE]  = 2;
$character_tpl[CTPL_INI][BONE][POLY]  = 2;

$character_tpl[CTPL_INI][SOUL][MEAT]  = 4;
$character_tpl[CTPL_INI][SOUL][BONE]  = 4;
$character_tpl[CTPL_INI][SOUL][POLY]  = 4;


?>