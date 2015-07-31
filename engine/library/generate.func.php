<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

/******************************************/
//用于 Generate阶段 的 函数 集

class GenerateFunc{

	public static function do_ready(){
		OrgansOperator::ready();
		Character::ready();	
	}
	public static function check_rollback($sec,$c_process,$MaxBinSize){
		if (!OrgansOperator::doRelJmpMatchAll()){
			GeneralFunc::LogInsert('rollback ocurred in doRelJmpMatchAll() fail',WARNING);
			self::do_rollback();
			return 0;
		}
		if (false !== $MaxBinSize){
			$cTotalBinSize = OrgansOperator::getTotalBinSize();
			if (false === $cTotalBinSize){ // over range etc...
				GeneralFunc::LogInsert('rollback ocurred in resetRelJmpAll() fail',NOTICE);
				self::do_rollback();
				return 0;
			}
			if ($cTotalBinSize > $MaxBinSize){
				GeneralFunc::LogInsert('rollback ocurred for NotEnoughReserveSize, sec:'.$sec.',current byte:'.$cTotalBinSize.',max byte:'.$MaxBinSize,NOTICE);
				self::do_rollback();
				return true;// break;
			}
		}
		if (true !== OrgansOperator::resetRelJmp4OverMax()){
			GeneralFunc::LogInsert('rollback ocurred for RelJmpOverMax, sec:'.$sec.' process:'.$c_process,NOTICE);
			self::do_rollback();
			return 0;
		}
		return 0;
	}
	private static function do_rollback(){	
		OrgansOperator::rollback();
		Character::rollback();
	}

	//按正则清除 可用mem表
	public static function doFilterMemUsable(&$usable_mem){
		$sp_array = OrgansOperator::getStackPointArray();
		if (is_array($usable_mem)){		
			$tmp = $usable_mem;
			foreach ($tmp as $i => $a){
				foreach ($sp_array as $reg){
					if (ValidMemAddr::is_reg_include($a,$reg)){
						unset ($usable_mem[$i]);
						break;
					}
				}		
			}
		}
	}
	
	//////////////////////////////////////////////////
	//位数精度调整 (高 -> 低)
	// 如: (4,8,16,32) -> (8,32)
	//
	public static function bits_precision_adjust($bits){
		if ($bits <= 8){
			$bits = 8;
		}else{
			$bits = 32;
		}
		return $bits;
	}

	//////////////////////////////////////////////////
	//检测整数并返回位数(8,16,32)
	//非整数则返回false
	public static function get_bit_from_inter($value){
		if (!is_numeric($value)){ //过滤含非数字字符
			return false;
		}
		$a = intval($value);
		if ($a == $value){        //过滤小数点以及超过32位范围整数
			if (($a <= 127) and ($a >= -128)){
				return 8;
			}
			if (($a <= 32767) and ($a >= -32768)){
				return 16;
			}
			if (($a <= 2147483647) and ($a >= -2147483648)){
				return 32;
			}
		}

		return false;
	}

	//////////////////////////////////////////////////
	//检测16位数字
	private static function is_32bit_hex($value){
		return preg_match("/^[0-9A-F]{1,8}$/",$value);
	}
		
	//自定义随机 算法
	//
	// 返回true 概率 = 1/$n
	// rand (1,$a)
	//
	public static function my_rand($n){
		
		if (1 == $n){
			return true;
		}
		if ($n < 1){
			return false;
		}

		if (1 < mt_rand (1,$n)){
			return false;
		}

		return true;
	}

	///////////////////////////////////////////////
	//
	//2级随机，使分布更均匀
	//
	public static function multi_level_rand($one,$two){
		$a = mt_rand(1,$one);
		$a = intval(ceil($two / $a));
		$b = mt_rand(1,$a);
		return $b;
	}

	///////////////////////////////////
	//随机 整数
	//返回 $ret['value'] = 12
	//         [BITS]  = 8  
	//
	public static function rand_interger($bits = false){

		$usable_bits = array(4 => true,8 => true,16 => true,32 => true);

		$ret = 1;
		
		$tmp = $usable_bits;

		$new_ret['value'] = 1;
		$new_ret[BITS]  = 4;
			
		if (false !== $bits){ //往下覆盖，如指定 16位，包含 16,8,4位
			foreach ($tmp as $a => $b){
				if ($a > $bits){
					unset ($usable_bits[$a]);    
				}
			}
		}   

		if (count($usable_bits)){
			$bits = GeneralFunc::my_array_rand($usable_bits); 		
		
			//var_dump ($bits);
			if (4 === $bits){
				$ret = mt_rand (0,7);
			}elseif (8 === $bits){
				$ret = mt_rand (8,127);
			}elseif (16 === $bits){
				$ret = mt_rand (128,32767);   
			}else{
				$ret = mt_rand (32768,2147483647);			
			}	

			if (mt_rand(0,1)){
				$ret = '-'.$ret;
			}
		}
		$new_ret['value'] = $ret;
		$new_ret[BITS]  = $bits;
		return $new_ret;
	}
	
	// 生成 organs 处理流程 数组
	public static function GenOrganProcess($user_strength,$max_strength){

			$default_max = 0;
			if (isset($user_strength['default'])){
				$count = OrgansOperator::getUnitNumber();
				$default_max = intval(ceil(($count * $user_strength['default'])/100));
			}
			//
			if (!isset($user_strength[POLY]['max'])){
				$user_strength[POLY]['max'] = $default_max;			
			}
			if (!isset($user_strength[POLY]['min'])){
				$user_strength[POLY]['min'] = intval($user_strength[POLY]['max']/2);
			}elseif ($user_strength[POLY]['max'] < $user_strength[POLY]['min']){
				$user_strength[POLY]['max'] = $user_strength[POLY]['min'];
			} 
			//			
			if (!isset($user_strength[BONE]['max'])){
				$user_strength[BONE]['max'] = $default_max;			
			}
			if (!isset($user_strength[BONE]['min'])){
				$user_strength[BONE]['min'] = intval($user_strength[BONE]['max']/2);
			}elseif ($user_strength[BONE]['max'] < $user_strength[BONE]['min']){
				$user_strength[BONE]['max'] = $user_strength[BONE]['min'];
			}
			//
			if (!isset($user_strength[MEAT]['max'])){
				$user_strength[MEAT]['max'] = $default_max;			
			}
			if (!isset($user_strength[MEAT]['min'])){
				$user_strength[MEAT]['min'] = intval($user_strength[MEAT]['max']/2);
			}elseif ($user_strength[MEAT]['max'] < $user_strength[MEAT]['min']){
				$user_strength[MEAT]['max'] = $user_strength[MEAT]['min'];
			}

			$c_poly_strength = mt_rand($user_strength[POLY]['min'],$user_strength[POLY]['max']);
			$c_bone_strength = mt_rand($user_strength[BONE]['min'],$user_strength[BONE]['max']);
			$c_meat_strength = mt_rand($user_strength[MEAT]['min'],$user_strength[MEAT]['max']);

			//是否有强度超过 最大强度设置
			if (false !== $max_strength){
				if ($c_poly_strength > $max_strength){
					GeneralFunc::LogInsert('the strength number exceeds maximum of '.POLY.', ('.$c_poly_strength.' -> '.$max_strength.')',3);
					$c_poly_strength = $max_strength;
				}
				if ($c_bone_strength > $max_strength){
					GeneralFunc::LogInsert('the strength number exceeds maximum of '.BONE.', ('.$c_bone_strength.' -> '.$max_strength.')',3);
					$c_bone_strength = $max_strength;
				}
				if ($c_meat_strength > $max_strength){
					GeneralFunc::LogInsert('the strength number exceeds maximum of '.MEAT.', ('.$c_meat_strength.' -> '.$max_strength.')',3);
					$c_meat_strength = $max_strength;
				}
			}

			$process = array();
			
			for ($i = $c_poly_strength;$i > 0;$i--){		    
				$process[] = POLY;
			}    

			for ($i = $c_bone_strength;$i > 0;$i--){		    
				$process[] = BONE;
			}
			
			for ($i = $c_meat_strength;$i > 0;$i--){		    
				$process[] = MEAT;
			}
			
			shuffle($process);

			return $process;
	}
	//处理代码(重定位,尺寸strict)等...写入汇编文件前最后处理
	private static function gen_asm_file_kid($c_sec,$c_obj,&$buf,&$buf_head,&$reloc_info_2_rewrite_table,&$non_null_labels,$commit=''){
		global $sec;


		$asm = '';
		//当前指令 含有的重定位 (完整参数) 如： UNEST_RELINFO_104_3_2 + 123 
		//         后面判断 并 替换时 使用
		//         一条指令 可 含 多个重定位 (一个参数 至多 一个重定位，如多个，不知不同的exe加载器是否支持对同一地址的多次重定位运算)
		//         (ready部分限制了源码 一条指令 至多一个重定位，Poly 可导致一条指令 多个重定位)
		$rel_param_result = false;
		if (isset($c_obj[PREFIX])){
			if (is_array($c_obj[PREFIX])){
				foreach ($c_obj[PREFIX] as $z => $y){
					$asm .= $y.' ';
				}
			}
		}
		$asm .= $c_obj[OPERATION].' ';
									//                    _
		$last_params_type = 0;      // 最后参数 是否为 i   |
		$last_params_cont = "";     // 最后参数            |
		$mem_bits = 0;              // 内存参数 位数      _| 
		if ((isset($c_obj[PARAMS]))and(is_array($c_obj[PARAMS]))){
			foreach ($c_obj[PARAMS] as $z => $y){
				if ($z){
					$asm .= ',';
				}
				
				if (isset($c_obj[REL][$z])){
					$rel_param_result[$z]['org'] = $y;				             
				}
				
				if ((isset($c_obj[P_TYPE][$z]))and($c_obj[P_TYPE][$z] == 'm')){        //内存指针 参数，每个指令至多有一条
					$mem_bits = $c_obj[P_BITS][$z];
					//根据位数给内存指针加前缀
					if ('LEA' !== $c_obj[OPERATION]){  //lea eax,[...]
						if (8 == $c_obj[P_BITS][$z]){
							$asm .= 'byte ';
						}elseif (16 == $c_obj[P_BITS][$z]){
							$asm .= 'word ';
						}else{
							$asm .= 'dword ';
						}
					}
				}			
				$asm .= $y;

				$last_params_cont = $y;                  //最后一个参数
				$last_params_type = isset($c_obj[P_TYPE][$z])?$c_obj[P_TYPE][$z]:NULL;//最后一个参数 类型
				$last_params_bits = isset($c_obj[P_BITS][$z])?$c_obj[P_BITS][$z]:NULL;//最后一个参数 位数				
			}
		}	

		if (false !== $rel_param_result){
			//var_dump ($rel_param_result);
			if (count($rel_param_result) > 1){
				var_dump ($rel_param_result);
			}
			$label_buf = '';
			foreach ($rel_param_result as $z => $y){
				$c_rel_index = $c_obj[REL][$z]['i'];
				$c_rel_copy  = $c_obj[REL][$z][C];
				$c_rel_name  = UNIQUEHEAD.'RELINFO_'.$sec.'_'.$c_rel_index.'_'.$c_rel_copy; 
				
				//$c_rel = explode ('_',$y[REL]);
				//
				//当重定位 类型 isMem 且 最后参数为 imm，则重定位 不在末 4位，特殊处理，见 readme.reloc.txt
				//VirtualAddress				
				if ((RelocInfo::isMem($c_rel_index,$c_rel_copy))and($last_params_type === 'i')){
					$asm  = substr($asm,0,strlen($asm) - strlen($last_params_cont));
					//
					$last_params_modified_bits = $mem_bits;
					if ((8 == $last_params_bits) or (16 == $last_params_bits) or (32 == $last_params_bits)){ //最后整数单位位数有效且小于Mem.bits...
						if ($last_params_bits < $mem_bits){
							$last_params_modified_bits = $last_params_bits;
						}
						
					}
					if (8 == $last_params_modified_bits){
						$asm .= 'byte strict '.$last_params_cont;
						$buf_head .= 'dd '.$c_rel_name.'_label - sec_'."$c_sec".'_start - 4 - 1'.PHP_EOL;                
					}elseif (16 == $last_params_modified_bits){
						$asm .= 'word strict '.$last_params_cont;
						$buf_head .= 'dd '.$c_rel_name.'_label - sec_'."$c_sec".'_start - 4 - 2'.PHP_EOL;                
					}else{
						$asm .= 'dword strict '.$last_params_cont;
						$buf_head .= 'dd '.$c_rel_name.'_label - sec_'."$c_sec".'_start - 4 - 4'.PHP_EOL;                
					}
					//排错 (已解决?!)
					if ($mem_bits != $last_params_modified_bits){
						echo "<br> BUG?? : $mem_bits , $last_params_bits , $last_params_modified_bits , $last_params_type";
						echo "<br> <font color=red> $asm </font>";
					}
					//
				}else{
					$buf_head .= 'dd '.$c_rel_name.'_label - sec_'."$c_sec".'_start - 4'.PHP_EOL;                
				}
																						   //SymbolTableIndex
				$buf_head .= 'dd ';
				$buf_head .= RelocInfo::getSymbolTableIndex($c_rel_index,$c_rel_copy);
				$buf_head .= PHP_EOL;
				$buf_head .= 'dw ';
				$buf_head .= RelocInfo::getType($c_rel_index,$c_rel_copy);
				$buf_head .= PHP_EOL;

				$reloc_info_2_rewrite_table[$c_sec][] = $c_rel_name;
				$c_reloc_value = RelocInfo::getValue($c_rel_index,$c_rel_copy);
				if (RelocInfo::isLabel($c_rel_index,$c_rel_copy)){ //标号
					str_replace($c_rel_name,' strict '.$c_rel_name.'_label',$asm);
					$asm = str_replace($c_rel_name,' strict '.$c_rel_name.'_label',$asm);
					if ($c_reloc_value !== '0'){
						$non_null_labels[$sec][$c_rel_index][$c_rel_copy] = $c_reloc_value;
					}
				}else{                                              //参数
					if (RelocInfo::isMem($c_rel_index,$c_rel_copy)){//内存指针
						$asm = str_replace('[','[DWORD ',$asm);                     //作为内存指针的重定位  强制定位为32位 [DWORD xxx]
						$asm = str_replace($c_rel_name,'0x'.$c_reloc_value,$asm);
					}else{                                          //常数	
																	//常数有可能被多态为： mov eax,- (UNEST_RELINFO_104_3_2) / 需 2步替换
																	//                     先整个参数前加strict定义，然后再替换重定位值
						$asm = str_replace($y['org'],' strict dword '.$y['org'],$asm);
						$asm = str_replace($c_rel_name,'0x'.$c_reloc_value,$asm);
					}
				}
				$label_buf .= PHP_EOL.$c_rel_name.'_label'.' : ';
			}
			$buf .= $asm;
			$buf .= $label_buf;
		}else{			    
			$buf .= $asm;
		}			
		$buf .= "$commit".PHP_EOL;

		return;
	}

	public static function gen_asm_file($out_file,$a,&$reloc_info_2_rewrite_table,&$non_null_labels,$MaxBinSize){

		$cReserveBinSize = PHP_INT_MAX;
		if (false !== $MaxBinSize){
			$cReserveBinSize = $MaxBinSize - OrgansOperator::getTotalBinSize();
		}
		
		global $max_output; //输出 最大 行数

		$total_buf = '';
			
		if ($buf_head = IOFormatParser::out_file_buff_head($a)){
			$buf_head .= PHP_EOL;
		}		
		$buf = 'sec_'."$a".'_start:'.PHP_EOL;
		
		$c_unit = OrgansOperator::getBeginUnit();

		while ($c_unit){
		
			if ($max_output){
				$max_output --;
			}

			$asm = OrgansOperator::getCode($c_unit);
			//	insert Fat
			if (OrgansOperator::CheckFatAble($c_unit,P)){
				$buf .= OrganFat::start($c_unit,P,$cReserveBinSize);
				$buf .= PHP_EOL;
			}
			// 内容
			if (defined('DEBUG_ECHO')){
				$show_len = '['.$c_unit.']{';
				if ($c_len = OrgansOperator::getLen($c_unit)){
				 	$show_len .= $c_len;
				}
				$show_len .= '}';
				if($c_range = OrgansOperator::getRelJmpRange($c_unit)){
					$show_len .= '[range (without Fat):'."$c_range".']';
				}
			}else{
				$show_len = '';
			}

			if ($label = OrgansOperator::getLabel($c_unit)){
				$buf .= $label.' : '.' ; '.$show_len.$comment.PHP_EOL;
				
			}else{
				$comment = OrgansOperator::echoComment($c_unit);
				self::gen_asm_file_kid($a,OrgansOperator::getCode($c_unit),$buf,$buf_head,$reloc_info_2_rewrite_table,$non_null_labels,' ; '.$show_len.$comment);
			}
			// insert Fat
			if (OrgansOperator::CheckFatAble($c_unit,N)){
				$buf .= OrganFat::start($c_unit,N,$cReserveBinSize);
				$buf .= PHP_EOL;
			}
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			$c_unit = OrgansOperator::next($c_unit);
		}	

		$buf .= 'sec_'."$a".'_end:'.PHP_EOL;
		   
		$total_buf .= PHP_EOL.";********** section $a **********".PHP_EOL;
		$total_buf .= '[SECTION .'.$a.']'.PHP_EOL.'ALIGN 1'.PHP_EOL;
		$total_buf .= "$buf_head";
		$total_buf .= "$buf";
		
		if (0 === $max_output){	
			return false;
		}		

		file_put_contents ($out_file,$total_buf,FILE_APPEND);
		return true;

	}

}