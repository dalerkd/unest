<?php
// disasmed code -> structure
class DisasmParser{
	private static $_repo;
	const LABEL_POS = 1; // 类jmp指令 label operand always 在 1 位

	private static function flush(){
		self::$_repo = array();
	}

	private static function isImm($str){
		return (preg_match('/^(-|\+)?(0X){0,1}([0-9]|[A-F]){1,}$/',$str))?true:false;
	}

	private static function getBitsByStr($str){
		$str = trim($str);		
		if ('BYTE'  === $str){return 8;}
		if ('WORD'  === $str){return 16;}		
		if ('DWORD' === $str){return 32;}
		if ('QWORD' === $str){return 64;}
		if ('TWORD' === $str){return TWORD_BITS;}
		if ('OWORD' === $str){return 128;}
		if ('YWORD' === $str){return 256;}
		if ('ZWORD' === $str){return 512;}		
		return false;
	}

	private static function getAttachTag($str){
		$str = trim($str);
		if ('TO'    === $str){return true;}
		if ('SHORT' === $str){return true;}
		if ('FAR'   === $str){return true;}
		if ('NEAR'  === $str){return true;}
		return false;
	}


	private static function AsmParser($asm,$prefix){
		$ret = array();
		$o_resize = false;
		$weak_bits_operand = array(); // just for mem operand
		$strong_bits = array();
		if (!empty($prefix)){
			$ret[PREFIX] = $prefix;
			if (isset($prefix['o_resize'])){
				$o_resize = true;
			}
		}
		$tmp = preg_split('/( |,|\[)/',$asm,-1,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
		$ins_collected = false;
		$operand_id = 1;
		$c_prefix_bits = OPT_BITS;
		$c_weak_bits = true;
		foreach ($tmp as $i => $c){			
			if ($ins_collected){ // collect operands
				if (',' === $c){
					$c_prefix_bits = OPT_BITS;
					$operand_id ++;
					$c_weak_bits = true;
					continue;
				}else{
					if (!isset($ret[P_TYPE][$operand_id])){
						$c = trim($c);
						if (empty($c)){continue;}
						if ($z = self::getBitsByStr($c)){
							$c_prefix_bits = $z;
							$c_weak_bits = false;
							$strong_bits[$operand_id] = $z;
							continue;
						}
						if (self::getAttachTag($c)){
							$ret[ATTACH_TAG][$operand_id] = $c;
							continue;
						}
						if ('[' === $c){
							$ret[P_TYPE][$operand_id] = T_MEM;
							if ($c_prefix_bits === OPT_BITS){
								$ret[P_BITS][$operand_id] = Instruction::getCurrentBits($o_resize);								
								$c_weak_bits = false;
							}else{
								$ret[P_BITS][$operand_id] = $c_prefix_bits;
							}
							if ($c_weak_bits){
								$weak_bits_operand[] = $operand_id;
							}							
						}elseif (self::isImm($c)){
							$ret[P_TYPE][$operand_id] = T_IMM;
							$ret[P_BITS][$operand_id] = Instruction::getCurrentBits($o_resize);
						}elseif ($c_bits = Instruction::getGeneralRegBits($c)){
							$ret[P_TYPE][$operand_id] = T_GPR;
							$ret[P_BITS][$operand_id] = $c_bits;
							$strong_bits[] = $c_bits;
						}elseif (Instruction::isRegister($c)){
							$ret[P_TYPE][$operand_id] = T_ORS;
							$ret[P_BITS][$operand_id] = false;
						}else{
							$ret[P_TYPE][$operand_id] = T_IGN;
							$ret[P_BITS][$operand_id] = $c_bits;
							GeneralFunc::LogInsert('Ignore an unkown operand in: '.$asm,WARNING);
						}
						$ret[OPERAND][$operand_id] = $c;
					}else{
						$ret[OPERAND][$operand_id] .= $c;
					}
				}
			}
			
			$c = trim($c);
			if (empty($c)){continue;}
			
			if (Instruction::isPrefixInst($c)){
				
			}elseif (false !== Instruction::getInstructionOpt($c)){
				$ins_collected = true;
				$ret[INST] = $c;
			}			
		}
		$a = 0;
		$b = 0;
		$c = 0;
		if (isset($ret[OPERAND])){$a = count($ret[OPERAND]);}
		if (isset($ret[P_TYPE])) {$b = count($ret[P_TYPE]);}
		if (isset($ret[P_BITS])) {$c = count($ret[P_BITS]);}
		if (($a !== $b) or ($b !== $c)){return false;}
		// inherit p_bits if mem operand with weak
		if (!empty($weak_bits_operand)){
			if (!empty($strong_bits)){
				$ret[P_BITS][$weak_bits_operand[0]] = $strong_bits[0];
			}else{
				GeneralFunc::LogInsert('fail to reset value for weak operand: '.$asm,WARNING);
			}
		}
		// echo '<br>'.$asm;
		// var_dump($weak_bits_operand);
		// var_dump($strong_bits);
		return $ret;
	}

	private static function memAddrParser($unit){
		$ret = array();
		if (isset($unit[OPERAND])){
			$operands_array = $unit[OPERAND];
			foreach ($operands_array as $i => $operands){
				if (T_MEM === $unit[P_TYPE][$i]){
					$a_resize = false;
					if (isset($unit[PREFIX]['a_resize'])){$a_resize = true;}
					$ret[ADDR_BITS] = Instruction::getCurrentBits($a_resize);
					$ret[OP_BITS]   = $unit[P_BITS][$i];
					$total_len = strlen($operands);
					if (('[' === $operands[0]) and (']' === $operands[$total_len-1])){
						$operands = substr($operands, 1,$total_len - 2);
					}else{
						return false;
					}

					// get Segment Override in [mem addr]
					if (isset($unit[PREFIX])){
						$seg_prefix = array_search(2,$unit[PREFIX]);
						if (false !== $seg_prefix){
							$ret[SEG_PREFIX] = $seg_prefix;
							$j = preg_split("/".$seg_prefix.":/", $operands);
							$operands = implode($j);
						}
					}				

					$tmp = preg_split('/(\+|-)/',$operands,-1,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

					$Prev_is_negative = false;

					foreach ($tmp as $v){
						$v = trim($v);
						if (!empty($v)){

							if ('+' === $v){
								$Prev_is_negative = false;
								continue;
							}elseif ('-' === $v){
								$Prev_is_negative = true;
								continue;
							}

							if ($bits = Instruction::getGeneralRegBits($v)){
								if (!isset($ret[ADDR_BITS])){
									$ret[ADDR_BITS] = $bits;
								}
								if (!isset($ret[SIB_BASE])){
									$ret[SIB_BASE] = $v;
								}else{
									$ret[SIB_INDEX] = $v;
								}
							}elseif (false !== strpos($v, '*')){
								$z = explode('*', $v);
								if (2 === count($z)){
									if ($bits = Instruction::getGeneralRegBits($z[0])){
										if (!isset($ret[ADDR_BITS])){
											$ret[ADDR_BITS] = $bits;
										}
										$ret[SIB_INDEX] = $z[0];
										$ret[SIB_SCALE] = intval($z[1]);
									}else{
										return false;
									}

								}
							}else{
								$z = explode(' ',$v);
								if (2 == count($z)){
									$v = $z[1];
									if (!isset($ret[ADDR_BITS])){
										if ($bbb = self::getBitsByStr($z[0])){
											$ret[ADDR_BITS] = $bbb;
										}										
									}
								}							
								if (self::isImm($v)){
									if ($Prev_is_negative){
										$v = '-'.$v;
									}
									$ret[SIB_DISPLACEMENT] = Instruction::hexImm2decImm($v,$Prev_is_negative,$ret[ADDR_BITS]);
								}else{
									return false;
								}
							}
						}
					}
					break; // 单条指令至多一个mem类型参数
				}
			}
		}		
		return $ret;
	}
	// 根据 机器码获取前缀
	private static function getPrefix($hex){
		$ret = array();
		$t_len = strlen($hex);	
		$i = 0;
		while ($i < $t_len){
			$c = substr($hex,$i,2);
			if ($r = Instruction::getPrefixNameByHex($c)){
				$ret[$r[0]] = $r[1];
			}else{
				break;
			}
			$i += 2;
		}
		return $ret;
	}
	// reloc
	private static function relocReplace($sec,&$unit,$reloc,$bin_code){
		foreach ($reloc as $i => $c_reloc){
			$define = $c_reloc['contents'];
			if (isset($unit[P_TYPE])){
				$imm_id = array_search(T_IMM, $unit[P_TYPE]);
				if (1 & $c_reloc['pos']){ // imm or displacement (without imm)
					if ($imm_id){
						$unit[IMM_IS_RELOC] = $define;
						// get lastest dword to imm (default value)
						if (strlen($bin_code) > OPT_BITS/4){
							$tmp = '';
							for ($i=0;$i<=OPT_BITS/4;$i+=2){
								$tmp .= substr($bin_code,strlen($bin_code)-$i,2);								
							}
							$unit[OPERAND][$imm_id] = Instruction::hexImm2decImm($tmp,false,OPT_BITS);
							if (false === $unit[OPERAND][$imm_id]){
								return false;
							}
						}else{
							return false;
						}						
					}elseif (isset($unit[P_M_REG][SIB_DISPLACEMENT])){
						$unit[DISP_IS_RELOC] = $define;
					}else{
						return false;
					}
				}
				if (2 & $c_reloc['pos']){ // displacement (with imm)
					if ($imm_id){
						if (isset($unit[P_M_REG][SIB_DISPLACEMENT])){
							$unit[DISP_IS_RELOC] = $define;
						}else{
							return false;
						}
					}else{
						return false;
					}
				}
			}else{
				return false;
			}
		}
		return true;
	}
	// refactor mem addr by P_M_REG
	// private static function refactorMemAddr(&$unit){
	// 	$mem_id = array_search(T_MEM, $unit[P_TYPE]);
	// 	if ($mem_id){
	// 		$mem_str = '';
	// 		if (isset($unit[P_M_REG][SIB_BASE])){
	// 			$mem_str = $unit[P_M_REG][SIB_BASE];
	// 		}
	// 		if (isset($unit[P_M_REG][SIB_INDEX])){
	// 			if (!empty($mem_str)){$mem_str .= '+';}
	// 			$mem_str .= $unit[P_M_REG][SIB_INDEX];
	// 		}
	// 		if (isset($unit[P_M_REG][SIB_SCALE])){
	// 			$mem_str .= '*'.$unit[P_M_REG][SIB_SCALE];
	// 		}
	// 		if (isset($unit[P_M_REG][SIB_DISPLACEMENT])){
	// 			if (!empty($mem_str)){$mem_str .= '+';}
	// 			$mem_str .= $unit[P_M_REG][SIB_DISPLACEMENT];
	// 		}
	// 		$unit[OPERAND][$mem_id] = $mem_str;
	// 		return true;
	// 	}
	// 	return false;		
	// }
	// translate imm -> dec
	private static function translateImmDec(&$unit){
		if (isset($unit[P_TYPE])){
			$imm_id = array_search(T_IMM, $unit[P_TYPE]);
			if ($imm_id){
				$bits = $unit[P_BITS][$imm_id];
				$neg = false;
				$str = $unit[OPERAND][$imm_id];
				if ('-' == $str[0]){
					$neg = true;
					$str = substr($str,1,strlen($str)-1);
				}elseif ('+' == $str[0]){
					$str = substr($str,1,strlen($str)-1);
				}
				if (false === ($v = Instruction::hexImm2decImm($str,$neg,$bits))){
					return false;					
				}else{
					$unit[OPERAND][$imm_id] = $v;
				}				
			}
		}
		return true;
	}
	//
	private static function setEipRecord($unit){		
		if (Instruction::isEipInst($unit[INST])){
			if (T_IMM === $unit[P_TYPE][self::LABEL_POS]){
				if (!isset($unit[IMM_IS_RELOC])){
					return $unit[OPERAND][self::LABEL_POS];
				}
			}
		}
		return false;
	}
	//
	private static function resort($repo,$label_record,$last_label_record){
		$mapped_record = array();
		$jump_record = array();
		$i = 1;		
		foreach ($repo as $line => $value){
			if (isset($label_record[$line])){
				foreach ($label_record[$line] as $src){					
					self::$_repo[$i][LABEL_FROM] = $src;
					$jump_record[$src] = $i;
					$i ++;
				}
			}
			self::$_repo[$i] = $value;
			$mapped_record[$line] = $i;
			$i++;
		}
		if (!empty($last_label_record)){
			foreach ($last_label_record as $src){
				self::$_repo[$i][LABEL_FROM] = $src;
				$jump_record[$src] = $i;
				$i++;
			}
		}
		foreach ($jump_record as $src => $dst){
			$src = $mapped_record[$src];
			self::$_repo[$src][IMM_IS_LABEL] = $dst;
			self::$_repo[$dst][LABEL_FROM] = $src;
		}
	}
	// 
	public static function start($sec,$code,$reloc){
		self::flush();
		$ret = true;
		$label_record = array(); // 记录跳转 [dst][] = src
		$tmp_repo = array();
		$last_label_record = array(); // 记录跳转末尾 [] = src
		$last_unit = end($code);$last_line = key($code);
		$end_line = $last_line + $last_unit['len'];
		foreach ($code as $i => $a){
			// prefix parser
			$c_prefix = self::getPrefix($a['bin']);			
			// code parser
			$c_array  = self::AsmParser($a['asm'],$c_prefix);
			if (false === $c_array){
				GeneralFunc::LogInsert('fail to parse asm code: '.$a['asm'],WARNING);
				return false;//$ret = false;
			}
			if (!isset($c_array[INST])){
				GeneralFunc::LogInsert('no legal inst found in line: '.$a['asm'],WARNING);
				return false;//$ret = false;
			}
			// mem addr parser
			$c_mem = self::memAddrParser($c_array);
			if (false === $c_mem){
				GeneralFunc::LogInsert('fail to parse mem structure: '.$a['asm'],WARNING);
				return false;//$ret = false;
			}
			if (!empty($c_mem)){				
				$c_array[P_M_REG] = $c_mem;
			}
			// imm hex -> dec
			if (!self::translateImmDec($c_array)){
				GeneralFunc::LogInsert('fail to translateImmDec(): '.$a['asm'],WARNING);
				return false;//$ret = false;
			}
			// reloc parser			
			if (isset($reloc[$i])){
				if (!self::relocReplace($sec,$c_array,$reloc[$i],$a['bin'])){
					GeneralFunc::LogInsert('fail to relocReplace(): '.$a['asm'],WARNING);
					return false;//$ret = false;
				}
			}
			// refactor mem addr by P_M_REG
			// if (isset($c_array[P_M_REG])){
			// 	if (!self::refactorMemAddr($c_array)){
			// 		GeneralFunc::LogInsert('fail to refactorMemAddr(): '.$a['asm'],WARNING);
			// 		return false;//$ret = false;
			// 	}
			// }
			// Label record
			if (false !== ($label_id = self::setEipRecord($c_array))){
				if (isset($code[$label_id])){
					$label_record[$label_id][] = $i;
					$c_array[IMM_IS_LABEL] = $label_id;
				}elseif ($label_id == $end_line){ // jmp to end					
					$last_label_record[] = $i;
					$c_array[IMM_IS_LABEL] = $label_id;
				}else{
					GeneralFunc::LogInsert('Control transfer over current segment, line '.$i.' to line '.$label_id,WARNING);
					return false;
				}
			}
			// else{
			// 	GeneralFunc::LogInsert('parse jump structure, line: '.$i,WARNING);
			// }

			$c_array[ORIGINAL_LEN] = strlen($a['bin'])/2;
			
			$tmp_repo[$i] = $c_array;
		}
		
		// resort units and insert Label position
		self::resort($tmp_repo,$label_record,$last_label_record);

		return self::$_repo;
	}

}