<?php
// load IoParser first
$rdyFailFlag  = 0; // 1.get rdy file name fail;
                   // 2.rdy file not exists; 
                   // 3.rdy parser fail; 
				   // 4.io parser not be set;
                   // 5.io_parser load fail;
$rdyPathName  = false;
$ioParserName = false;
$rdyContents  = array();
$basePath = '';
if (isset($argv)){
	if (count($argv) > 1){
		parse_str($argv[1],$tmp);
		if ((isset($tmp['base']))and(isset($tmp['rdy']))){
			$basePath = $tmp['base'];
			$rdyPathName = $tmp['rdy'];
		}
	}	
}
if ((!$rdyPathName)and(isset($_REQUEST['base']))and(isset($_REQUEST['rdy']))){
	$basePath    = $_REQUEST['base'];
	$rdyPathName = $_REQUEST['rdy'];
}
if (($basePath[strlen($basePath)-1]) != DIRECTORY_SEPARATOR){
	$basePath .= DIRECTORY_SEPARATOR;
}
if ($rdyPathName){
	$rdyPathName = $basePath.$rdyPathName;
	if (file_exists($rdyPathName)){
		$cf = @file_get_contents($rdyPathName);
		if ($cf == false){
			$rdyFailFlag = 3;
		}else{
			$rdyContents = unserialize($cf);
		}
	}else{$rdyFailFlag = 2;}
}else{$rdyFailFlag = 1;}
if (isset($rdyContents['io_parser'])){
	$ioParserName = $rdyContents['io_parser'];	
}else{
	$rdyFailFlag = 4;
}
$ioParserFile = dirname(__FILE__)."/io.parser/"."$ioParserName".".io.php";	
if (!file_exists($ioParserFile)){
	$ioParserFile = dirname(__FILE__)."/io.parser/".'default.fail.io.php';
}	
require $ioParserFile;

require dirname(__FILE__)."/include/common.inc.php";
require dirname(__FILE__)."/library/general.func.php";
require dirname(__FILE__)."/library/generate.func.php";
require dirname(__FILE__)."/library/instruction.func.php";
require dirname(__FILE__)."/library/predict.inst.len.php";
require dirname(__FILE__)."/library/character.func.php";
require dirname(__FILE__)."/organs/poly.php";
require dirname(__FILE__)."/organs/bone.php";
require dirname(__FILE__)."/organs/meat.php";
require dirname(__FILE__)."/organs/fat.php";
require dirname(__FILE__)."/library/config.func.php";
require dirname(__FILE__)."/library/oplen.func.php";
require dirname(__FILE__)."/library/debug.func.php";
require dirname(__FILE__)."/library/stero.graphic.class.php";
require dirname(__FILE__)."/library/generate.stack.balance.func.php";

// 捕获超时
$complete_finished = false; //执行完成标志
register_shutdown_function('shutdown_except');

// 依赖库检测
GeneralFunc::DependenciesChecker();
if (!GeneralFunc::LogHasErr()){
	require dirname(__FILE__)."/library/organ.wrapper.func.php";
}
// ioparser load success ?
if (!IO_PARSER){
	GeneralFunc::LogInsert('ioparser does not set or illegal');
}
if ($rdyFailFlag){
	switch ($rdyFailFlag){
		case 1:GeneralFunc::LogInsert('get rdy file name fail');break;
		case 2:GeneralFunc::LogInsert('rdy file not exists');break;
		case 3:GeneralFunc::LogInsert('rdy parser fail');break;
		case 4:GeneralFunc::LogInsert('io parser not be set');break;
		case 5:GeneralFunc::LogInsert('io_parser load fail');break;
	}
}
if (!GeneralFunc::LogHasErr()){
	// init()
	Instruction::init();
	PredictInstLen::init();
	if (!isset($argv)){
		$argv = array();
	}
	CfgParser::init_rdy($rdyContents);
	CfgParser::get_params($argv,1); //同时支持$_GET/$_POST/命令行输入 的参数
	Character::init();
	OrganPoly::init();
	OrganBone::init();
	OrganMeat::init();
}
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
			if ((isset($a['all'])) or (isset($a['sbalance']))){
				define ('SBALANCE_DEBUG_ECHO',true);
				echo "<br><font color=blue><b>* SBALANCE_DEBUG_ECHO</b></font>";
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
	// $garble_rel_info                 = CfgParser::get_rdy('garble_rel_info');
	$CodeSectionArray                = CfgParser::get_rdy('CodeSectionArray');
	$sec_name                        = CfgParser::get_rdy('sec_name');
	// $all_valid_mem_opcode_len        = CfgParser::get_rdy('valid_mem_len');
	$ready_preprocess_config         = CfgParser::get_rdy('preprocess_config');   //保护(不进行混淆)设置
	$dynamic_insert                  = CfgParser::get_rdy('dynamic_insert');      //动态 插入
	$preprocess_sec_name             = CfgParser::get_rdy('preprocess_sec_name'); //预处理阶段收集的操作目标段 名
	// $stack_balance_array             = CfgParser::get_rdy('stack_balance_array');	
	$all_organs                      = CfgParser::get_rdy('all_organs');

	if (ENGIN_VER !== CfgParser::get_rdy('engin_version')){
		GeneralFunc::LogInsert('unmatch generat version: '.ENGIN_VER.' !== '.CfgParser::get_rdy('engin_version'),ERROR);
	}
	// ValidMemAddr::init(CfgParser::get_rdy('valid_mem_index'),CfgParser::get_rdy('valid_mem_index_ptr'));
	
	CfgParser::unset_rdy();

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
}

if (!GeneralFunc::LogHasErr()){
	// deal by each section
	foreach ($CodeSectionArray as $sec => $body){
		echo "<br>++++++++++++++++++++++++ $sec ++++++++++++++++++++++++++ <br>"; 
		OrganOptWrapper::organ_import($sec,$all_organs[$sec]);
		
		// stack pointer define
		$c_user_cnf = CfgParser::get_user_cnf($sec);
		$c_user_cnf['stack_pointer_define'][STACK_POINTER_REG] = STACK_POINTER_REG;
		OrgansOperator::setStackPattern($c_user_cnf['stack_pointer_define']);
		// reset usable&forbid by manual
		CfgParser::reconfigure_soul_usable($sec);
		
		// OrgansOperator::initIpsp();

		// if (defined('DEBUG_ECHO') and defined('SEC_DEBUG_ECHO')){
		// 	DebugShowFunc::my_shower_01($sec,NULL);
		// }	
		
		// 根据 双向链表 信息 初始化 character.Rate
		Character::flushUnits();
		$c = OrgansOperator::getBeginUnit();
		while ($c){
			Character::initUnit($c,SOUL);
			$c = OrgansOperator::next($c);
		}
		Character::show();


		OrganOptWrapper::show();

		// $OverMaxBinSize = false;
		// $MaxBinSize 	= false;
		// if (isset($c_user_cnf['output_opcode_max'])){ // 设置了最大输出，则必须计算rel.jmp
		// 	$MaxBinSize = $c_user_cnf['output_opcode_max'];
		// 	if ($c_user_cnf['output_opcode_max'] < $body['SizeOfRawData']){ // 设置最大输出size 不足原代码，显示错误
		// 		GeneralFunc::LogInsert($language['section_name'].$body['name'].$language['section_number']."$sec ".$language['max_output_less_org']);
		// 		break;
		// 	}elseif ($c_user_cnf['output_opcode_max'] == $body['SizeOfRawData']){ // 设置最大输出size 等于 原代码，显示警告
		// 		GeneralFunc::LogInsert($language['section_name'].$body['name'].$language['section_number']."$sec ".$language['max_output_equal_org'],2);
		// 	}
		// }

		// // stack balance gen
		// $c_strength = CfgParser::GetStrength($sec);
		// $sb_min = $sb_max = 0;
		// if (isset($c_strength['SBAL']['min'])){$sb_min = intval($c_strength['SBAL']['min']);}
		// if (isset($c_strength['SBAL']['max'])){$sb_max = intval($c_strength['SBAL']['max']);}
		// $sb_strength = mt_rand($sb_min,$sb_max);
		// if ($sb_strength > 0){
		// 	var_dump ($sb_strength);
		// 	GenerateFunc::do_ready();
		// 	StackBalance::start($stack_balance_array[$sec],$sb_strength);
		// 	if (true === GenerateFunc::check_rollback($sec,'stack balance',$MaxBinSize)){
		// 		$OverMaxBinSize = true;
		// 	}
		// }
		// OrgansOperator::show();

		// $id = OrgansOperator::getBeginUnit();
		// while ($id){
		// 	if (!OrgansOperator::isVirtUnit($id)){
		// 		echo '<br>********************';
		// 		var_dump($id);
		// 		echo '--------------------';
		// 		var_dump(OrgansOperator::Get_Unit_Usable($id,P));
		// 		echo '--------------------';
		// 		var_dump(OrgansOperator::Get_Unit_Usable($id,N));
		// 	}
		// 	$id = OrgansOperator::next($id); 
		// }
		// exit;


		// 测试soul_usable 项是否有问题,所有可写单位(寄存器,内存地址)和可读单位(内存地址)都写入操作代码，(注:flag目前不管)
		//                             这样...当soul_usable有问题，我们就能通过运行结构文件发现了(除不会出错的那种问题...)
		if ((isset($c_user_cnf['gen4debug01'])) and (true === $c_user_cnf['gen4debug01'])){
			GeneralFunc::LogInsert("gen4debug01 option was effected on section: $sec , name: ".$body['name'],NOTICE);
			DebugFunc::debug_usable_array();				 
		}else{
			$organ_process = GenerateFunc::GenOrganProcess(CfgParser::GetStrength($sec),CfgParser::params('maxstrength'));
			$organ_process = array(POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,POLY,);
			if (empty($organ_process)){
				GeneralFunc::LogInsert($language['section_name'].$body['name'].$language['section_number']."$sec ".$language['section_without_garble'],2);
			}

	        if (defined('DEBUG_ECHO') and defined('CHARACTER_DEBUG_ECHO')){
				Character::show();
			}
		

			foreach ($organ_process as $c_process){
			// 		if ($OverMaxBinSize){ // over max binary size -> give up other try
			// 			break;
			// 		}
			// 		// clear units' character.Rate which valid range too less.
			// 		$tmp = OrgansOperator::getLessJmpRangeUnits(MIN_REL_JMP_RANGE_RESERVE);
			// 		if (!empty($tmp)){
			// 			foreach ($tmp as $u){
			// 				Character::removeRate($u);
			// 			}
			// 		}
			// 		// choose obj and decrease character.Rate
				$process_runner = false;
				$objs = false;
		        if (POLY === $c_process){          	
					if (false !== ($a = Character::random(POLY))){
						$objs[1] = $a;
						Character::modifyRate(POLY,$a,-1); // <- poly失败回滚，这里仍然 -1
						$process_runner = array('OrganPoly','start');
					}
					// }elseif (MEAT === $c_process){
			// 			if (false !== ($a = Character::random(MEAT))){
			// 				$objs[1] = $a;
			// 				Character::modifyRate(MEAT,$a,-9);
			// 			}				
				}elseif (BONE === $c_process){
					$a = Character::random(BONE);
					$b = Character::random(BONE);
					$objs = OrgansOperator::getAmongUnits($a,$b);
					Character::modifyRate(BONE,$a,-1); // Bone 仅对撕裂位 character.Rate --
					Character::modifyRate(BONE,$b,-1);
					$process_runner = array('OrganBone','start');
				}else{
		 			GeneralFunc::LogInsert('unkown act in process: '.$c_process.' at section: '.$sec.'.',WARNING);
				}

				if ((false === $process_runner)or(false === $objs)){
					continue;
				}				
				OrganOptWrapper::job_begin();
				$process_runner($objs);
				$effecs_arr = OrganOptWrapper::job_commit();
				if (false === $effecs_arr){
					GeneralFunc::LogInsert('a job_commit() returns fail,roll back now!!',WARNING);
				}else{
					foreach ($effecs_arr[1] as $removed_unit){
						Character::removeRate($removed_unit);
					}
					foreach ($effecs_arr[0] as $new_unit){
						Character::initUnit($new_unit,$c_process);
					}
				}				
				Character::show();
				// break;

				if (!isset($exetime_record['organ'][$c_process])){
					$exetime_record['organ'][$c_process] = 0;
				}
				$exetime_record['organ'][$c_process] += GeneralFunc::exetime_record();

			// 		if (true === GenerateFunc::check_rollback($sec,$c_process,$MaxBinSize)){
			// 			$OverMaxBinSize = true;
			// 		}
					
			// 		if (defined('DEBUG_ECHO') and defined('CHARACTER_DEBUG_ECHO')){
			// 			Character::show();
			// 		}
			// 	}
			}
		}

		IOFormatParser::output_begin($sec);
		OrganOptWrapper::asm_output(array('IOFormatParser','output_insert'),array('IOFormatParser','get_reloc_num'));
		IOFormatParser::show();

		if (!isset($exetime_record['gen_asm_file'])){
			$exetime_record['gen_asm_file'] = 0;
		}
		$exetime_record['gen_asm_file'] += GeneralFunc::exetime_record();
	}
}

// OrganOptWrapper::show();
// Character::show();

if (!GeneralFunc::LogHasErr()){
	if (!IOFormatParser::output_commit($basePath,CfgParser::params('filename'),CfgParser::params('outputfile'),$CodeSectionArray)){
		GeneralFunc::LogInsert('final output fail,check access permission or asm report.');
	}else{
		$complete_finished = true; //执行完成标志
	}
	$exetime_record['nasm final obj'] = GeneralFunc::exetime_record();	
}
exit;
?>