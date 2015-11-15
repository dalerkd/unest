<?php

require dirname(__FILE__)."/../trait/organ.physic.trait.php";
require dirname(__FILE__)."/../trait/organ.logic.trait.php";

// organ base class
class OrgansOperator{
	use tOrganPhysic,tOrganLogic;
}

// Organ operation(insert/remove) wrapper (not thread safe)
// readme.units.structure.txt
class OrganOptWrapper extends OrgansOperator{

	// use tOrganPhysic,tOrganLogic;

	private static $_pseudo_id_map;    // [pseudo id] => dlist id
	private static $_pseudo_jmp_array; // [pseudo src id] => pseudo dst id
	private static $_removed_units;	
	private static $_sec;
	private static $_save4physic;
	private static $_save4logic;
	private static $_job_fail_flag;

	//
	public static function create($sec){
		self::$_sec = $sec;
		parent::Physic_create();
		parent::Logics_create();
	}
	//
	private static function flush(){
		self::$_pseudo_id_map = array();
		self::$_pseudo_jmp_array = array();
		self::$_removed_units = array();
	}
	// 
	public static function job_begin(){
		self::flush();
		self::organ_save();
		self::$_job_fail_flag = false; // give up current job!
	}	
	//
	private static function job_roll_back(){
		self::organ_restore();
	}
	// 
	public static function job_commit(){		
		$ret_arr = array(self::$_pseudo_id_map,self::$_removed_units);
		if ((empty(self::$_pseudo_id_map)) and (empty(self::$_removed_units))){
			return $ret_arr;
		}
		do{
			if (self::$_job_fail_flag){
				break;
			}
			// reloc all jmp units
			foreach (self::$_pseudo_jmp_array as $src => $dst){
				$a = parent::Physic_relocJmpVector(self::$_pseudo_id_map[$src],self::$_pseudo_id_map[$dst],1);
				$b = parent::Physic_relocJmpVector(self::$_pseudo_id_map[$dst],self::$_pseudo_id_map[$src],2);
				if ((!$a)or(!$b)){
					GeneralFunc::LogInsert('fail to Physic_relocJmpVector()',WARNING);
					break;
				}
			}
			// get all new unit's length
			foreach (self::$_pseudo_id_map as $id){parent::getLen($id);}
			// reset all effected RelJmp inst
			echo '<br>parent::resetRelJmpExpired() start...';
			if (!parent::resetRelJmpExpired()){
				break;
			}
			// check length by original length (only valid in ready process)		
			foreach (self::$_pseudo_id_map as $id){
				parent::Physic_recheck_len($id,self::$_sec);
			}
			echo '<br>parent::resetRelJmpRangeExpired() start...';
			// reset all range be modified RelJmp inst len
			if (true !== parent::resetRelJmpRangeExpired()){
				break;
			}		
			// logic structure
			$done = array();
			foreach (self::$_pseudo_id_map as $id){
				$prev = parent::prev($id);
				$next = parent::next($id);
				if (!isset($done[$prev][$id])){
					parent::Logics_insert($prev,$id);
					$done[$prev][$id] = true;
				}
				if (!isset($done[$id][$next])){
					parent::Logics_insert($id,$next);
					$done[$id][$next] = true;
				}
			}
			// all effects of insertable position
			echo '<br>######### step 1';
			parent::Logics_gen_usable_forbid_pos(self::$_pseudo_id_map,1,false); // gpr&eflags
			echo '<br>######### step 2';
			parent::Logics_gen_usable_forbid_pos(self::$_pseudo_id_map,2,false); // mem
			echo '<br>######### step 3';
			parent::Logics_gen_usable_forbid_pos(self::$_pseudo_id_map,3,true);  // mem reverse readable
			echo '<br>######### step 4';
			parent::Logics_gen_usable_forbid_pos(self::$_pseudo_id_map,4,false); // stack			
			echo '<br>######### step 5';
			parent::Logics_gen_usable_forbid_pos(self::$_pseudo_id_map,5,true);  // stack reverse
			return $ret_arr;
		}while (false);
		// roll back
		self::job_roll_back();
		return false;	
	}
	// insert organ
	// param $organ  : array (readme.array.txt $_unit_inst) 
	// param $pos    : insert in next of $pos' (first unit defined as false)
	// param $psid   : pseudo id is just for jcc/label re-adjust
	// param $comment: array( 0 => 'current comment', 1 => heritance id)
	public static function organ_insert($organ,$pos,$psid,$comment){
		if (parent::isVirtUnit($pos)){
			self::$_job_fail_flag = true;
			GeneralFunc::LogInsert('virtual unit cant be used as pos in organ_insert()',WARNING);
		}
		if (!self::organ_fix($organ)){
			self::$_job_fail_flag = true;
			GeneralFunc::LogInsert('unsupported exception: organ_fix() return fail!',WARNING);
		} 
		if (isset($organ[IMM_IS_LABEL])){
			self::$_pseudo_jmp_array[$psid] = $organ[IMM_IS_LABEL];
			$organ[IMM_IS_LABEL] = 0;
		}elseif (isset($organ[LABEL_FROM])){
			self::$_pseudo_jmp_array[$organ[LABEL_FROM]] = $psid;
			$organ[LABEL_FROM] = 0;
		}
		if ($c_id = parent::Physic_Insert($organ,$pos,$comment)){
			self::$_pseudo_id_map[$psid] = $c_id;
		}			
		return $c_id;
	}
	// remove unit
	public static function organ_remove($id){
		parent::removeDLink($id);
		self::$_removed_units[] = $id;
		// parent::Logics_remove($id); // not remove it from logic links
	}
	//
	public static function organ_export(){
		self::organ_save();
		return array(self::$_save4physic,self::$_save4logic);
	}
	//
	public static function organ_import($sec,$src){
		self::$_sec = $sec;
		self::$_save4physic = $src[0];
		self::$_save4logic = $src[1];
		self::organ_restore();
	}
	//
	private static function organ_save(){
		self::$_save4physic = parent::Physic_export();
		self::$_save4logic  = parent::Logics_export();		
	}
	//
	private static function organ_restore(){
		parent::Physic_import(self::$_save4physic);
		parent::Logics_import(self::$_save4logic);
	}
	// param01.output as asm code Function
	// param02.reloc value setup Function
	public static function asm_output($outputFunc,$relocParserFunc){
		$c_unit = parent::getBeginUnit();
		// $outputFunc('db 0cch'); // insert int03 in seg's header
		while ($c_unit){
			if (!parent::isVirtUnit($c_unit)){
				$c_asm = self::organ2asm_parser($c_unit,$relocParserFunc);
				if (false === $c_asm){break;}
				if (TRACK_COMMENT_ON){
					$c_asm .= ';'.parent::getComment($c_unit);
				}
				$outputFunc($c_asm);
			}
			$c_unit = parent::next($c_unit); 
		}
	}
	//
	private static function organ2asm_parser($id,$relocParserFunc){
		$ret = '';
		$organ_array = parent::getCode($id);
		// label
		if (isset($organ_array[LABEL_FROM])){
			$ret .= 'LABEL_'.self::$_sec.'_'.$id.'_from_'.$organ_array[LABEL_FROM].':';
		}else{
			// prefix
			if (!empty($organ_array[PREFIX])){				
				if ($a = array_search(PREFIX_GROUP_3, $organ_array[PREFIX])){					
					$ret .= 'o';
					$ret .= Instruction::getCurrentBits(true);
					$ret .= ' ';	
				}
				if ($a = array_search(PREFIX_GROUP_4, $organ_array[PREFIX])){
					$ret .= 'a';
					$ret .= Instruction::getCurrentBits(true);
					$ret .= ' ';	
				}
				if ($a = array_search(PREFIX_GROUP_1, $organ_array[PREFIX])){
					$ret .= $a.' ';
				}
				if ($a = array_search(PREFIX_GROUP_0, $organ_array[PREFIX])){
					$ret .= $a.' ';
				}
			}
			// inst
			$implicit_bits_limit = false; // 隐含 位数 限制
			if (isset($organ_array[INST])){
				$ret .= $organ_array[INST].' ';
				if ('LEA' == $organ_array[INST]){
					$implicit_bits_limit = true;
				}
			}
			// 
			if (isset($organ_array[IMM_IS_LABEL])){
				$ret .= 'LABEL_'.self::$_sec.'_'.$organ_array[IMM_IS_LABEL].'_from_'.$id;
			}else{
				// params
				if (isset($organ_array[OPERAND])){					
					foreach ($organ_array[OPERAND] as $i => $value){
						if (T_GPR === $organ_array[P_TYPE][$i]){
							$ret .= $value;
						}elseif (T_MEM === $organ_array[P_TYPE][$i]){							
							if (!$implicit_bits_limit){						
								switch ($organ_array[P_BITS][$i]){
									case 64:
									$ret .= 'QWORD ';
									break;
									case 32:
									if (isset($organ_array[O_SIZE_ATTR])){
										$ret .= 'DWORD ';
									}
									break;
									case 16:
									if (isset($organ_array[O_SIZE_ATTR])){
										$ret .= 'WORD ';
									}
									break;
									case 8:
									$ret .= 'BYTE ';
									break;
								}
							}
							$ret .= '[';							
							
							if (isset(parent::$_mem_operand_map[$value])){
								$pm = parent::$_mem_operand_map[$value];
								
								if (isset($pm[SEG_PREFIX])){
									$ret .= $pm[SEG_PREFIX].':';
								}
								if (isset($pm[SIB_BASE])){
									$ret .= $pm[SIB_BASE].'+';
								}
								if (isset($pm[SIB_INDEX])){
									$ret .= $pm[SIB_INDEX];
									if (isset($pm[SIB_SCALE])){
										$ret .= '*'.$pm[SIB_SCALE];
									}
									$ret .= '+';
								}
								if (isset($pm[SIB_DISPLACEMENT])){
									$tmpValue = $pm[SIB_DISPLACEMENT];
									if (isset($pm[DISPLACEMENT_IS_RELOC])){
									    $tmpValue = $relocParserFunc(2,parent::$_reloc_map[$pm[DISPLACEMENT_IS_RELOC]],$tmpValue);
									}
									if (false === $tmpValue){
										GeneralFunc::LogInsert('fail to return reloc value(displacement),sec: '.self::$_sec.' id: '.$id);
										return false;
									}else{
										$ret .= $tmpValue;
									}
								}else{
									$ret .= '0';
								}
							}
							$ret .= ']';
						}elseif (T_IMM === $organ_array[P_TYPE][$i]){
							if (isset($organ_array[IMM_IS_RELOC])){
								$tmpValue = $relocParserFunc(1,parent::$_reloc_map[$organ_array[IMM_IS_RELOC]],$value);
							}else{
								$tmpValue = $value;
							}
							if (false === $tmpValue){
								GeneralFunc::LogInsert('fail to return reloc value(imm),sec: '.self::$_sec.' id: '.$id);
								return false;
							}else{
								$ret .= $tmpValue;
							}
						}else{
							$ret .= $value;
						}
						$ret .= ',';
					}
					$ret = substr($ret,0,strlen($ret) - 1);
				}
			}			
		}
		return $ret;
	}
	// organ fix
	private static function organ_fix(&$organ){
		if (isset($organ[INST])){
			if ($a = Instruction::isJmp($organ[INST])){
				$organ[EIP_INST] = $a;
			}
		}
		if (isset($organ[VIRT_UNIT])){ // virtual unit
			$organ[INST] = 'NOP';
			$organ[ORIGINAL_LEN] = 0;
		}
		if (isset($organ[P_M_REG])){ // ready step only | new mem operands
			$mem_operand_id = array_search(T_MEM, $organ[P_TYPE]);
			if (false === $mem_operand_id){return false;}
			if (isset($organ[DISP_IS_RELOC])){				
				$organ[P_M_REG][DISPLACEMENT_IS_RELOC] = parent::Physic_RelocMap_add($organ[DISP_IS_RELOC]);
				unset ($organ[DISP_IS_RELOC]);
			}
			$mem_idx = parent::Physic_replaceMem($organ);
			$organ[OPERAND][$mem_operand_id] = $mem_idx;
			unset($organ[P_M_REG]);
		}
		if (isset($organ[IMM_IS_RELOC])){
			if (is_array($organ[IMM_IS_RELOC])){
				$organ[IMM_IS_RELOC] = parent::Physic_RelocMap_add($organ[IMM_IS_RELOC]);
			}
		}
		if (!isset($organ[OPERAND])){return true;}
		self::fix_operands_idx($organ);
		foreach ($organ[OPERAND] as $i => $c_operand){			
			if (isset($organ[P_TYPE][$i])){
				if (T_MEM === $organ[P_TYPE][$i]){					
					$j = parent::getMemOperandArr($c_operand);
					if (false === $j){return false;}
					if ($j[ADDR_BITS] === Instruction::getCurrentBits(true)){
						$r = Instruction::getPrefixNameByHex('67');
						$organ[PREFIX][$r[0]] = $r[1];							
					}
					if (!isset($organ[P_BITS][$i])){
						$organ[P_BITS][$i] = $j[OP_BITS];
					}
				}
				if (!isset($organ[P_BITS][$i])){
					if (T_IMM === $organ[P_TYPE][$i]){
						$organ[P_BITS][$i] = OPT_BITS;
					}elseif (T_GPR === $organ[P_TYPE][$i]){
						$j = Instruction::getGeneralRegBits($c_operand);
						if (false === $j){return false;}
						$organ[P_BITS][$i] = $j;
					}else{
						return false;
					}
				}
				if (T_IMM === $organ[P_TYPE][$i]){ // calculat T_IMM's value
					$organ[OPERAND][$i] = Instruction::my_calculate($organ[OPERAND][$i],$organ[P_BITS][$i]);
				}
			}else{
				return false;
			}
		}
		if (isset($organ[OPERAND][1])){
			if (isset($organ[P_BITS][1])){
				if ($organ[P_BITS][1] === Instruction::getCurrentBits(true)){
					$r = Instruction::getPrefixNameByHex('66');
					$organ[PREFIX][$r[0]] = $r[1];
				}
			}
		}
		return true;
	}
	// operands' id base 0 => base 1
	private static function fix_operands_idx(&$organ){
		if (isset($organ[OPERAND][0])){
			$old = $organ;
			unset($organ[OPERAND]);
			unset($organ[P_TYPE]);
			unset($organ[P_BITS]);
			foreach ($old[OPERAND] as $id => $value){
				$organ[OPERAND][$id+1] = $value;
				if (isset($old[P_TYPE][$id])){
					$organ[P_TYPE][$id+1] = $old[P_TYPE][$id];
				}
				if (isset($old[P_BITS][$id])){
					$organ[P_BITS][$id+1] = $old[P_BITS][$id];
				}				
			}			
		}
	}
	// show
	public static function show(){
		$c = parent::getBeginUnit();
		echo '<table border=1>';
		echo '<tr><td></td><td bgcolor=yellow>GPR writable</td><td bgcolor=pink>GPR forbid</td><td bgcolor=yellow>EFLAGS writable</td><td bgcolor=pink>EFLAGS forbid</td><td bgcolor=#66FFFF>mem readable</td><td bgcolor=#66EEFF>mem writable</td><td bgcolor=#66DDFF>mem lastwrite</td><td bgcolor=#66CCFF>mem readable reverse</td><td bgcolor=yellow>stack access</td><td bgcolor=yellow>stack (reverse)</td></tr>';		
		echo '<tr><td>No.</td><td>inst</td><td>len</td><td>GPR effects</td><td>EFLAGS effects</td><td>Mem effects</td><td>Exec path</td><td>RelJmpArray</td><td>RelJmpEffect(<font color=blue>Prev</font> <font color=red>Next</font>)</td><td>stack access</td><td>sp writes</td></tr>';		
		while ($c){
			// logic
			echo '<tr>';
			echo '<td></td>';
			echo '<td bgcolor=yellow>';
			if (isset(parent::$_gpr_write_able[$c])){
				var_dump(parent::$_gpr_write_able[$c]);
			}			
			echo '</td>';
			echo '<td bgcolor=pink>';
			if (isset(parent::$_gpr_write_forbid[$c])){
				var_dump(parent::$_gpr_write_forbid[$c]);
			}			
			echo '</td>';
			echo '<td bgcolor=yellow>';
			if (isset(parent::$_eflags_write_able[$c])){
				var_dump(parent::$_eflags_write_able[$c]);
			}			
			echo '</td>';
			echo '<td bgcolor=pink>';
			if (isset(parent::$_eflags_write_forbid[$c])){
				var_dump(parent::$_eflags_write_forbid[$c]);
			}			
			echo '</td>';

			echo '</td>';			
			echo '<td bgcolor=#66FFFF>';
			if (isset(parent::$_mem_read_able[$c])){
				var_dump(parent::$_mem_read_able[$c]);
			}			
			echo '</td>';
			echo '<td bgcolor=#66EEFF>';
			if (isset(parent::$_mem_writ_able[$c])){
				var_dump(parent::$_mem_writ_able[$c]);
			}			
			echo '</td>';
			echo '<td bgcolor=#66DDFF>';
			if (isset(parent::$_mem_nearest_writ_able[$c])){
				var_dump(parent::$_mem_nearest_writ_able[$c]);
			}					
			echo '</td>';
			echo '<td bgcolor=#66CCFF>';
			if (isset(parent::$_mem_read_able_reverse[$c])){
				var_dump(parent::$_mem_read_able_reverse[$c]);
			}						
			echo '</td>';
			echo '<td ';
			if (isset(parent::$_stack_usable[$c])){
				if (parent::$_stack_usable[$c]){
					echo 'bgcolor=yellow>'.'True';
				}else{
					echo 'bgcolor=pink>'.'FALSE';
				}
			}else{
				echo '>';
			}		
			echo '</td>';
			echo '<td ';
			if (isset(parent::$_stack_usable_reverse[$c])){
				if (parent::$_stack_usable_reverse[$c]){
					echo 'bgcolor=yellow>'.'True';
				}else{
					echo 'bgcolor=pink>'.'FALSE';
				}
			}else{
				echo '>';
			}		
			echo '</td>';
			echo '</tr>';			
			// physic
			if (parent::isVirtUnit($c)){
				echo '<tr bgcolor = gray>';
			}else{
				echo '<tr>';
			}
			echo '<td>'."$c".'</td>';
			$c_inst   = parent::getCode($c);
			echo '<td>';
			echo 'Comment: ';
			echo '[<font color = blue>';
			echo parent::getComment($c);
			echo '</font>]';
			echo '<br>';
			var_dump ($c_inst);
			if (isset($c_inst[IMM_IS_RELOC])){
				echo '-- imm reloc --';
				var_dump(parent::$_reloc_map[$c_inst[IMM_IS_RELOC]]);
			}
			if ($c === parent::$_public_last_unit_id){
				echo '<font color=pink><b>PUBLIC LAST UNIT</b></font>';
			}
			echo '</td>';			
			$c_len = parent::getLen($c);
			echo '<td>'.$c_len.'</td>';
			echo '<td>';
			if (isset(parent::$_GPR_effects[$c])){
				var_dump (parent::$_GPR_effects[$c]);
			}
			echo '</td>';
			echo '<td>';
			if (isset(parent::$_EFLAGS_effects[$c])){
				var_dump(parent::$_EFLAGS_effects[$c]);
			}
			echo '</td>';
			echo '<td>';
			if (isset(parent::$_MEM_effects[$c])){
				var_dump(parent::$_MEM_effects[$c]);
				foreach (parent::$_MEM_effects[$c] as $mid => $c_opt){					
					echo '---------- '.$mid.' -------';
					var_dump(parent::$_mem_operand_map[$mid]);
					if (isset(parent::$_mem_operand_map[$mid][DISPLACEMENT_IS_RELOC])){
						echo '-- displacement reloc --';
						var_dump(parent::$_reloc_map[parent::$_mem_operand_map[$mid][DISPLACEMENT_IS_RELOC]]);
					}
				}
			}
			echo '</td>';
			echo '<td>';
			if (isset(parent::$_units_digraph[$c])){
				var_dump(parent::$_units_digraph[$c]);
			}
			echo '</td>';
			echo '<td>';
			if (isset(parent::$_rel_jmp_dst[$c])){
				echo '<br>$_rel_jmp_dst: ';
				if (false === parent::$_rel_jmp_dst[$c]){
					echo '<font color=red><b>FALSE</b></font>';
				}else{
					echo parent::$_rel_jmp_dst[$c];
				}
				echo '<br>$_rel_jmp_range: '.parent::$_rel_jmp_range[$c];
				echo '<br>$_rel_jmp_max: ';
				if (isset(parent::$_rel_jmp_max[$c])){
					echo parent::$_rel_jmp_max[$c];
				}else{
					echo 'Null';
				}
				echo '<br>$_rel_jmp_units: ';
				var_dump (parent::$_rel_jmp_units[$c]);
			}else{
				echo '-';
			}
			echo '</td>';
			echo '<td>';
			if (isset(parent::$_rel_jmp_pos_effects[$c][P])){
				echo '<font color=blue>Prev:';
				var_dump (parent::$_rel_jmp_pos_effects[$c][P]);
				echo '</font>';
			}
			if (isset(parent::$_rel_jmp_pos_effects[$c][N])){
				echo '<font color=red>Next:';
				var_dump (parent::$_rel_jmp_pos_effects[$c][N]);
				echo '</font>';
			}
			echo '</td>';
			echo '<td>';
			if (isset(parent::$_STACK_effects[$c])){
				var_dump(parent::$_STACK_effects[$c]);
			}
			echo '</td>';
			echo '<td>';
			if (isset(parent::$_SP_writes[$c])){
				if (parent::$_SP_writes[$c]){
					echo 'SP writes';
				}
			}
			echo '</td>';
			echo '</tr>';		
			$c = parent::next($c);
		}
		echo '</table>';
	}
}