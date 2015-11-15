<?php

// load IoParser first
$ioParserName = false;
if (isset($argv)){
	if (count($argv) > 1){
		parse_str($argv[1],$tmp);
		if (isset($tmp['ioparser'])){
			$ioParserName = $tmp['ioparser'];
		}
	}	
}
if ((!$ioParserName)and(isset($_REQUEST['ioparser']))){
	$ioParserName = $_REQUEST['ioparser'];
}
if ($ioParserName){
	if (!preg_match("/^[a-z0-9]{1,}$/",$ioParserName)){
		$ioParserName = false;
	}
}
if ($ioParserName){
	$ioParserFile = dirname(__FILE__)."/io.parser/"."$ioParserName".".io.php";	
	if (!file_exists($ioParserFile)){		
		$ioParserName = false;
	}	
}
if (!$ioParserName){
	$ioParserFile = dirname(__FILE__)."/io.parser/"."default.fail.io.php";
}
require $ioParserFile;

require dirname(__FILE__)."/include/common.inc.php";
require dirname(__FILE__)."/library/ready.func.php";
require dirname(__FILE__)."/library/general.func.php";
require dirname(__FILE__)."/library/preprocess.func.php";
require dirname(__FILE__)."/library/config.func.php";
require dirname(__FILE__)."/library/instruction.func.php";
require dirname(__FILE__)."/library/predict.inst.len.php";
require dirname(__FILE__)."/library/ready.stack.balance.func.php";
require dirname(__FILE__)."/library/stero.graphic.class.php";
require dirname(__FILE__)."/library/disasm.parser.class.php";

Instruction::init();
PredictInstLen::init();

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
// 同时支持$_GET/$_POST/命令行输入 的参数
if (!isset($argv)){
	$argv = array();
}
CfgParser::get_params($argv);


if (!GeneralFunc::LogHasErr()){
	if (CfgParser::params('echo')){
		require dirname(__FILE__)."/library/debug_show.func.php";
	}	

	set_time_limit(CfgParser::params('timelimit'));	

	$bin_file = CfgParser::params('_file').".bin";
	$asm_file = CfgParser::params('_file').".asm";
	$rdy_file = CfgParser::params('_file').".rdy";     //obj 分析完成保存文件
	$out_file = CfgParser::params('_file').".out.asm";	

	define ('UNIQUEHEAD','UNEST_'); // 独特的头部标志，防止与代码中字符冲突 (如冲突，增加随机字符，注:禁止增加下划线 英文字符必须为大写)
	$pattern_reloc = '/('.UNIQUEHEAD.'RELINFO_[\d]{1,}_[\d]{1,}_[\d]{1,})/';  //匹配 reloc 信息	
}
if (defined('DEBUG_ECHO')){
		CfgParser::params_show();
}
if (!GeneralFunc::LogHasErr()){

	$exetime_record = array();
	GeneralFunc::exetime_record(); //获取程序开始执行的时间

	// 目标处理文件格式处理
	$myTables = array();
	@$handle = fopen(CfgParser::params('filename'),'rb');
	if (!$handle){
		GeneralFunc::LogInsert('fail to open file:'.CfgParser::params('filename'));
	}else{
		$buf = fread($handle,filesize(CfgParser::params('filename')));
		fclose($handle);

		$input_filesize = filesize(CfgParser::params('filename'));
		
		IOFormatParser::in_file_format();
		$exetime_record['analysis_input_file_format'] = GeneralFunc::exetime_record(); //获取程序执行的时间
	}
}

// 读取配置文件
if (!GeneralFunc::LogHasErr()){

	$protect_section   = array();   // 保护段设置
	$dynamic_insert    = array();   // 动态赋值设置
    
	$preprocess_sec_name = CfgParser::GetPreprocess_sec();
	if (!count($preprocess_sec_name)){
	    GeneralFunc::LogInsert($language['without_act_sec']);        
	}else{
		// 过滤 非指定 节表
		$ignore_sec = $preprocess_sec_name;
		$tmp = $myTables['CodeSectionArray'];
		foreach ($tmp as $a => $b){
			if (!isset($preprocess_sec_name[$b['name']])){
				unset ($myTables['CodeSectionArray'][$a]);
			}else{
			    unset($ignore_sec[$b['name']]);
			}
		}

		if (count($ignore_sec)){
			foreach ($ignore_sec as $a => $b){
				GeneralFunc::LogInsert($language['ignore_ready_sec'].$a,3);
			}
		}
        
		if (defined('DEBUG_ECHO')){
			DebugShowFunc::my_shower_07($myTables['CodeSectionArray'],$ignore_sec);
		}
	}

	if (CfgParser::get_preprocess_config('protect_section')){  // 保护段设置
		$protect_section = CfgParser::get_preprocess_config('protect_section');
		//检测是否重叠保护段
		if (PreprocessFunc::is_overlap_section($protect_section)){
			GeneralFunc::LogInsert($language['overlay_protect_section']);      
		}	
	}
	if (CfgParser::get_preprocess_config('dynamic_insert')){  // 动态写入设置
		$dynamic_insert = CfgParser::get_preprocess_config('dynamic_insert');
		//检测是否重叠保护段
		if (PreprocessFunc::is_overlap_section($dynamic_insert)){
			GeneralFunc::LogInsert($language['overlay_dynamic_insert']);      
		}	
	}	
}


if (!GeneralFunc::LogHasErr()){
	// 关联保护段和混淆目标段
	$protect_section_array = false; //['sec_number']['rva'] => size ; (rva = 相对段开头的偏移地址)
	if (!empty($protect_section)){
		$protect_section_array = PreprocessFunc::bind_protect_section_2_sec($protect_section,$myTables['CodeSectionArray'],$language);
	}
	//var_dump ($protect_section_array);
}

if (!GeneralFunc::LogHasErr()){
	// 关联保护段和混淆目标段
	$dynamic_insert_array = false; //['sec_number']['rva'] => size ; (rva = 相对段开头的偏移地址)
	if (!empty($dynamic_insert)){
		$dynamic_insert_array = PreprocessFunc::bind_dynamic_insert_2_sec($dynamic_insert,$myTables['CodeSectionArray'],$language);
	}
	//var_dump ($dynamic_insert_array);
}

//////////////////////////////////////////////////////////

if (!GeneralFunc::LogHasErr()){
    
	// save snippet into file and dissemble it
	$bin_filesize = 0;

	if (!count($myTables['CodeSectionArray'])){
	    GeneralFunc::LogInsert($language['no_target_sec']);
	}else{		
		$p_sec_abs = array(); // 保护区域(绝对 [开始] => 结束)(反汇编代码行号)
		$asm_size = ReadyFunc::collect_and_disasm($bin_file,$asm_file,DISASM_CMD,$myTables['CodeSectionArray'],$buf,$bin_filesize,$protect_section_array,$p_sec_abs,$language,false);            

		if (!GeneralFunc::LogHasErr()){
			$exetime_record['collect_and_disasm'] = GeneralFunc::exetime_record(); //获取程序执行的时间
		   
			$LineNum_Code2Reloc = array();  //代码对应重定位
											//$LineNum_Code2Reloc[节表编号][代码行数][重定位编号 1] = true;
											//                                       [.........  2] = true;
											//
			$AsmLastSec = array();          //节表末尾标行号[节表编号][代码行数] = true;
											//
			if ($asm_size){
				if (ReadyFunc::format_disasm_file($asm_file,$bin_filesize,$AsmResultArray,$language)){
					$exetime_record['format_disasm_file'] = GeneralFunc::exetime_record(); //获取程序执行的时间
                    if (!empty($protect_section)){ //处理 保护段 (把汇编指令修正为: db xx ，并合并为一个单位)  
					    PreprocessFunc::format_protect_section ($p_sec_abs,$AsmResultArray,$language);
						$exetime_record['format_protect_section'] = GeneralFunc::exetime_record(); //获取程序执行的时间						
					}
					ReadyFunc::sec_reloc_format($myTables,$AsmResultArray,$AsmLastSec,$language,$LineNum_Code2Reloc);			
					$exetime_record['sec_reloc_format'] = GeneralFunc::exetime_record(); //获取程序执行的时间
				}
			}else{
				GeneralFunc::LogInsert($language['disasm_file_not_found']);
			}
		}
	}
}
// var_dump($myTables['RelocArray']);
if (!GeneralFunc::LogHasErr()){
	$all_organs = array();
	// analysis each seg -> construction
	$all_secs = array_keys($myTables['CodeSectionArray']);
	var_dump ($all_secs);
	foreach ($all_secs as $c_sec){		
		$c_reloc = isset($LineNum_Code2Reloc[$c_sec])?$LineNum_Code2Reloc[$c_sec]:array();
		if ($repo = DisasmParser::start($c_sec,$AsmResultArray[$c_sec],$c_reloc)){
			OrganOptWrapper::create($c_sec);
			OrganOptWrapper::job_begin();
			$organ_id = false;
			$original_length_array = array();
			foreach ($repo as $i => $c_organ){
				$organ_id = OrganOptWrapper::organ_insert($c_organ,$organ_id,$i,array('soul.'.$i));
				if (!$organ_id){break;}
			}
			// attach a Virtual Unit at end
			$i ++;
			$organ_id = OrganOptWrapper::organ_insert(array(VIRT_UNIT=>true),$organ_id,$i,array('soul.'.$i));
			if (!$organ_id){
				unset ($myTables['CodeSectionArray'][$c_sec]);
				GeneralFunc::LogInsert('fail to OrganOptWrapper::organ_insert(),give up section: '.$c_sec,WARNING);
			}else{				
				if (false === OrganOptWrapper::job_commit()){
					unset ($myTables['CodeSectionArray'][$c_sec]);
					GeneralFunc::LogInsert('fail to job_commit(),give up section: '.$c_sec,WARNING);
				}else{
					// set public last unit
					OrgansOperator::Logics_set_public_last_unit($organ_id);
					// job finished , export now.
					$all_organs[$c_sec] = OrganOptWrapper::organ_export();
					// show
					if (defined('DEBUG_ECHO')){
						echo "<br><font color=red><b>$c_sec</b></font>";
						OrganOptWrapper::show();
					}					
				}				
			}
		}else{
			unset ($myTables['CodeSectionArray'][$c_sec]);
			GeneralFunc::LogInsert('fail parse,give up section: '.$c_sec,WARNING);
		}
	}
}

if (!GeneralFunc::LogHasErr()){
	// 抽取 节表名 .xxx$bbb >> convert to >> 'bbb'
	// $sec_name[xxx][] = sec_number
	$sec_name = array();
	foreach ($myTables['CodeSectionArray'] as $a => $b){
		$sec_name[$b['name']][] = $a;
	}
	// 根据 dynamic insert 记录 替换 $StandardAsmResultArray 中对应 整数参数
	$dynamic_insert_result = array();
	// if (!GeneralFunc::LogHasErr()){
	// 	$dynamic_insert_result = PreprocessFunc::dynamic_insert_dealer($dynamic_insert_array,$StandardAsmResultArray);
	// }
}
// die ('<br>died HIRO:'.__FILE__.':'.__LINE__);

if (!GeneralFunc::LogHasErr()){
	// unset all empty unit
	// $soul_forbid = GeneralFunc::multi_array_filter($soul_forbid);
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	//初始化完成，将数据保存入文档，供给下一步骤使用 
	// $rdy_output['garble_rel_info']                 = $garble_rel_info;
  
    $rdy_output['UniqueHead']                      = UNIQUEHEAD;
	$rdy_output['CodeSectionArray']                = $myTables['CodeSectionArray'];

	$rdy_output['preprocess_sec_name']             = $preprocess_sec_name;
	
	// $rdy_output['valid_mem_index']                 = $all_valid_mem_opt_index;
	// $rdy_output['valid_mem_len']                   = $all_valid_mem_opcode_len;
	// $rdy_output['valid_mem_index_ptr']             = count($all_valid_mem_opt_index);
	$rdy_output['sec_name']                        = $sec_name;
	
	$rdy_output['io_parser']                       = IO_PARSER;
	$rdy_output['engin_version']                   = ENGIN_VER;
	$rdy_output['preprocess_config']               = CfgParser::get_preprocess_config();
	$rdy_output['dynamic_insert']                  = $dynamic_insert_result;

	// $rdy_output['organs'] = $organs;

	// $rdy_output['stack_balance_array'] = $stack_balance_array;

	$rdy_output['all_organs'] = $all_organs;

	file_put_contents($rdy_file,serialize($rdy_output)); 			
}
echo "<br><br><br><br>";
echo "binary size: ";
if (isset($asm_size)){
	var_dump ($asm_size);
}
echo "<br><br><br><br>";

$complete_finished = true;
exit;

?>