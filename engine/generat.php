<?php

//////////////////////////////////////////
//堆栈指针 寄存器
define ('STACK_POINTER_REG','ESP');
require dirname(__FILE__)."/include/common.inc.php";
require dirname(__FILE__)."/library/general.func.php";
require dirname(__FILE__)."/library/generate.func.php";
require dirname(__FILE__)."/library/mem.func.php";
require dirname(__FILE__)."/library/reloc.func.php";
require dirname(__FILE__)."/library/organ.func.php";
require dirname(__FILE__)."/library/instruction.func.php";
require dirname(__FILE__)."/library/character.func.php";
require dirname(__FILE__)."/organs/poly.php";
require dirname(__FILE__)."/organs/bone.php";
require dirname(__FILE__)."/organs/meat.php";
require dirname(__FILE__)."/organs/fat.php";
require dirname(__FILE__)."/library/config.func.php";
require dirname(__FILE__)."/../nasm.inc.php";
require dirname(__FILE__)."/library/oplen.func.php";
// require dirname(__FILE__)."/library/rel.jmp.func.php";
require dirname(__FILE__)."/library/debug.func.php";
require dirname(__FILE__)."/library/stero.graphic.class.php";
require dirname(__FILE__)."/library/generate.stack.balance.func.php";
//////////////////////////////////////////
//捕获超时
$complete_finished = false; //执行完成标志
register_shutdown_function('shutdown_except');

//init()
Instruction::init();
if (!isset($argv)){
	$argv = array();
}
CfgParser::get_params($argv,1); //同时支持$_GET/$_POST/命令行输入 的参数
Character::init();
OrganPoly::init();
OrganBone::init();
OrganMeat::init();

if (!GeneralFunc::LogHasErr()){

	if (CfgParser::params('echo')){
		require_once dirname(__FILE__)."/library/debug_show.func.php";

		$a = CfgParser::params('echo');
		if (is_array($a)){
			//////////////////////////////////////////
			//organ debug echo
			if ((isset($a['all'])) or (isset($a['bone']))){
				define ('BONE_DEBUG_ECHO',true);
				echo "<br><font color=blue><b>* BONE_DEBUG_ECHO</b></font>";
			}
			if ((isset($a['all'])) or (isset($a['meat']))){
				define ('MEAT_DEBUG_ECHO',true);
				echo "<br><font color=blue><b>* MEAT_DEBUG_ECHO</b></font>";
			}
			if ((isset($a['all'])) or (isset($a['poly']))){
				define ('POLY_DEBUG_ECHO',true);
				echo "<br><font color=blue><b>* POLY_DEBUG_ECHO</b></font>";
			}
			if ((isset($a['all'])) or (isset($a['sec']))){
				define ('SEC_DEBUG_ECHO',true);
				echo "<br><font color=blue><b>* SEC_DEBUG_ECHO</b></font>";
			}
			if ((isset($a['all'])) or (isset($a['char']))){
				define ('CHARACTER_DEBUG_ECHO',true);
				echo "<br><font color=blue><b>* CHARACTER_DEBUG_ECHO</b></font>";
			}			
		}
	}
	set_time_limit(CfgParser::params('timelimit'));	

	$max_output = CfgParser::params('maxoutput');

	$out_file   = CfgParser::params('_file').".out.asm";	
	$outputfile = CfgParser::params('_file'); 
}

if (!GeneralFunc::LogHasErr()){	

	define ('UNIQUEHEAD',CfgParser::get_rdy('UniqueHead'));
	$garble_rel_info                 = CfgParser::get_rdy('garble_rel_info');
	$CodeSectionArray                = CfgParser::get_rdy('CodeSectionArray');
	$sec_name                        = CfgParser::get_rdy('sec_name');
	$all_valid_mem_opcode_len        = CfgParser::get_rdy('valid_mem_len');
	$output_type                     = CfgParser::get_rdy('output_type');
	$ready_preprocess_config         = CfgParser::get_rdy('preprocess_config');   //保护(不进行混淆)设置
	$dynamic_insert                  = CfgParser::get_rdy('dynamic_insert');      //动态 插入
	$preprocess_sec_name             = CfgParser::get_rdy('preprocess_sec_name'); //预处理阶段收集的操作目标段 名
	$stack_balance_array             = CfgParser::get_rdy('stack_balance_array');	
	$organs                          = CfgParser::get_rdy('organs');

	if (ENGIN_VER !== CfgParser::get_rdy('engin_version')){
		GeneralFunc::LogInsert('unmatch generat version: '.ENGIN_VER.' !== '.CfgParser::get_rdy('engin_version'),ERROR);
	}
	ValidMemAddr::init(CfgParser::get_rdy('valid_mem_index'),CfgParser::get_rdy('valid_mem_index_ptr'));
	// 加载输出文件格式 解析器
	$file_format_parser = dirname(__FILE__)."/IOFormatParser/".$output_type.".IO.php";
	if (file_exists($file_format_parser)){
		require $file_format_parser;
	}else{
		GeneralFunc::LogInsert('type without file format parser');
	}
	
	CfgParser::unset_rdy();

	if (('BIN' !== $output_type)&&('COFF' !== $output_type)){ // 未知的文件类型
		GeneralFunc::LogInsert($language['unkown_output_type']."$output_type");      
	}

	if (!empty($dynamic_insert)){ // 确定 POST or Get 传递进来的动态插入数据
		GeneralFunc::get_dynamic_insert_value($dynamic_insert);
	}

	$poly_strength = array();   // $cf['Poly_']; 多态强度 $poly_strength['sec'] = strength;

	$mem_usage_record = array(); // 内存使用记录
	
	$exetime_record = array();
	GeneralFunc::exetime_record(); // 获取程序开始执行的时间

	////////////////////////////////////////////////////////////////////////////////

	if (defined('DEBUG_ECHO')){
		CfgParser::params_show();
		CfgParser::show($sec_name);
	}
	////////////////////////////////////////////////////////////////////////////////
	//比较预处理设置项是否更改(相较ready阶段)
	if ($ready_preprocess_config !== CfgParser::get_preprocess_config()){
		GeneralFunc::LogInsert($language['nomatch_preprocess_config']);		
	}
	unset($ready_preprocess_config);
	unset($preprocess_config);

	//比较 段名是否有新增(相较ready阶段)
	CfgParser::CmpPreprocess_sec($preprocess_sec_name);		

	//初始化 汇编输出文件 以及 动态写入 内容
	$init_asm_file = '[bits '.OPT_BITS."]\r\n";
	foreach ($dynamic_insert as $a => $b){			
		if (isset($b['new'])){
			$insert_value = $b['new'];
		}else{           //无最新赋值则使用 原始值
			$insert_value = $b['default'];
		}
		$init_asm_file .= '%define '.UNIQUEHEAD.'dynamic_insert_'.$a.' '.$insert_value."\r\n";
	}

	file_put_contents($out_file,$init_asm_file);

	$reloc_info_2_rewrite_table = array(); // 重定位信息 ,用来 重写 Obj文件中的重定位表 / 
										   // 次序与 汇编后 段头 dword 部分 相同

	$non_null_labels = array(); // 应用跳转 标号 的 非 零 单位
								// 需要 编译完成后 再修改其值
}

// deal by each section
foreach ($CodeSectionArray as $sec => $body){

	echo "<br>++++++++++++++++++++++++ $sec ++++++++++++++++++++++++++ <br>"; 
	
	OrgansOperator::import($organs[$sec]);
	$c_user_cnf = CfgParser::get_user_cnf($sec);
	OrgansOperator::setStackPattern($c_user_cnf['stack_pointer_define']);

	CfgParser::reconfigure_soul_usable($sec);
	
	OrgansOperator::initIpsp();

	if (defined('DEBUG_ECHO') and defined('SEC_DEBUG_ECHO')){
		DebugShowFunc::my_shower_01($sec,NULL);
	}
	// OrgansOperator::show();

	if (GeneralFunc::LogHasErr()){
		break;
	}
	// init reloc information
	if (isset($garble_rel_info[$sec])){
		RelocInfo::init($garble_rel_info[$sec]);
	}else{
		RelocInfo::init(array());
	}
	// 根据 双向链表 信息 初始化 character.Rate
	Character::flushUnits();
	$c = OrgansOperator::getBeginUnit();
	while ($c){
		Character::initUnit($c,SOUL);
		$c = OrgansOperator::next($c);
	}

	$OverMaxBinSize = false;
	$MaxBinSize 	= false;
	if (isset($c_user_cnf['output_opcode_max'])){ // 设置了最大输出，则必须计算rel.jmp
		$MaxBinSize = $c_user_cnf['output_opcode_max'];
		if ($c_user_cnf['output_opcode_max'] < $body['SizeOfRawData']){ // 设置最大输出size 不足原代码，显示错误
			GeneralFunc::LogInsert($language['section_name'].$body['name'].$language['section_number']."$sec ".$language['max_output_less_org']);
			break;
		}elseif ($c_user_cnf['output_opcode_max'] == $body['SizeOfRawData']){ // 设置最大输出size 等于 原代码，显示警告
			GeneralFunc::LogInsert($language['section_name'].$body['name'].$language['section_number']."$sec ".$language['max_output_equal_org'],2);
		}
	}

	// stack balance gen
	GenerateFunc::do_ready();
	StackBalance::start($stack_balance_array[$sec]);
	if (true === GenerateFunc::check_rollback($sec,'stack balance',$MaxBinSize)){
		$OverMaxBinSize = true;
	}

	// 测试soul_usable 项是否有问题,所有可写单位(寄存器,内存地址)和可读单位(内存地址)都写入操作代码，(注:flag目前不管)
	//                             这样...当soul_usable有问题，我们就能通过运行结构文件发现了(除不会出错的那种问题...)
	if ((isset($c_user_cnf['gen4debug01'])) and (true === $c_user_cnf['gen4debug01'])){
		GeneralFunc::LogInsert("gen4debug01 option was effected on section: $sec , name: ".$body['name'],3); // debug 应用,提示之
		DebugFunc::debug_usable_array();
	 	if (true !== ($failId = OrgansOperator::resetRelJmpExpired())){
		 	GeneralFunc::LogInsert('fail!,rel jmp range more than max,No: '.$failId,ERROR);
		}
	}else{
		$organ_process = GenerateFunc::GenOrganProcess(CfgParser::GetStrength($sec),CfgParser::params('maxstrength'));
		// $organ_process = array(BONE);
		if (empty($organ_process)){
			GeneralFunc::LogInsert($language['section_name'].$body['name'].$language['section_number']."$sec ".$language['section_without_garble'],2);
		}

        if (defined('DEBUG_ECHO') and defined('CHARACTER_DEBUG_ECHO')){
			Character::show();
		}

		foreach ($organ_process as $c_process){
			if ($OverMaxBinSize){ // over max binary size -> give up other try
				break;
			}
			// clear units' character.Rate which valid range too less.
			$tmp = OrgansOperator::getLessJmpRangeUnits(MIN_REL_JMP_RANGE_RESERVE);
			if (!empty($tmp)){
				foreach ($tmp as $u){
					Character::removeRate($u);
				}
			}
			// choose obj and decrease character.Rate
			$objs = false;
            if (POLY === $c_process){          	
				if (false !== ($a = Character::random(POLY))){
					$objs[1] = $a;					
					Character::modifyRate(POLY,$a,-1); // <- poly失败回滚，这里仍然 -1
				}						
			}elseif (MEAT === $c_process){
				if (false !== ($a = Character::random(MEAT))){
					$objs[1] = $a;
					Character::modifyRate(MEAT,$a,-9);
				}				
			}elseif (BONE === $c_process){
				$a = Character::random(BONE);
				$b = Character::random(BONE);
				$objs = OrgansOperator::getAmongUnits($a,$b);
				Character::modifyRate(BONE,$a,-1); // Bone 仅对撕裂位 character.Rate --
				Character::modifyRate(BONE,$b,-1);
			}else{
				GeneralFunc::LogInsert('unkown act in process: '.$c_process.' at section: '.$sec.'.',2);
			}			
			if (false === $objs){
			    continue;
			}
			//###### ready for RollBack
			GenerateFunc::do_ready();
			//###### Organ操作目标 开始处理
			if (POLY === $c_process){
				OrganPoly::start($objs);
			}elseif (MEAT === $c_process){
				OrganMeat::start($objs);
			}elseif (BONE === $c_process){
				OrganBone::start($objs);
			}

			if (!isset($exetime_record['organ'][$c_process])){
				$exetime_record['organ'][$c_process] = 0;
			}
			$exetime_record['organ'][$c_process] += GeneralFunc::exetime_record();

			if (true === GenerateFunc::check_rollback($sec,$c_process,$MaxBinSize)){
				$OverMaxBinSize = true;
			}
			
			if (defined('DEBUG_ECHO') and defined('CHARACTER_DEBUG_ECHO')){
				Character::show();
			}
		}
	}

	// generate asm code start...
	if (false === GenerateFunc::gen_asm_file($out_file,$sec,$reloc_info_2_rewrite_table,$non_null_labels,$MaxBinSize)){
		if (0 === $max_output){
			GeneralFunc::LogInsert($language['too_big_output']);
		}else{
			GeneralFunc::LogInsert($language['unkown_fatal_error_113']);
		}		
	}		

	if (!isset($exetime_record['gen_asm_file'])){
		$exetime_record['gen_asm_file'] = 0;
	}
	$exetime_record['gen_asm_file'] += GeneralFunc::exetime_record();
	
	$mem_usage_record[$sec] = number_format(memory_get_usage());
}

// die ("HIRO　die here!");
  
if (!GeneralFunc::LogHasErr()){
	// compile start ...
	$report_filename = "$out_file".'.report';
	$binary_filename = IOFormatParser::out_file_gen_name();
	
	exec ("$nasm -f bin \"$out_file\" -o \"$binary_filename\" -Z \"$report_filename\" -Xvc");
	
	$exetime_record['nasm final obj'] = GeneralFunc::exetime_record();

	if (file_exists($binary_filename)){
		$newCodeSection = array(); //$newCodeSection[节表编号][addr] 
								   //                         [size]
								   //                         [NumberOfRelocation]
		IOFormatParser::out_file_format_gen();

		//最后比较 最终生成代码长度 是否超过 用户配置 @output_opcode_max
		// $user_cnf = CfgParser::get_user_cnf();
		// foreach ($user_cnf as $uc_sec => $v){
		// 	if (isset($user_cnf[$uc_sec]['output_opcode_max'])){
		// 		//echo "<br><font color=red><b>test $uc_sec : ".$user_cnf[$uc_sec]['output_opcode_max'].' !< '.$newCodeSection[$uc_sec]['size'].'</font></b>';
		// 		if ($user_cnf[$uc_sec]['output_opcode_max'] < $newCodeSection[$uc_sec]['size']){
		// 			GeneralFunc::LogInsert($language['output_more_max'].' (sec:'.$uc_sec.')');
		// 			//出错，删除result文件
		// 			unlink($binary_filename);
		// 			break;
		// 		}
		// 	}
		// }
		
	}else{ //编译失败，参见Log文件 $report_filename
		GeneralFunc::LogInsert('compile fail, generate stopped.');
	}
	$exetime_record['others'] = GeneralFunc::exetime_record(); //获取程序执行的时间
}

//输出$output[] 到日志文件,jason格式
//if (isset($my_params['log'])){ 
//    file_put_contents($base_addr.'/'.$my_params['log'],json_encode($output));  
//}

$complete_finished = true; //执行完成标志
exit;

?>