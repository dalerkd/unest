<?php

trait tOrganLogic{
	private static $_usable_forbid_wait_list;
	protected static $_units_digraph;

	protected static $_gpr_write_able;
	protected static $_gpr_write_forbid;

	protected static $_eflags_write_able;
	protected static $_eflags_write_forbid;

	protected static $_mem_writ_able;
	protected static $_mem_read_able;
	protected static $_mem_nearest_writ_able;
	protected static $_mem_read_able_reverse;

	protected static $_stack_usable;
	protected static $_stack_usable_reverse;

	protected static $_public_last_unit_id;

	//
	private static function init_zero_unit(){
		self::$_gpr_write_able[0] = array();
		self::$_gpr_write_forbid[0] = array();
		self::$_eflags_write_able[0] = array();
		self::$_eflags_write_forbid[0] = array();
		self::$_mem_writ_able[0] = array();
		self::$_mem_read_able[0] = array();
		self::$_mem_nearest_writ_able[0] = array();	
		self::$_mem_read_able_reverse[0] = array();	
	}
	//
	private static function flush(){
		self::$_units_digraph = array();
		self::$_usable_forbid_wait_list = array();
		self::$_gpr_write_able = array();
		self::$_gpr_write_forbid = array();
		self::$_eflags_write_able = array();
		self::$_eflags_write_forbid = array();
		self::$_mem_writ_able = array();
		self::$_mem_read_able = array();
		self::$_mem_nearest_writ_able = array();
		self::$_mem_read_able_reverse = array();
		self::$_stack_usable  = array();	
		self::$_stack_usable_reverse = array();
		self::$_public_last_unit_id = false;
	}
	//
	protected static function Logics_export(){
		return array(
			self::$_units_digraph,
			self::$_gpr_write_able,
			self::$_gpr_write_forbid,
			self::$_eflags_write_able,
			self::$_eflags_write_forbid,
			self::$_mem_writ_able,
			self::$_mem_read_able,
			self::$_mem_nearest_writ_able,
			self::$_mem_read_able_reverse,
			self::$_stack_usable,
			self::$_stack_usable_reverse,
			self::$_public_last_unit_id,
			);
	}
	protected static function Logics_import($src){
		self::flush();
		self::$_units_digraph         = $src[0];
		self::$_gpr_write_able        = $src[1];
		self::$_gpr_write_forbid      = $src[2];
		self::$_eflags_write_able     = $src[3];
		self::$_eflags_write_forbid	  = $src[4];
		self::$_mem_writ_able         = $src[5];
		self::$_mem_read_able         = $src[6];
		self::$_mem_nearest_writ_able = $src[7];
		self::$_mem_read_able_reverse = $src[8];
		self::$_stack_usable          = $src[9];
		self::$_stack_usable_reverse  = $src[10];
		self::$_public_last_unit_id   = $src[11];
	}
	//
	protected static function Logics_create(){
		self::flush();
		self::init_zero_unit();
	}
	//
	protected static function Logics_insert($phyPrevId,$phyNextId){

		if ($phyPrevId){
			if (isset(self::$_units_digraph[$phyPrevId][N][0])){unset(self::$_units_digraph[$phyPrevId][N][0]);}
		}
		if ($phyNextId){
			if (isset(self::$_units_digraph[$phyNextId][P][0])){unset(self::$_units_digraph[$phyNextId][P][0]);}
		}
		
		if ($phyPrevId){
			$c_inst = self::getCode($phyPrevId);			
			
			if (isset($c_inst[IMM_IS_LABEL])){
				self::$_units_digraph[$phyPrevId][N][1] = $c_inst[IMM_IS_LABEL];
				self::$_units_digraph[$c_inst[IMM_IS_LABEL]][P][1] = $phyPrevId;
			}
			if ((!isset($c_inst[EIP_INST])) or (2 !== $c_inst[EIP_INST])){ // not abs jmp
				if ($phyNextId){
					self::$_units_digraph[$phyPrevId][N][0] = $phyNextId;
					self::$_units_digraph[$phyNextId][P][0] = $phyPrevId;
				}else{ // end unit flag
					self::$_units_digraph[$phyPrevId][N][0] = 0;
				}
			}else{
				self::$_units_digraph[$phyNextId][P][0] = 0;
			}
		}else{
			self::$_units_digraph[$phyNextId][P][0] = 0;
		}
	}
	// 
	protected static function Logics_gen_usable_forbid_pos($units,$step,$reverse){
		if (!$reverse){
			arsort($units);
		}
		// var_dump(self::$_usable_forbid_wait_list);
		self::$_usable_forbid_wait_list = $units;
		while (!empty(self::$_usable_forbid_wait_list)){
			$processing = self::$_usable_forbid_wait_list;
			foreach ($processing as $ptr => $id){				
				// echo '<br>doing...:'.$id;
				$is_modified = false;
				unset(self::$_usable_forbid_wait_list[$ptr]);
				if (0 === $id){continue;}
				$effects_units = self::Logics_get_effects($id,$reverse);
				if (1 === $step){ // gpr & eflags
					$is_modified = self::Logics_gen_usable_forbid_GPR_And_EFLAGS($id,$effects_units);
				}elseif (2 === $step){ // mem 
					$is_modified = self::Logics_gen_usable_mem($id,$effects_units);
				}elseif (3 === $step){ // mem reverse
					$is_modified = self::Logics_gen_readable_mem_reverse($id,$effects_units);
				}elseif (4 === $step){ // stack 
					$is_modified = self::Logics_gen_usable_stack($id,$effects_units);
				}elseif (5 === $step){ // stack 
					$is_modified = self::Logics_gen_usable_stack_reverse($id,$effects_units);
				}
				if ($is_modified){ // re-insert prev unit(s) in wait list
					$rev_reverse = $reverse?false:true;					
					$effects_units = self::Logics_get_effects($id,$rev_reverse);					
					foreach ($effects_units as $eid) {
						if (!in_array($eid, self::$_usable_forbid_wait_list)){
							echo '<br>insert wait list: '.$eid.' by '.$id.' $step='.$step;
							self::$_usable_forbid_wait_list[] = $eid;
						}
					}
				}
			}
		}		
	}
	//
	private static function Logics_gen_usable_stack_reverse($id,$effects_units){
		$ret = false;
		$inherit_stack_usable = false;
		$c_stack_usable = false;
		foreach ($effects_units as $eid){
			if ((isset(self::$_stack_usable_reverse[$eid])) and (self::$_stack_usable_reverse[$eid])){
				$inherit_stack_usable = true;
			}else{
				$inherit_stack_usable = false;
				break;
			}
		}
		if (!$inherit_stack_usable){
			foreach ($effects_units as $eid){
				if ((isset(self::$_STACK_effects[$eid])) and (self::$_STACK_effects[$eid])){
					$inherit_stack_usable = true;
				}else{
					$inherit_stack_usable = false;
					break;
				}
			}
		}
		if ($inherit_stack_usable){
			foreach ($effects_units as $eid){
				if (self::isIndependWriteSp($eid)){
					$inherit_stack_usable = false;
					break;
				}
			}
		}			
		if ((!isset(self::$_stack_usable_reverse[$id]))or($inherit_stack_usable!=self::$_stack_usable_reverse[$id])){
			self::$_stack_usable_reverse[$id] = $inherit_stack_usable;
			$ret = true;
		}
		return $ret;
	}
	//
	private static function Logics_gen_usable_stack($id,$effects_units){
		$ret = false;
		$inherit_stack_usable = false;
		$c_stack_usable = false;
		foreach ($effects_units as $eid){
			if ((isset(self::$_stack_usable[$eid])) and (self::$_stack_usable[$eid])){
				$inherit_stack_usable = true;
			}else{
				$inherit_stack_usable = false;
				break;
			}			
		}
		if ($inherit_stack_usable){
			if (self::isWriteEsp($id)){
				$inherit_stack_usable = false;
			}
		}
		if (!$inherit_stack_usable){
			if ((isset(self::$_STACK_effects[$id])) and (self::$_STACK_effects[$id])){
				$inherit_stack_usable = true;
			}
		}		
		if ((!isset(self::$_stack_usable[$id]))or($inherit_stack_usable!=self::$_stack_usable[$id])){
			self::$_stack_usable[$id] = $inherit_stack_usable;
			$ret = true;
		}
		return $ret;
	}
	// mem's readable reverse
	private static function Logics_gen_readable_mem_reverse($id,$effects_units){
		$ret = false;			
		$mem_readable_reverse = array();
		foreach ($effects_units as $i => $cid){
			// filter gpr effects
			$c_inheritance = array();
			if (isset(self::$_mem_read_able_reverse[$cid])){
				$conflicts = self::Logics_get_mem_conflict($cid,self::$_mem_read_able_reverse[$cid]);
				$c_inheritance = array_diff(self::$_mem_read_able_reverse[$cid], $conflicts);				
			}
			//  add effect units' mem
			$mem_readable_reverse[] = array_unique(array_merge($c_inheritance,self::$_mem_read_able[$cid]));					
		}
		// merge mulit src
		$result = self::Logics_usable_merge($mem_readable_reverse);
		// set up
		if ((!isset(self::$_mem_read_able_reverse[$id]))or(!GeneralFunc::is_same_array($result,self::$_mem_read_able_reverse[$id]))){
			$ret = true;
			self::$_mem_read_able_reverse[$id] = $result;
		}
		return $ret;
	}
	// return conflict mem readable units between $id's effects and mem readable
	private static function Logics_get_mem_conflict($id,$mem_usable){
		$ret = array();
		if (isset(self::$_GPR_effects[$id])){
			foreach (self::$_GPR_effects[$id] as $reg => $value){
				if ((in_array(W,$value)) or (in_array(W|R,$value))){					
					foreach ($mem_usable as $mid){ // writable included readable
						if (self::Logics_is_mem_effect($mid,$reg)){
							$ret[] = $mid;
						}
					}
				}
			}
		}
		return $ret;
	}
	// 判断内存地址是否受指定gpr寄存器影响
	private static function Logics_is_mem_effect($mid,$gpr){
		if (isset(self::$_mem_operand_map[$mid])){
			if (isset(self::$_mem_operand_map[$mid][SIB_BASE])){
				if ($gpr === Instruction::getGeneralRegIndex(self::$_mem_operand_map[$mid][SIB_BASE])){
					return true;
				}
			}
			if (isset(self::$_mem_operand_map[$mid][SIB_INDEX])){
				if ($gpr === Instruction::getGeneralRegIndex(self::$_mem_operand_map[$mid][SIB_INDEX])){
					return true;
				}
			}
		}
		return false;
	}	
	// mem's readable & mem's writable
	private static function Logics_gen_usable_mem($id,$effects_units){
		$ret = false; // 标记上单位需重处理		
		$tmp_n_writ_able = array();		
		$tmp_n_read_able = array();
		$tmp_n_nearest_writ_able = array();

		// inheritance from next unit(s)
		foreach ($effects_units as $nid){
			if (isset(self::$_mem_writ_able[$nid])){
				$tmp_n_writ_able[] = self::$_mem_writ_able[$nid];
			}
			if (isset(self::$_mem_read_able[$nid])){
				$tmp_n_read_able[] = self::$_mem_read_able[$nid];
			}
			if (isset(self::$_mem_nearest_writ_able[$nid])){
				$tmp_n_nearest_writ_able[] = self::$_mem_nearest_writ_able[$nid];
			}
		}

		$n_read_able = self::Logics_usable_merge($tmp_n_read_able);
		$n_writ_able = self::Logics_usable_merge($tmp_n_writ_able);
		self::$_mem_nearest_writ_able[$id] = self::Logics_usable_merge($tmp_n_nearest_writ_able);

		$c_read_able = array();
		$c_writ_able = array();

		$n_usable_conflict = self::Logics_get_mem_conflict($id,$n_read_able);

		if (!empty($n_usable_conflict)){
			$n_read_able = array_diff($n_read_able,$n_usable_conflict);
			$n_writ_able = array_diff($n_writ_able,$n_usable_conflict);
			self::$_mem_nearest_writ_able[$id] = array_diff(self::$_mem_nearest_writ_able[$id],$n_usable_conflict);
		}

		if (isset(self::$_MEM_effects[$id])){
			foreach (self::$_MEM_effects[$id] as $mid => $opt){
				if ($opt & W){
					$c_writ_able[] = $mid;
					self::$_mem_nearest_writ_able[$id] = array($mid);
				}else{
					self::$_mem_nearest_writ_able[$id] = array();
				}
				if (($opt & R) or ($opt & W)){
					$c_read_able[] = $mid;
				}
			}
		}

		$c_writ_able = array_unique(array_merge($c_writ_able,$n_writ_able));
		$c_read_able = array_unique(array_merge($c_read_able,$n_read_able));
	
		if ((!isset(self::$_mem_writ_able[$id]))or(!GeneralFunc::is_same_array($c_writ_able,self::$_mem_writ_able[$id]))){
			$ret = true;
			self::$_mem_writ_able[$id] = $c_writ_able;
		}
		if ((!isset(self::$_mem_read_able[$id]))or(!GeneralFunc::is_same_array($c_read_able,self::$_mem_read_able[$id]))){
			$ret = true;
			self::$_mem_read_able[$id] = $c_read_able;
		}
		return $ret;
	}
	// 
	private static function Logics_gen_usable_forbid_GPR_And_EFLAGS($id,$effects_units){
		$flag_prev_unit = false; // 标记上单位需重处理		
		$tmp_n_usable = array();
		$tmp_n_forbid = array();
		$tmp_n_eflag_usable = array();
		$tmp_n_eflag_forbid = array();	
		foreach ($effects_units as $nid){				
			if (isset(self::$_gpr_write_able[$nid])){
				$tmp_n_usable[] = self::$_gpr_write_able[$nid];
			}
			if (isset(self::$_gpr_write_forbid[$nid])){
				$tmp_n_forbid[] = self::$_gpr_write_forbid[$nid];
			}
			if (isset(self::$_eflags_write_able[$nid])){
				$tmp_n_eflag_usable[] = self::$_eflags_write_able[$nid];
			}				
			if (isset(self::$_eflags_write_forbid[$nid])){
				$tmp_n_eflag_forbid[] = self::$_eflags_write_forbid[$nid];
			}
		}

		// do not expand as heritance
		$n_forbid = self::Logics_gpr_array_merge($tmp_n_forbid,0);		
		$n_usable = self::Logics_gpr_usable_merge($tmp_n_usable);
		
		$n_eflag_usable = self::Logics_usable_merge($tmp_n_eflag_usable);
		$n_eflag_forbid = self::Logics_forbid_merge($tmp_n_eflag_forbid);
		
		$c_usable = array();
		$c_forbid = array();
		$c_eflag_usable = array();
		$c_eflag_forbid = array();		
		// gpr
		if (isset(self::$_GPR_effects[$id])){
			foreach (self::$_GPR_effects[$id] as $reg => $value){
				foreach ($value as $bits => $access){
					if ($access&R){
						$c_forbid[$reg][$bits] = true;
						foreach (Instruction::getGprParents($reg,$bits) as $tmp_bits){
							$c_forbid[$reg][$tmp_bits] = true;
						}
					}else{
						$c_usable[$reg][$bits] = true;
						foreach (Instruction::getGprChildren($reg,$bits) as $tmp_bits){
							$c_usable[$reg][$tmp_bits] = true;
						}
					}
				}
			}
		}
		$c_new_usable = array();
		$c_new_forbid = array();
		self::Logics_gpr_expand($c_usable,$c_new_usable,1);
		self::Logics_gpr_expand($c_forbid,$c_new_forbid,3);
		self::Logics_gpr_diff($n_usable,$c_new_forbid);
		self::Logics_gpr_diff($n_forbid,$c_new_usable);
		$c_final_usable = self::Logics_gpr_array_merge(array($n_usable,$c_new_usable),0);
		$c_final_forbid = self::Logics_gpr_array_merge(array($n_forbid,$c_new_forbid),0);
		
		if ((!isset(self::$_gpr_write_able[$id]))or(!self::Logics_issame_gpr_array(self::$_gpr_write_able[$id],$c_final_usable))){
			self::$_gpr_write_able[$id] = $c_final_usable;
		 	$flag_prev_unit = true;
		 }
		if ((!isset(self::$_gpr_write_forbid[$id]))or(!self::Logics_issame_gpr_array(self::$_gpr_write_forbid[$id],$c_final_forbid))){
			self::$_gpr_write_forbid[$id] = $c_final_forbid;
		 	$flag_prev_unit = true;
		}


		// eflags
		if (isset(self::$_EFLAGS_effects[$id])){
			foreach (self::$_EFLAGS_effects[$id] as $reg => $value){
				if ($value & R){
					$c_eflag_forbid[] = $reg;
				}else{
					$c_eflag_usable[] = $reg;
				}
			}
		}
		// eflags
		self::Logics_midata_merge($n_eflag_usable,$n_eflag_forbid,$c_eflag_usable,$c_eflag_forbid);
		if ((!isset(self::$_eflags_write_able[$id]))or(!GeneralFunc::is_same_array(self::$_eflags_write_able[$id],$c_eflag_usable))){
			self::$_eflags_write_able[$id] = $c_eflag_usable;
			$flag_prev_unit = true;
		}
		if ((!isset(self::$_eflags_write_forbid[$id]))or(!GeneralFunc::is_same_array(self::$_eflags_write_forbid[$id],$c_eflag_forbid))){
			self::$_eflags_write_forbid[$id] = $c_eflag_forbid;
			$flag_prev_unit = true;
		}

		return $flag_prev_unit;
	}
	//
	private static function Logics_midata_merge($n_usable,$n_forbid,&$c_usable,&$c_forbid){
		$n_usable = array_diff($n_usable,$c_forbid,$n_forbid,$c_usable);
		$n_forbid = array_diff($n_forbid,$c_forbid,$c_usable);

		$c_usable = array_merge($c_usable,$n_usable);
		$c_forbid = array_merge($c_forbid,$n_forbid);
	}
	//
	private static function Logics_gpr_usable_merge($array){
		$ret = array();		
		if (isset($array[0])){
			if (isset($array[1])){			
				foreach ($array[0] as $regIdx => $c){
					foreach ($c as $regBits => $true){
						if (isset($array[1][$regIdx][$regBits])){
							$ret[$regIdx][$regBits] = true;
						}
					}
				}
			}else{
				$ret = $array[0];
			}
		}
		return $ret;
	}
	//
	private static function Logics_usable_merge($array){
		$ret = array();
		if (isset($array[1])){
			$ret = array_intersect($array[0],$array[1]);
		}elseif (isset($array[0])){
			$ret = $array[0];
		}
		return $ret;
	}
	private static function Logics_forbid_merge($array){
		$ret = array();
		if (isset($array[1])){
			$ret = array_unique(array_merge($array[0],$array[1]));
		}elseif (isset($array[0])){
			$ret = $array[0];
		}
		return $ret;
	}	
	// 收集影响单位(默认从下至上,$reverse=true则从上至下)
	private static function Logics_get_effects($id,$reverse=false){
		$ret = array();
		if ($reverse){
			if (isset(self::$_units_digraph[$id][P][0])){
				$natral_prev_id = self::$_units_digraph[$id][P][0];
				if (0 < $natral_prev_id){
					$ret[] = $natral_prev_id;
					if (isset(self::$_units_digraph[$natral_prev_id][P][1])){
						$ret[] = self::$_units_digraph[$natral_prev_id][P][1];
					}
				}
			}
		}else{
			if (isset(self::$_units_digraph[$id][N][0])){
				$ret[] = self::$_units_digraph[$id][N][0];
			}
			if (isset(self::$_units_digraph[$id][N][1])){
				$unnatral_next_id = self::$_units_digraph[$id][N][1];				
				if (0 < $unnatral_next_id){
					if (isset(self::$_units_digraph[$unnatral_next_id][N][0])){
						$ret[] = self::$_units_digraph[$unnatral_next_id][N][0];
					}
				}
			}
		}
		return $ret;
	}
	//
	private static function Logics_gpr_array_merge($gprArr,$type){
		$ret = array();
		foreach ($gprArr as $c_gpr){
			self::Logics_gpr_expand($c_gpr,$ret,$type);
		}
		return $ret;
	}	
	// $type : 1.expand to children;2.expand to parents;3.include 1&2;
	private static function Logics_gpr_expand($src,&$dst,$type){
		foreach ($src as $reg => $c){
			foreach ($c as $bits => $true){
				$dst[$reg][$bits] = true;
				if ($type & 1){
					foreach (Instruction::getGprChildren($reg,$bits) as $tmp_bits){
						$dst[$reg][$tmp_bits] = true;
					}
				}
				if ($type & 2){
					foreach (Instruction::getGprParents($reg,$bits) as $tmp_bits){
						$dst[$reg][$tmp_bits] = true;
					}
				}
			}
		}
	}
	//
	private static function Logics_issame_gpr_array($a,$b){
		if (count($a) !== count($b)){return false;}
		foreach ($a as $reg => $contents){
			if (!isset($b[$reg])){return false;}
			if (count($contents) !== count($b[$reg])){return false;}
			foreach ($contents as $bits => $true){
				if (!isset($b[$reg][$bits])){return false;}
			}
		}
		return true;
	}
	// $src -= $filter
	private static function Logics_gpr_diff(&$src,$filter){
		foreach ($filter as $reg => $c){
			foreach ($c as $bits => $true){
				if (isset($src[$reg][$bits])){
					unset($src[$reg][$bits]);
				}
			}
			if (empty($src[$reg])){
				unset ($src[$reg]);
			}
		}
	}
	public static function Logics_set_public_last_unit($id){
		if (isset(self::$_units_digraph[$id])){
			self::$_public_last_unit_id = $id;
		}
	}
	// return usable 
	public static function Get_Unit_Usable($id,$dir=false){
		if (false === $dir){$dir = P|N;}
		$ret = array();
		if ($dir & P){
			if (isset(self::$_units_digraph[$id])){
				$ret[P][GPR_WRITE_ABLE]  = self::$_gpr_write_able[$id];
				$ret[P][FLAG_WRITE_ABLE] = self::$_eflags_write_able[$id];
				// $ret['mem_write']  = self::$_mem_writ_able[$id];
				$ret[P][MEM_READ_ABLE]   = array_unique(array_merge(self::$_mem_read_able[$id],self::$_mem_read_able_reverse[$id]));
				$ret[P][MEM_WRITE_ABLE]  = self::$_mem_nearest_writ_able[$id];
				$ret[P][STACK_USABLE]    = self::isUsableStack($id,P);
			}
			if (P === $dir){return $ret[P];}
		}
		if ($dir & N){
			$nexts = self::Logics_get_effects($id);			
			if (empty($nexts)){ // no next unit, use public last unit
				if (false !== self::$_public_last_unit_id){
					$ret[N] = self::Get_Unit_Usable(self::$_public_last_unit_id,P);
				}
			}else{
				foreach ($nexts as $eid){
					$c_usable = self::Get_Unit_Usable($eid,P);
					if (!isset($ret[N])){
						$ret[N] = $c_usable;
					}else{ // merge
						$ret[N][GPR_WRITE_ABLE]  = self::Logics_gpr_usable_merge(array($ret[N][GPR_WRITE_ABLE],$c_usable[GPR_WRITE_ABLE]));
						$ret[N][FLAG_WRITE_ABLE] = self::Logics_usable_merge(array($ret[N][FLAG_WRITE_ABLE],$c_usable[FLAG_WRITE_ABLE]));
						$ret[N][MEM_READ_ABLE]   = self::Logics_usable_merge(array($ret[N][MEM_READ_ABLE],$c_usable[MEM_READ_ABLE]));
						$ret[N][MEM_WRITE_ABLE]  = self::Logics_usable_merge(array($ret[N][MEM_WRITE_ABLE],$c_usable[MEM_WRITE_ABLE]));
					}
				}
				$ret[N][STACK_USABLE] = self::isUsableStack($id,N);
			}			
			if (N === $dir){return $ret[N];}
		}
		return $ret;
	}
	// reset all units' forbid & usable by manual
	public static function Reset_Access_By_Manual($type,$array){
		$all_units = array_keys(self::$_units_digraph);
		if ('eflag' === $type){
			foreach ($array as $eflag => $value){
				if (Instruction::isEflag($eflag)){
					foreach ($all_units as $uid){
						if (false !== array_search($eflag, self::$_eflags_write_forbid[$uid])){
							continue;
						}
						if (false !== array_search($eflag, self::$_eflags_write_able[$uid])){
							continue;
						}
						self::$_eflags_write_able[$uid][] = $eflag;
					}
				}
			}
		}elseif ('gpr' === $type){
			foreach ($all_units as $uid){
				self::$_gpr_write_able[$uid] = self::Logics_gpr_array_merge(array($array,self::$_gpr_write_able[$uid]),1);
				self::Logics_gpr_diff(self::$_gpr_write_able[$uid],self::$_gpr_write_forbid[$uid]);
			}
		}elseif ('gpr_forbid' === $type){
			$new_forbid = array();
			foreach ($array as $gpr){
				if ($gprIdx = Instruction::getGeneralRegIndex($gpr)){
					if ($gprBits = Instruction::getGeneralRegBits($gpr,true)){
						$new_forbid[$gprIdx][$gprBits] = true;
					}
				}
			}
			foreach ($all_units as $uid){
				self::$_gpr_write_forbid[$uid] = self::Logics_gpr_array_merge(array($new_forbid,self::$_gpr_write_forbid[$uid]),3);
				self::Logics_gpr_diff(self::$_gpr_write_able[$uid],self::$_gpr_write_forbid[$uid]);
			}
		}elseif ('stack' === $type){
			foreach ($all_units as $uid){
				self::$_stack_usable[$uid] = true;
			}			
		}elseif ('mem_write_forbid' === $type){
			foreach ($all_units as $uid){
				self::$_mem_writ_able[$uid] = array();
				self::$_mem_nearest_writ_able[$uid] = array();
			}
		}
	}
	//
	public static function isUsableStackbyUnit($id){
		if (self::isUsableStack($id,P)){return true;}
		if (self::isUsableStack($id,N)){return true;}
		return false;
	}
	//
	public static function isUsableStack($id,$dir){
		if (P === $dir){
			return self::Logics_is_Usable_Stack($id);
		}
		if (N === $dir){
			$next_unit = self::Logics_get_effects($id);
			if (!empty($next_unit)){
				foreach ($next_unit as $eid){
					if (!self::isUsableStack($eid,P)){
						return false;
					}
				}
				return true;
			}
		}		
		return false;
	}
	//
	private static function Logics_is_Usable_Stack($id){
		if (isset(self::$_stack_usable[$id]) and (self::$_stack_usable[$id])){return true;}
		if (isset(self::$_stack_usable_reverse[$id]) and (self::$_stack_usable_reverse[$id])){return true;}
		return false;
	}
	// // remove [it'll be completed auto]
	// protected static function Logics_remove($id){
	// 	// unit should not have any connect with other units when remove
	// 	$conn_yet = false;
	// 	if (isset(self::$_units_digraph[$id])){
	// 		if (isset(self::$_units_digraph[$id][P][0])){
	// 			$prev_id = self::$_units_digraph[$id][P][0];
	// 			if ($prev_id){
	// 				if ($id === self::$_units_digraph[$prev_id][N][0]){
	// 					$conn_yet = true;
	// 				}
	// 			}
	// 		}			
	// 		if (isset(self::$_units_digraph[$id][N][0])){
	// 			$next_id = self::$_units_digraph[$id][N][0];
	// 			if ($next_id){
	// 				if ($id === self::$_units_digraph[$next_id][P][0]){
	// 					$conn_yet = true;
	// 				}
	// 			}
	// 		}
	// 		if ($conn_yet){
	// 			GeneralFunc::LogInsert('shouldnt try to remove unit which still connecting other units,id: '.$id,WARNING);
	// 		}else{
	// 			unset(self::$_units_digraph[$id]);
	// 		}
	// 	}
	// }
}