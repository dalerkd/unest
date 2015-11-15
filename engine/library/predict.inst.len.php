<?php
class PredictInstLen{
	private static $_insns_array;
	private static $_regs_array;

	public static function init(){
		require dirname(__FILE__)."/../instructions/insns.dat.php";
		self::$_insns_array = $inst_dat_array;
		self::$_regs_array  = $regs_dat_array;
	}
	// vec_range,$reverse_range 指令跨度,仅eip跳转指令有效
	public static function predictInstLength(&$unit_inst,$p_m_reg,$vec_range,$reverse_range,&$extend){
		$ret = false;
		$oplen = array();
		$operand_num = 0;
		if (isset($unit_inst[LABEL_FROM])){ // label
			return array();
		}
		if (isset($unit_inst[INST])){ // inst alias
			$tmp = Instruction::getMatchCC($unit_inst[INST]);
			if ($tmp){
				$unit_inst[INST] = $tmp;
			}
		}

		$oplen[GOS_PREFIX] = (isset($unit_inst[PREFIX]))?count($unit_inst[PREFIX]):0;
		
		$a_resize = isset($unit_inst[PREFIX]['a_resize'])?true:false;
		$o_resize = isset($unit_inst[PREFIX]['o_resize'])?true:false;

		if (isset($p_m_reg[SIB_INDEX])){ // sib
			$oplen[GOS_SIB] = 1;
		}else{
			if (isset($p_m_reg[SIB_BASE])){
				 if (Instruction::isStackReg($p_m_reg[SIB_BASE])){
				 	$oplen[GOS_SIB] = 1;
				 }
			}
		}
		if (isset($p_m_reg[SIB_DISPLACEMENT])){ // displacement		
			if (isset($p_m_reg[DISPLACEMENT_IS_RELOC])){ // displacement is reloc
				$oplen[GOS_DISPLACEMENT] = OPT_BITS/8;
			}elseif ($a_resize){
				$oplen[GOS_DISPLACEMENT] = Instruction::getCurrentBits($a_resize)/8;
			}elseif ((!isset($p_m_reg[SIB_BASE])) and (!isset($p_m_reg[SIB_INDEX]))){
				$oplen[GOS_DISPLACEMENT] = Instruction::getCurrentBits($a_resize)/8;
			}elseif ((isset($p_m_reg[SIB_BASE])) and 
				(Instruction::isSByte($p_m_reg[SIB_DISPLACEMENT],OPT_BITS))){
				// 基址存在的情况下 displacement 才有可能被写为1byte
				$oplen[GOS_DISPLACEMENT] = 1;
			}else{
				$oplen[GOS_DISPLACEMENT] = Instruction::getCurrentBits($a_resize)/8;
			}
		}
		if (isset($unit_inst[OPERAND])){
			$operand_num = count($unit_inst[OPERAND]);
		}
		if (isset(self::$_insns_array[$unit_inst[INST]][$operand_num])){
			if (0 == $operand_num){
				if (isset(self::$_insns_array[$unit_inst[INST]][$operand_num][0][OPLEN])){
					$oplen[GOS_OPCODE] = self::$_insns_array[$unit_inst[INST]][$operand_num][0][OPLEN];
					$ret = true;
				}
			}else{
				$obj = self::$_insns_array[$unit_inst[INST]][$operand_num];
				// filter a??/o?? limited
				if ($a_resize){$obj = self::filter_A_O_limit($obj,A_PREFIX);}
				if ($o_resize){$obj = self::filter_A_O_limit($obj,O_PREFIX);}
				
				$operand_possible_array = self::getOperandsPossibleValue($unit_inst,$p_m_reg,$vec_range,$reverse_range);
				// var_dump($operand_possible_array);
				if ($matched_set = self::matchSameOperands($obj,$operand_possible_array)){
					if (isset($matched_set[O_PREFIX])){
						$extend[O_SIZE_ATTR] = true;
					}
					if (isset($matched_set[OPLEN])){
						$oplen[GOS_OPCODE] = $matched_set[OPLEN];
					}
					if (isset($matched_set[IMM_BIT])){
						foreach ($matched_set[IMM_BIT] as $a){
							if ($a > 0){
								if (!isset($oplen[GOS_IMM])){$oplen[GOS_IMM] = 0;}
								$oplen[GOS_IMM] += $a;
							}
						}						
					}
					if (isset($matched_set[MODRM])){
						$oplen[GOS_ADDRMODE] = 1;
					}
					if (isset($matched_set[RELATIVE_ADDR])){
						if ($matched_set[RELATIVE_ADDR]){
							$oplen[GOS_REL] = $matched_set[RELATIVE_ADDR]/8;
						}else{
							$oplen[GOS_REL] = Instruction::getCurrentBits($o_resize)/8;
						}						
					}
					$ret = true;
				}
			}			
		}									
		if ($ret){
			return $oplen;
		}		
		return false;
	}
	//
	private static function filter_A_O_limit($obj,$type){
		$ret = array();
 		$filter = array(); 
		$c_bits = Instruction::getCurrentBits(true);
		$filter[] = $c_bits;
		
		foreach ($obj as $z){
			if ((!isset($z[$type])) or (in_array($z[$type], $filter))){
				$ret[] = $z;
			}
		}
		return $ret;
	}
	//
	private static function matchSameOperands($obj,$operand_possible_array){
		$final_result = false;
		$max_accu = -1;
		foreach ($obj as $c_obj){
			$result = $c_obj;
			$total_accu = 0;
			foreach ($operand_possible_array as $o_id => $o_array){
				if (!isset($o_array[$c_obj[$o_id][OPERAND_TYPE]])){
					$result = false;
					break;
				}else{
					$total_accu += $o_array[$c_obj[$o_id][OPERAND_TYPE]];
				}
			}
			if (false !== $result){
				if ($total_accu > $max_accu){
					$max_accu = $total_accu; 
					$final_result = $result;
				}
			}
		}
		return $final_result;
	}
	// ($ret['describ'] = 'accurate level [0,1,2]');
	private static function getOperandsPossibleValue($unit_inst,$p_m_reg,$vec_range,$reverse_range){
		$ret = array();
		foreach ($unit_inst[OPERAND] as $o_id => $o_contents){
			$c_type = $unit_inst[P_TYPE][$o_id];
			$c_bits = $unit_inst[P_BITS][$o_id];
			
			if (T_IMM === $c_type){
				$ret[$o_id]['imm'] = 0;
				$ret[$o_id]['imm'.$c_bits] = 1;
				// if (isset($unit_inst[IMM_IS_LABEL])){				
				if (isset($unit_inst[EIP_INST])){
					$ret[$o_id]['imm|far']             = 0;
					$ret[$o_id]['imm|near']            = 0;
					$ret[$o_id]['imm'.$c_bits.'|far']  = 1;
					$ret[$o_id]['imm'.$c_bits.'|near'] = 1;
				}
				if (!isset($unit_inst[IMM_IS_RELOC])){ // imm is reloc					
					if (isset($unit_inst[IMM_IS_LABEL])){
						if (($vec_range <= 127)or(($reverse_range) and (128 == $vec_range))){
							$ret[$o_id]['imm|short'] = 2;
						}
					}else{
						if (1 == $o_contents){
							$ret[$o_id]['unity'] = 2;
						}
						if (Instruction::isSByte($o_contents,$c_bits)){						
							if (32 === $c_bits){
								$ret[$o_id]['imm8']   = 0;
								$ret[$o_id]['sbytedword']   = 2;
								$ret[$o_id]['sbytedword32'] = 2;
							}elseif (16 === $c_bits){
								$ret[$o_id]['imm8']   = 0;
								$ret[$o_id]['sbyteword']   = 2;
								$ret[$o_id]['sbyteword16'] = 2;
							}
						}
					}
				}
			}elseif (T_MEM === $c_type){
				if ((!isset($p_m_reg[SIB_BASE])) and (!isset($p_m_reg[SIB_INDEX]))){
					$ret[$o_id]['mem_offs'] = 2;
				}
				$ret[$o_id]['mem'] = 0;
				$ret[$o_id]['mem|far'] = 0;
				$ret[$o_id]['mem|near'] = 0;
				$ret[$o_id]['mem'.$c_bits] = 1;
				$ret[$o_id]['mem'.$c_bits.'|far'] = 1;
				$ret[$o_id]['rm'] = 0;
				$ret[$o_id]['rm'.$c_bits] = 1;
				$ret[$o_id]['rm'.$c_bits.'|near'] = 1; 
			}elseif (T_GPR === $c_type){
				$o_contents = strtolower($o_contents);
				$ret[$o_id]['reg'.$c_bits] = 1;
				$ret[$o_id]['reg_'.$o_contents] = 1;
				if (32 == $c_bits){
					if ('eax' != $o_contents){
						$ret[$o_id]['reg32na'] = 1;
					}
				}
				$ret[$o_id]['rm'] = 0;
				$ret[$o_id]['rm'.$c_bits] = 1;
				$ret[$o_id]['rm'.$c_bits.'|near'] = 1;
			}elseif (T_ORS === $c_type){
				if (isset(self::$_regs_array[$o_contents])){
					foreach (self::$_regs_array[$o_contents] as $a => $true){
						$ret[$o_id][$a] = 0;
					}
				}
			}
			if (isset($unit_inst[ATTACH_TAG][$o_id])){
				foreach ($ret[$o_id] as $a => $b){
					$a .= '|'.strtolower($unit_inst[ATTACH_TAG][$o_id]);
					$ret[$o_id][$a] = $b+1;
				}
			}
		}
		return $ret;
	}
}