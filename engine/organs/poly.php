<?php

class OrganPoly{

    private static $_index = 0; //poly 唯一编号，一组一个

	private static $_poly_model_index;
	private static $_poly_model_repo;

    // init
	public static function init(){
		$cf = @file_get_contents(dirname(__FILE__)."/../templates/poly.tpl");
        if ($cf == false){
			GeneralFunc::LogInsert('fail to open poly templates file: '.dirname(__FILE__)."/../templates/poly.tpl",WARNING);
		}else{
			$tmp = unserialize($cf);//反序列化，并赋值  
			if (POLY_TPL_VER !== $tmp['version']){
			    GeneralFunc::LogInsert('unmatch poly template version: ('.POLY_TPL_VER.' !== '.$tmp['version'].') '.dirname(__FILE__)."/../templates/poly.tpl",WARNING);
			}else{
			    self::$_poly_model_index = $tmp['index'];
				self::$_poly_model_repo  = $tmp['repo'];
			}
		}
	}	
	// 对可乱序的多态模板进行乱序处理
	// 返回 乱序后的多态模板
	private static function ooo ($poly_model){
		$ret = $poly_model;
			$t = $poly_model[OOO];        
			if (shuffle($t)){
				if ($t != $poly_model[OOO]){
						foreach ($poly_model[OOO] as $a => $b){
								if ($t[$a] != $b){
										$ret[CODE][$t[$a]]   = $poly_model[CODE][$b];
										$ret[P_TYPE][$t[$a]] = $poly_model[P_TYPE][$b];
									}
							}
					}
			}
		return $ret;
	}	
	// 检查目标多态模板是否可用 (new_regs 与 soul_usable['next'] 比较)
	// 随机部分 检查的同时 也 生成
	private static function check_poly_usable ($uid,&$usable_poly_model,&$rand_result){

		$org    = OrgansOperator::getCode($uid);
		$c_usable = OrgansOperator::Get_Unit_Usable($uid);

		$obj = $org[INST];
		$tmp = $usable_poly_model;
		foreach ($tmp as $a => $b){
			// 检查new stack 是否冲突 , not use yet
			if ((!$c_usable[P][STACK_USABLE]) and (!$c_usable[N][STACK_USABLE])){
				if ((isset(self::$_poly_model_repo[$obj][$b]['new_stack']))and(true === self::$_poly_model_repo[$obj][$b]['new_stack'])){
					if (defined('DEBUG_ECHO') && defined ('POLY_DEBUG_ECHO')){
						echo "<font color=red>stack conflict!";
						var_dump ($usable_poly_model[$a]);
						echo '</font>';
					}
					unset($usable_poly_model[$a]);
					continue;
				}		    
			}
			////////////////////////
			$break = false;
			if (isset(self::$_poly_model_repo[$obj][$b][NEW_REGS][NORMAL])){ // 检查新增 通用 寄存器 或 内存地址
				foreach (self::$_poly_model_repo[$obj][$b][NEW_REGS][NORMAL] as $c){ // 目前 仅考虑 OPT_BITS(bits) 通用寄存器
					if (Instruction::getGeneralRegIndex($org[OPERAND][$c])){         // 原始指令参数中的通用寄存器
						$c = Instruction::getGeneralRegIndex($org[OPERAND][$c]);
						if ((!isset($c_usable[N][GPR_WRITE_ABLE][$c][OPT_BITS]))or(!$c_usable[N][GPR_WRITE_ABLE][$c][OPT_BITS])){ // 仅 检查 Next 部分，见 readme_poly.txt 2013/04/19
							//echo "<br> $sec $line $c";
							unset ($usable_poly_model[$a]);
							$break = true;
							break;
						}
					}elseif (Instruction::getGeneralRegIndex($c)){ // 独立的通用寄存器
						if ((!isset($c_usable[N][GPR_WRITE_ABLE][$c][OPT_BITS]))or(!$c_usable[N][GPR_WRITE_ABLE][$c][OPT_BITS])){
							unset ($usable_poly_model[$a]);
							$break = true;
							break;
						}
					}else{ // 内存地址
						$available = false;
						if (in_array($org[OPERAND][$c], $c_usable[N][MEM_WRITE_ABLE])){
							$available = true;
						}
						if (!$available){
							// var_dump ($org[PARAMS][$c]);
							unset ($usable_poly_model[$a]);
							$break = true;
							break;
						}
					}
				}
			}	
			if ($break){
				continue;
			}
			if ((isset(self::$_poly_model_repo[$obj][$b][NEW_REGS][FLAG]))and(is_array(self::$_poly_model_repo[$obj][$b][NEW_REGS][FLAG]))){ //检查新增 标志 寄存器
				if (isset($c_usable[N][FLAG_WRITE_ABLE])){
					$zzz = array_diff(self::$_poly_model_repo[$obj][$b][NEW_REGS][FLAG],$c_usable[N][FLAG_WRITE_ABLE]);
				}else{
					$zzz = self::$_poly_model_repo[$obj][$b][NEW_REGS][FLAG];
				}
				if (!empty($zzz)){
					unset ($usable_poly_model[$a]);
					continue;
				}
			}
			// 需要获得 随机数(寄存器/内存)
			if (isset(self::$_poly_model_repo[$obj][$b][DRAND])){
				$zzz = self::setDrand(self::$_poly_model_repo[$obj][$b],$c_usable);
				if (false === $zzz){
					unset ($usable_poly_model[$a]);
					continue;
				}else{
					$rand_result[$a] = $zzz;
				}				
			}
		}
	}
	// set Drand element
	private static function setDrand($tpl,$c_usable){
		$ret = false;
		$rand_mem = false;
		foreach ($tpl[DRAND] as $z => $y){
			if (shuffle ($y)){
				foreach ($y as $x){							
					$c_bits = intval(substr($x,1));
					$x = $x[0];
					if ($x == 'i'){
						$r_int = GenerateFunc::rand_interger();							
						$ret[$z] = $r_int['value'];
						$ret[P_TYPE][$z] = T_IMM;
					}elseif (($x == 'm')&&(!$rand_mem)){
						if ((isset($tpl[RAND_PRIVILEGE][$z]))and($tpl[RAND_PRIVILEGE][$z] & W)){ // 需要写权限
							$mid = self::get_rand_memid($c_usable[P][MEM_WRITE_ABLE],$c_bits);
							if (false !== $mid){
								$ret[$z] = $mid;
								$ret[P_TYPE][$z] = T_MEM;
								$rand_mem = true; // 内存地址只能 一次
							}							
						}else{ // readonly
							$mid = self::get_rand_memid($c_usable[P][MEM_READ_ABLE],$c_bits);
							if (false !== $mid){
								$ret[$z] = $mid;
								$ret[P_TYPE][$z] = T_MEM;
							}
						}
					}elseif ($x == 'r'){								
						if ((isset($tpl[RAND_PRIVILEGE][$z]))and($tpl[RAND_PRIVILEGE][$z] & W )){
							// need writable 
							if (isset($c_usable[P][GPR_WRITE_ABLE])){
								$c_usable_normal_reg = false;
								foreach ($c_usable[P][GPR_WRITE_ABLE] as $j => $k){
									if ((isset($k[$c_bits]))and($k[$c_bits])){
										$c_usable_normal_reg[$j] = $c_bits;
									}elseif (8 === $c_bits){
										if ((isset($k[9]))and($k[9])){
											$c_usable_normal_reg[$j] = 9;
										}
									}
								}
								if (false !== $c_usable_normal_reg){
									$r = array_rand($c_usable_normal_reg);
									$ret[$z] = Instruction::getRegByIdxBits($c_usable_normal_reg[$r],$r);										
									$ret[P_TYPE][$z] = T_GPR;
								}
							}
						}else{ // read only									
							$ret[$z] = GeneralFunc::my_array_rand(Instruction::getRegsByBits($c_bits));
							$ret[P_TYPE][$z] = T_GPR;
						}   
					}
					if (isset($ret[$z])){
						break;
					}
				}
			}
			if (!isset($ret[$z])){
				// echo '<br> !isset( ... ';
				return false;
			}
		}
		return $ret;
	}
	private static function get_rand_memid($mid_arr,$match_bits){
		if (shuffle($mid_arr)){
			foreach ($mid_arr as $mid){									
				if ($zzz = OrgansOperator::getMemOperandArr($mid)){
					// TODO: >= 即可
					if ($zzz[OP_BITS] === $match_bits){
						return $mid;
					}
				}
			}
		}
		return false;
	}
	// 根据 多态模板 生成 多态代码
	// 调用前已做过可用性检查，这里直接生成 返回
	private static function generat_poly_code($origial,$soul_usable,$poly_model,$rand_result,$int3 = false){

		$ret = array();

		if (isset($poly_model[OOO])){ //乱序
			$poly_model = self::ooo($poly_model);
		}
		if ($int3){
			$ret[CODE]['int3'][INST] = 'INT3';
		}
		if (isset($poly_model[FAT])){
			$ret[FAT] = $poly_model[FAT];
		}

		// $specific_usable = false;
		// if (isset($poly_model[SPECIFIC_USABLE])){
		// 	$specific_usable = $poly_model[SPECIFIC_USABLE];
		// }

		//修正参数中 数据(固定跳转/原参数继承/...)
		foreach ($poly_model[CODE] as $a => $b){//        foreach ($poly_model[OPERATION] as $a => $b){
			if (isset($b[LABEL_FROM])){
				$ret[CODE][$a][LABEL_FROM] = $b[LABEL_FROM];
				continue;
			}	

			$ret[CODE][$a][INST] = $b[INST];

			if (!isset($b[OPERAND])){ //无参数
				continue;
			}
			if (isset($b[IMM_IS_LABEL])){
				$ret[CODE][$a][IMM_IS_LABEL] = $b[IMM_IS_LABEL];
			}

			$bb = $b[OPERAND];
			foreach ($bb as $c => $d){
				if ('stack_pointer' === $d){
					$ret[CODE][$a][P_TYPE][$c]  = T_GPR;
					$d = STACK_POINTER_REG;
				}elseif ('stack_top' === $d){ // stack top
					$ret[CODE][$a][P_M_REG][SIB_BASE]  = STACK_POINTER_REG;
					$ret[CODE][$a][P_M_REG][ADDR_BITS] = OPT_BITS;
					$ret[CODE][$a][P_M_REG][OP_BITS]   = OPT_BITS;
					$ret[CODE][$a][P_TYPE][$c] = T_MEM;
				}else{
					//原参数的继承
					if (preg_match_all('/(p_)([\d]{1,})/',$d,$mat)){
						$mat = array_flip($mat[2]); 
						foreach ($mat as $z => $y){								
							$d = str_replace('p_'.$z,$origial[OPERAND][$z+1],$d);
							if (T_IMM === $origial[P_TYPE][$z+1]){
								if (isset($origial[IMM_IS_RELOC])){									
									echo '<br>HIRO:';
									$c_reloc_imm_array = OrgansOperator::getRelocArr($origial[IMM_IS_RELOC]);
									$ret[CODE][$a][IMM_IS_RELOC] = $c_reloc_imm_array;
									if (isset($poly_model[REL_RESET][$z])){
										foreach ($poly_model[REL_RESET][$z] as $v => $w){
											$ret[CODE][$a][IMM_IS_RELOC][$v] = $w;
										}
									}
								}
								if (isset($origial[IMM_IS_LABEL])){
									$ret[CODE][$a]['import_label'] = $origial[IMM_IS_LABEL];
								}
							}								
							if (!isset($poly_model[P_TYPE][$a][$c])){
								$poly_model[P_TYPE][$a][$c] = $origial[P_TYPE][$z+1];
							}
						}
					}
					if (preg_match_all('/(r_)([\d]{1,})/',$d,$mat)){ // 多态模板中的rand部分的替换
						$mat = array_flip($mat[2]);
						foreach ($mat as $z => $y){
							$d = str_replace('r_'.$z,$rand_result[$z],$d);
						}
						if (!isset($poly_model[P_TYPE][$a][$c])){
							$poly_model[P_TYPE][$a][$c] = $rand_result[P_TYPE][$z];
						}
					}						
				}
				$ret[CODE][$a][OPERAND][$c] = $d;
				if (isset($poly_model[P_TYPE][$a][$c])){
					$ret[CODE][$a][P_TYPE][$c] = $poly_model[P_TYPE][$a][$c];
				}
			}
		}
		return $ret;
	}
	// 根据多态目标 返回 可用多态模板数组,无可用返回false 
	// 此处不考虑usable限制，仅根据opt,para 获取所有可用tpl
    public static function get_usable_models($uid){
		$ret = false;
		$obj = OrgansOperator::getCode($uid);
		$reloc_info = OrgansOperator::getRelocInfo($uid);
		if ($obj){
		    $usable_poly_model = NULL;
		    if ((isset($obj[INST]))and(isset(self::$_poly_model_index[$obj[INST]]))){
		    	$usable_poly_model = self::$_poly_model_index[$obj[INST]];
			}
			if (is_array($usable_poly_model)){ // 初步 检测是否有可用多态模板(指令名)
				$p_num = 0;
				if (isset($obj[P_TYPE])){
					$p_num = count($obj[P_TYPE]);				
				}
				if (isset($usable_poly_model[$p_num])){
					$usable_poly_model = $usable_poly_model[$p_num];
				}
				if ($p_num){
					foreach ($obj[P_TYPE] as $a => $b){
						if ($b == T_GPR){ // 通用寄存器 可能有 直接按寄存器 进行的索引(优先于类型的索引)
							if (isset($usable_poly_model[$obj[OPERAND][$a]])){
								$usable_poly_model = $usable_poly_model[$obj[OPERAND][$a]];	
								continue;
							}
							$b = 'r';
						}
						if ($b == T_MEM){
							$b = 'm';
						}
						if ($b == T_IMM){ //常数 有可能含有 重定位 & 常数忽略位数
							$b = 'i';
							if (isset($obj[IMM_IS_RELOC])){
								$relArr = OrgansOperator::getRelocArr($obj[IMM_IS_RELOC]);
								if (false !== $relArr){
									$b = 'rel'.$relArr[RELOC_TYPE];
								}
							}
						}else{
							$b .= $obj[P_BITS][$a]; // 加上位数信息
						}
						if (isset($usable_poly_model[$b])){
							$usable_poly_model = $usable_poly_model[$b];
						}else{
							$usable_poly_model = NULL;
							break;
						}
					}
				}
				if (count($usable_poly_model)){											
				    $ret = 	$usable_poly_model;
				}
			}
		}
		return $ret;
	}
	// 对指定指令进行多态处理
	private static function collect_usable_poly_model($obj,&$ret){

		$usable_poly_model = self::get_usable_models($obj);

        if ($usable_poly_model){
			$rand_result = array();
			if (is_array($usable_poly_model)){
				self::check_poly_usable($obj,$usable_poly_model,$rand_result);				
				// echo '<br>zzzzzzzzzzzzzzzzzzzzzzz';
				// var_dump($obj);
				// var_dump($usable_poly_model);
				// var_dump($rand_result);

				//随机获得 多态模板
				if (!empty($usable_poly_model)){
					$x = GeneralFunc::my_array_rand($usable_poly_model);
					$c_rand_result = isset($rand_result[$x])?$rand_result[$x]:NULL;
					$c_obj    = OrgansOperator::getCode($obj);
					$c_usable = OrgansOperator::getUsable($obj);
					if (isset(self::$_poly_model_repo[$c_obj[INST]][$usable_poly_model[$x]])){ //开始根据 多态模板 生成 多态 代码
						if ('int3' === $x){
							$ret = self::generat_poly_code($c_obj,$c_usable,self::$_poly_model_repo[$c_obj[INST]][$usable_poly_model[$x]],$c_rand_result,true);
						}else{
							$ret = self::generat_poly_code($c_obj,$c_usable,self::$_poly_model_repo[$c_obj[INST]][$usable_poly_model[$x]],$c_rand_result);
						}
						return true;
					}else{						
						GeneralFunc::LogInsert('undefined poly model number: '.$c_obj[INST].'['.$x.']',WARNING);
					}
				}
			}
		}
		return false;
	}
	// 多态 处理
	public static function start ($objs){
		$obj = $objs[1];
		self::$_index ++;

		$c_poly_result = array();		
		if (self::collect_usable_poly_model($obj,$c_poly_result)){
			$insert_List_array = array();
			$reset_lable_array = array();
			$rid = max(array_keys($c_poly_result[CODE]));			
			$pos = $obj;
			foreach ($c_poly_result[CODE] as $id => $organ){
				if (isset($organ['import_label'])){
					$rid ++;
					$reset_lable_array[$rid][0] = $id;
					$reset_lable_array[$rid][1] = $organ['import_label'];
					$organ[IMM_IS_LABEL] = $rid;
					unset($organ['import_label']);					
				}
				$pos = OrganOptWrapper::organ_insert($organ,$pos,$id,array('poly.'.self::$_index,$obj));
			}
			if (!empty($reset_lable_array)){ // fix jmp's labels
				foreach ($reset_lable_array as $psid => $arr){
					$organ = array(LABEL_FROM => $arr[0]);
					OrganOptWrapper::organ_insert($organ,$arr[1],$psid,array('poly.'.self::$_index,$obj));
				}
			}
            OrganOptWrapper::organ_remove($obj);			
		}	
	}
}
?>