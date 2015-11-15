<?php

// organ physic required in organ.wrapper.func.php
trait tOrganPhysic{
	private static $_unit_begin_index;
	private static $_unit_insert_index;

	private static $_unit_dlist;
	private static $_unit_inst;

	private static $_unit_usable;
	private static $_unit_forbid;
	private static $_unit_fat;
	private static $_unit_inst_len;
	private static $_unit_ipsp;
	private static $_unit_comment;

	protected static $_GPR_effects; // general purpose register
	protected static $_MEM_effects;
	protected static $_EFLAGS_effects;
	protected static $_STACK_effects;
	protected static $_SP_writes;

	protected static $_rel_jmp_dst;
	protected static $_rel_jmp_range;
	protected static $_rel_jmp_max;
	protected static $_rel_jmp_units;
	protected static $_rel_jmp_pos_effects;
	protected static $_rel_jmp_label;
	protected static $_rel_jmp_expired; // wait to re-set	
	protected static $_rel_jmp_range_expired; // wait to re-getlen

	protected static $_mem_operand_map; // mem operands map
	protected static $_reloc_map;       // reloc value map


	// private static $_sec;

	// independent data (without rollback)
	private static $_data4rollback;
	private static $_stack_points_pattern;
	private static $_stack_points_array;
	private static $_stack_points_index_array;


	// const
	private static $_FAIL_MATCH_LABEL = -1;
	private static $_FAIL_GET_UNITS   = -2;
	private static $_FAIL_OVER_RANGE  = -3;
    
    //
    public static function Physic_export(){
    	return array(
			self::$_unit_begin_index,
			self::$_unit_insert_index,
			self::$_unit_dlist,
			self::$_unit_inst,
			self::$_unit_usable,
			self::$_unit_forbid,
			self::$_unit_fat,
			self::$_unit_inst_len,
			self::$_GPR_effects,
			self::$_MEM_effects,
	        self::$_EFLAGS_effects,
	        self::$_STACK_effects,
			self::$_SP_writes,
			self::$_unit_ipsp,
			self::$_unit_comment,
			self::$_rel_jmp_dst,
			self::$_rel_jmp_range,
			self::$_rel_jmp_max,
			self::$_rel_jmp_units,
			self::$_rel_jmp_pos_effects,
			self::$_rel_jmp_label,
			// self::$_sec,
			self::$_rel_jmp_expired,
			self::$_rel_jmp_range_expired,
			self::$_mem_operand_map,
			self::$_reloc_map,
		);
    }
    public static function Physic_import($src){
    	self::flush4Rollback();
		self::$_unit_begin_index      = $src[0];
		self::$_unit_insert_index     = $src[1];
		self::$_unit_dlist            = $src[2];
		self::$_unit_inst             = $src[3];
		self::$_unit_usable           = $src[4];
		self::$_unit_forbid           = $src[5];
		self::$_unit_fat              = $src[6];
		self::$_unit_inst_len         = $src[7];
		self::$_GPR_effects           = $src[8];
		self::$_MEM_effects           = $src[9];
        self::$_EFLAGS_effects        = $src[10];
        self::$_STACK_effects         = $src[11];
		self::$_SP_writes             = $src[12];
		self::$_unit_ipsp             = $src[13];
		self::$_unit_comment          = $src[14];
		self::$_rel_jmp_dst           = $src[15];
		self::$_rel_jmp_range         = $src[16];
		self::$_rel_jmp_max           = $src[17];
		self::$_rel_jmp_units         = $src[18];
		self::$_rel_jmp_pos_effects   = $src[19];
		self::$_rel_jmp_label         = $src[20];
		self::$_rel_jmp_expired       = $src[21];
		self::$_rel_jmp_range_expired = $src[22];
		self::$_mem_operand_map       = $src[23];
		self::$_reloc_map             = $src[24];
    }
    // TODO : Deprecated
	// Export & Import
	public static function export(){
		return serialize(self::Physic_export());		
	}
	// TODO : Deprecated
	public static function import($str){		
		$tmp = unserialize($str);
		return self::Physic_import($tmp);
	}
	// flush for RollBack
	private static function flush4Rollback(){
		self::$_unit_begin_index = 0;
		self::$_unit_insert_index = 1;
		self::$_unit_dlist  = array();

		self::$_unit_inst   = array();
		self::$_unit_usable = array();
		self::$_unit_forbid = array();
		self::$_unit_fat    = array();
		self::$_unit_inst_len = array();
		self::$_GPR_effects  = array();
		self::$_MEM_effects  = array();
        self::$_EFLAGS_effects = array();
        self::$_STACK_effects = array();
        self::$_SP_writes    = array();
		self::$_unit_ipsp    = array();
		self::$_unit_comment = array();
		self::$_rel_jmp_dst = array();
		self::$_rel_jmp_range = array();
		self::$_rel_jmp_max = array();
		self::$_rel_jmp_units = array();
		self::$_rel_jmp_pos_effects = array();
		self::$_rel_jmp_label = array();

		self::$_rel_jmp_expired = array();
		self::$_rel_jmp_range_expired = array();

		self::$_mem_operand_map = array();
		self::$_reloc_map       = array();

	    // self::$_sec = 0;
	    
	    self::$_data4rollback = NULL;
	}	
	// flush all 
	private static function flushAll(){
		self::flush4Rollback();
		self::$_data4rollback = NULL;
		self::$_stack_points_pattern = '';
		self::$_stack_points_array = array();
		self::$_stack_points_index_array = array();
	}
	// init
	public static function init($sl_dlist,$sl_inst,$sl_usable,$sl_forbid,$sl_len,$sl_GPR_effects){
		self::flushAll();

	    self::$_unit_inst     = $sl_inst;       
		self::$_unit_usable   = $sl_usable;
		self::$_unit_forbid   = $sl_forbid;
		self::$_unit_inst_len = $sl_len;
		self::$_unit_dlist    = $sl_dlist;
		self::$_GPR_effects   = $sl_GPR_effects;

		self::$_unit_begin_index = DEFAULT_DLIST_FIRST_NUM;
		self::$_unit_insert_index = 1 + max(array_keys(self::$_unit_inst));

		// self::$_sec = $sec;

		// init comments
		$c = self::getBeginUnit();
		while ($c){
			self::appendComment($c,'s'.$c);
			$c = self::next($c);
		}
	}
	private static function cloneLabel($id){
		$idx = self::newUnitByClone($id,$id);
		self::$_unit_inst[$idx][LABEL] .= '_CLONE_'."$idx";
		self::$_rel_jmp_label[$idx] = self::$_unit_inst[$idx][LABEL];
		if (isset(self::$_unit_fat[$idx][P])){ // clone Label Prev fat -> erase!
			unset(self::$_unit_fat[$idx][P]);
		}		
		return $idx;
	}
	// ready & start rollback
	public static function ready(){
		self::$_data4rollback = self::export();
	}
	public static function rollback(){
		self::import(self::$_data4rollback);
		self::$_data4rollback = NULL;
	}
	// new unit func(s)
	public static function newUnitNaked($pos,$inst_array){
		$idx = self::$_unit_insert_index;
		self::$_unit_insert_index ++;
		self::$_unit_ipsp[$idx] = self::is_effect_ipsp($inst_array);
		self::$_unit_inst[$idx] = $inst_array;
		//TODO self::initGPReffects($idx,$inst_array);
		if (self::insertUnit($pos,$idx)){
			self::$_unit_usable[$idx][P] = self::$_unit_usable[$idx][N] = array();
			self::$_unit_forbid[$idx][P] = self::$_unit_forbid[$idx][N] = array();
			if (isset($inst_array[LABEL])){
				self::$_rel_jmp_label[$idx] = $inst_array[LABEL];
			}else{				
				if ($c_array = self::getRelJmpInst($idx)){
					self::$_rel_jmp_dst[$idx] = false;
					self::$_rel_jmp_range[$idx] = 0;
					if ($c_array[0]){
						self::$_rel_jmp_max[$idx] = $c_array[0];
					}					
					self::$_rel_jmp_units[$idx] = array();
					self::$_rel_jmp_expired[$idx] = $idx; // need to be reset RelJmp
				}
			}
			if (self::seekResetRel($idx)){
				return $idx;
			}
		}
		return false;
	}
	// new unit,and clone from the pointed unit's direction
	public static function newUnitByCloneAttr($pos,$inst_array,$clone_id,$clone_dir){
		if ($idx = self::newUnitNaked($pos,$inst_array)){
			self::cloneUnitDirAttr($idx,P,$clone_id,$clone_dir);
			self::cloneUnitDirAttr($idx,N,$clone_id,$clone_dir);
			return $idx;
		}
		return false;
	}
	// new unit,set usable and fat by manual (TODO: 待被替换)
	public static function newUnitByManual($pos,$inst_array,$usable,$fat){		
		if ($idx = self::newUnitNaked($pos,$inst_array)){
			self::$_unit_usable[$idx] = $usable;
			if ($fat){
				self::$_unit_fat[$idx] = $fat;
			}
			return $idx;
		}
		return false;
	}
	// new unit , clone from an existing unit
	public static function newUnitByClone($pos,$src){
		if ($inst = self::getCode($src)){
			if ($idx = self::newUnitNaked($pos,$inst)){
				self::cloneUnitDirAttr($idx,P,$src,P);
				self::cloneUnitDirAttr($idx,N,$src,N);				
				self::$_unit_comment[$idx] = self::$_unit_comment[$src];
				return $idx;
			}
		}		
		return false;
	}
	private static function cloneUnitDirAttr($dst_id,$dst_dir,$src_id,$src_dir){
		if (isset(self::$_unit_usable[$src_id][$src_dir])){
			self::$_unit_usable[$dst_id][$dst_dir] = self::$_unit_usable[$src_id][$src_dir];
		}
		if (isset(self::$_unit_forbid[$src_id][$src_dir])){
			self::$_unit_forbid[$dst_id][$dst_dir] = self::$_unit_forbid[$src_id][$src_dir];
		}		
		if (isset(self::$_unit_fat[$src_id][$src_dir])){
			self::$_unit_fat[$dst_id][$dst_dir] = self::$_unit_fat[$src_id][$src_dir];
		}
	}
	// clone usable(s)
	public static function cloneUsables($src,$direction,$dst){
		self::$_unit_usable[$dst][P] = self::$_unit_usable[$src][$direction];
		self::$_unit_usable[$dst][N] = self::$_unit_usable[$src][$direction];
		self::$_unit_forbid[$dst][P] = self::$_unit_forbid[$src][$direction];
		self::$_unit_forbid[$dst][N] = self::$_unit_forbid[$src][$direction];
	}
	// add Comment
	public static function appendComment($id,$comment,$clone_src=false){
		if (false != $clone_src){
			if (isset(self::$_unit_comment[$clone_src])){
				self::$_unit_comment[$id] = self::$_unit_comment[$clone_src];
			}
		}
		if ($comment){
			self::$_unit_comment[$id][] = $comment;
		}
	}
	// echo Comment
	public static function echoComment($id){
		if (isset(self::$_unit_comment[$id])){
			$ret = implode(',',self::$_unit_comment[$id]);
		}else{
			$ret = '';
		}
		return $ret;
	}
	public static function getMemOperandArr($mid){
		return isset(self::$_mem_operand_map[$mid])?self::$_mem_operand_map[$mid]:false;
	}
	public static function getBeginUnit(){
		return self::$_unit_begin_index;
	}
	public static function getCode($id){
		return isset(self::$_unit_inst[$id])?self::$_unit_inst[$id]:false;
	}
	private static function getCurrentMemArr($id){
		$ret = false;
		if (isset(self::$_unit_inst[$id][P_TYPE])){
			$pid = array_search(T_MEM, self::$_unit_inst[$id][P_TYPE]);
			if (false !== $pid){
				$ret = self::getMemOperandArr(self::$_unit_inst[$id][OPERAND][$pid]);
			}
		}
		if (false === $ret){$ret = array();}
		return $ret;
	}
	protected static function getLen($id,$forced=false){
		if (self::isValidUnit($id)){
			if (($forced) or (!isset(self::$_unit_inst_len[$id]))){
				$c_inst = self::getCode($id);
				$c_len = 0;
				// echo '<br>+++++++++++++++++++++++++++++++++ '.$id.'<br>';
				// var_dump($c_inst);
				if (isset($c_inst[VIRT_UNIT])){
					$c_len = 0;
				}else{
					$extend = array();
					$c_p_m_reg = self::getCurrentMemArr($id);
					$c_vec_range = isset(self::$_rel_jmp_range[$id])?self::$_rel_jmp_range[$id]:0;
					$c_reverse_jmp = self::isReverseJmp($id); // 反跳
					$predictLenArray = PredictInstLen::predictInstLength($c_inst,$c_p_m_reg,$c_vec_range,$c_reverse_jmp,$extend);
					if (!empty($extend)){
						self::$_unit_inst[$id] += $extend;
					}
					// var_dump($predictLenArray);
					if (false === $predictLenArray){
						// GeneralFunc::LogInsert('inst length predicted fail, id: '.$id.', ['.$c_inst[INST].']', WARNING);
						$c_len = SINGLE_INST_MAX;
					}else{					
						foreach ($predictLenArray as $v){
							$c_len += $v;
						}					
					}
				}
				self::setInstLength($id,$c_len);						
			}			
			return self::$_unit_inst_len[$id];
		}
		return 0;
	}
	public static function getUsable($id,$dir=false){
		if (isset(self::$_unit_usable[$id])){
			if (false === $dir){
				return self::$_unit_usable[$id];
			}else{
				return isset(self::$_unit_usable[$id][$dir])?self::$_unit_usable[$id][$dir]:array();
			}
		}
		return false;
	}
	public static function getForbid($id){
		return isset(self::$_unit_forbid[$id])?self::$_unit_forbid[$id]:false;
	}
	public static function getLabel($id){
		return isset(self::$_unit_inst[$id][LABEL])?self::$_unit_inst[$id][LABEL]:false;
	}
	public static function getRelJmpRange($id){
	    return isset(self::$_rel_jmp_range[$id])?self::$_rel_jmp_range[$id]:false;
	}
	public static function getUnitNumber(){
		return count(self::$_unit_dlist);
	}
	public static function getAllUnits(){
		return array_keys(self::$_unit_dlist);
	}
	public static function getGPReffects($id){
		return isset(self::$_GPR_effects[$id])?self::$_GPR_effects[$id]:array();
	}
	// 获取所有可用range < $deadline的 rel_jmp 涉及的units
	public static function getLessJmpRangeUnits($deadline){
		$ret = array();
		foreach (self::$_rel_jmp_max as $id => $max){
			if (self::isValidUnit($id)){
				if ($max){
					if ($max <= (self::$_rel_jmp_range[$id] + $deadline)){
						$ret = array_merge($ret,self::$_rel_jmp_units[$id]);
					}
				}
			}
		}
		$ret = array_unique($ret);
		return $ret;
	}
	public static function getStackPointArray(){
		return self::$_stack_points_index_array;
	}
	public static function getDListUnit($id){
		return self::isValidUnit($id)?self::$_unit_dlist[$id]:false;
	}	
	public static function getAmongUnits($a,$b){
		$ret = self::getAmongUnitsBySort($a,$b);
		if (false === $ret){
			$ret = self::getAmongUnitsBySort($b,$a);
		}
		return $ret;
	}
	public static function getRelocInfo($id){
		return isset(self::$_unit_inst[$id][REL])?self::$_unit_inst[$id][REL]:false;
	}
	public static function getTotalBinSize(){		
		if (true !== self::resetRelJmpExpired()){				
			return false;
		}
		// calculate all insts' length
		$size = 0;
		$c = self::getBeginUnit();
		while ($c){
			$size += self::getLen($c);
			$c = self::next($c);
		}
		return $size;
	}
	public static function getRelocArr($id){
		return isset(self::$_reloc_map[$id])?self::$_reloc_map[$id]:false;
	}
	private static function getAmongUnitsBySort($first,$last){
		$units = array();
		$c = $first;
		$i = 1;
		while ($c){
			$units[$i] = $c;
			if ($c == $last){
				return $units;
			}
			$c = self::next($c);
			$i ++;
		}
		return false;
	}
	private static function getRelJmpInst($id){
		if (isset(self::$_unit_inst[$id][OPERATION])){
			if ((Instruction::isJmpStatic(self::$_unit_inst[$id][OPERATION])) and (0 === strpos(self::$_unit_inst[$id][PARAMS][0],UNIQUEHEAD.'SOLID_JMP_'))){
				$max = Instruction::getJmpRangeLmt(self::$_unit_inst[$id][OPERATION]);
				return array($max);
			}
		}
		return false;
	}
	protected static function getComment($id){
		return isset(self::$_unit_comment[$id])?self::$_unit_comment[$id]:'';
	}
	// set method(s)
	private static function setEffectsJmpRangeExpired($id){
		$tmp = array();
		if (isset(self::$_rel_jmp_pos_effects[$id][P])){
			$tmp = self::$_rel_jmp_pos_effects[$id][P];
		}
		if (isset(self::$_rel_jmp_pos_effects[$id][N])){
			$tmp = array_merge($tmp,self::$_rel_jmp_pos_effects[$id][N]);
		}		
		if (!empty($tmp)){
			$tmp = array_unique($tmp);	
			foreach ($tmp as $c){
				if (in_array($id, self::$_rel_jmp_units[$c])){
					self::$_rel_jmp_range_expired[$c] = $c;
				}
			}
		}		
	}
	private static function setComment($id,$comment){
		$c = '';
		if (isset($comment[1])){
			$c = self::getComment($comment[1]);
		}
		if (isset($comment[0])){
			$c .= $comment[0].';';
		}
		self::$_unit_comment[$id] = $c;
	}
	private static function setInstLength($id,$len){
		$changed = false;
		if ((isset(self::$_unit_inst_len[$id])) and (self::$_unit_inst_len[$id] != $len)){
		 	$changed = true;
		 	echo "<br>inst[".$id."] length changed:".self::$_unit_inst_len[$id].' -> '.$len;
		}
		self::$_unit_inst_len[$id] = $len;
		if ($changed){
		 	self::setEffectsJmpRangeExpired($id);
		 	var_dump(self::$_rel_jmp_range_expired);
		}
	}
	public static function setStackPattern($c_stack_pointer_define){
		self::$_stack_points_pattern = false;
		if (is_array($c_stack_pointer_define)){
			foreach ($c_stack_pointer_define as $a){
				$c = Instruction::getGeneralRegIndex($a);
				self::$_stack_points_index_array[$c] = true;
				$c_reg_array = Instruction::getRegsByIdx($a);	
				foreach ($c_reg_array as $c){
					self::$_stack_points_pattern .=  '('.$c.')|';
					self::$_stack_points_array[] = $c;					
				}
			}
			if (false !== self::$_stack_points_pattern){
			    self::$_stack_points_pattern = substr (self::$_stack_points_pattern,0,strlen(self::$_stack_points_pattern) - 1);
			}
			self::$_stack_points_index_array = array_keys(self::$_stack_points_index_array);
		}
		if (defined('DEBUG_ECHO')){
		    DebugShowFunc::my_shower_09(self::$_stack_points_pattern,$c_stack_pointer_define,self::$_stack_points_array,self::$_stack_points_index_array);
		}
	}
	public static function setStackValid($id){
		self::$_unit_usable[$id][P][STACK] = true;
		self::$_unit_usable[$id][N][STACK] = true;
	}	
	// TODO: delete
	// public static function setGPReffects($id,$reg,$bits,$perm){
	// 	if (isset(self::$_GPR_effects[$id][$reg][$bits])){
	// 		self::$_GPR_effects[$id][$reg][$bits] |= $perm;
	// 	}else{
	// 		self::$_GPR_effects[$id][$reg][$bits] = $perm;
	// 	}		
	// }
	// 在$id指令后'伪'分配$size,检测limited jcc是否会over range, 注:resetRelJmp后失效
	public static function setPseudoAlloc($id,$dir,$size){
		if ((!empty(self::$_rel_jmp_max)) and (!empty(self::$_rel_jmp_pos_effects[$id][$dir]))){
			$objs = array_intersect(self::$_rel_jmp_pos_effects[$id][$dir],array_keys(self::$_rel_jmp_max));
			foreach ($objs as $jid){
				if (self::$_rel_jmp_range[$jid] + $size >= self::$_rel_jmp_max[$jid]){
					return false;
				}
			}
			foreach ($objs as $jid){
				self::$_rel_jmp_range[$jid] += $size;
			}
		}
		return true;
	}
	// remove method(s)
	public static function removeStackRegUsable($id,$dir){
		if (!empty(self::$_stack_points_index_array)){
			foreach (self::$_stack_points_index_array as $reg){
				self::removeUsableReg($id,$dir,$reg);
			}			
		}
	}
	private static function removeUsableReg($id,$dir,$reg){
		if (isset(self::$_unit_usable[$id][$dir][NORMAL_WRITE_ABLE][$reg])){
			unset(self::$_unit_usable[$id][$dir][NORMAL_WRITE_ABLE][$reg]);
		}
	}
	public static function removeWritableMem($id,$dir){
		if (isset(self::$_unit_usable[$id][$dir][MEM_OPT_ABLE])){
			$iterator = self::$_unit_usable[$id][$dir][MEM_OPT_ABLE];
			foreach ($iterator as $i => $v){
				if (ValidMemAddr::is_writable($v)){
					$tmp = ValidMemAddr::get($v);
					$tmp[OPT] &= 1;
					self::$_unit_usable[$id][$dir][MEM_OPT_ABLE][$i] = ValidMemAddr::append($tmp);
				}
			}
		}
	}
	private static function removeLen($id){
		unset(self::$_unit_inst_len[$id]);
	}
	// add usable reg (['EAX'][BITS] = true)
	public static function addUsableReg($id,$dir,$reg,$ignor_forbid){
		if (isset(self::$_unit_inst[$id])){
			if (!isset(self::$_unit_forbid[$id][$dir][NORMAL][$reg])){
				self::$_unit_usable[$id][$dir][NORMAL_WRITE_ABLE][$reg][OPT_BITS] = true;
				return true;
			}else{
				if ($ignor_forbid){
					unset (self::$_unit_forbid[$id][$dir][NORMAL][$reg]);
					self::$_unit_usable[$id][$dir][NORMAL_WRITE_ABLE][$reg][OPT_BITS] = true;
					// cancel all mem address valid flag (which include $reg)
					if (isset(self::$_unit_usable[$id][$dir][MEM_OPT_ABLE])){
						$tmp = self::$_unit_usable[$id][$dir][MEM_OPT_ABLE];
						foreach ($tmp as $a => $mem_idx){
							if (ValidMemAddr::is_reg_include($mem_idx,$reg)){
								unset(self::$_unit_usable[$id][$dir][MEM_OPT_ABLE][$a]);
							}
						}
						if (empty(self::$_unit_usable[$id][$dir][MEM_OPT_ABLE])){
							unset (self::$_unit_usable[$id][$dir][MEM_OPT_ABLE]);
						}
					}
					return true;
				}
			}
		}
		return false;
	}
	public static function addUsableFlag($id,$dir,$flag){
		if (isset(self::$_unit_inst[$id])){
			if (!isset(self::$_unit_forbid[$id][$dir][FLAG][$flag])){
				self::$_unit_usable[$id][$dir][FLAG_WRITE_ABLE][$flag] = true;
				return true;
			}
		}
		return false;
	}
	// DList method(s)	
	public static function prev($id){
		return isset(self::$_unit_dlist[$id][P])?self::$_unit_dlist[$id][P]:false;
	}
	public static function next($id){
		return isset(self::$_unit_dlist[$id][N])?self::$_unit_dlist[$id][N]:false;
	}
	public static function manualDLink($prev,$next){
		self::allRelJmpExpired(); // TODO: diversify
		return self::reDlink($prev,$next);
	}
	protected static function removeDLink($id){
		if (self::isValidUnit($id)){
			self::reDlink(self::prev($id),self::next($id));
			self::setRelJmpExpired($id,P);
			self::setRelJmpExpired($id,N);			
			// label ? set $_rel_jmp_dst = false
			if (self::getLabel($id)){
				if ($rid = array_search($id, self::$_rel_jmp_dst)){
					self::$_rel_jmp_dst[$rid] = false;
				}
				unset (self::$_rel_jmp_label[$id]);
			}
			// is $_rel_jmp, clear all
			if (isset(self::$_rel_jmp_dst[$id])){
				self::flushRelJmp($id);
			}
			unset(self::$_unit_dlist[$id]);			
		}		
	}
	private static function reDlink($prev,$next){		
		if (self::isValidUnit($next)){
			self::$_unit_dlist[$next][P] = $prev;			
		}
		if (self::isValidUnit($prev)){
			self::$_unit_dlist[$prev][N] = $next;
		}else{
			if (self::isSentinelUnit($prev)){
				self::$_unit_begin_index = $next;
			}
		}
	}
	private static function insertUnit($prev,$current){
		self::$_unit_dlist[$current] = array(P=>false,N=>false);
		if (self::isValidUnit($prev)){
			$next = self::next($prev);
		}elseif (self::isSentinelUnit($prev)){
			$next = self::getBeginUnit();
		}else{
			return false;
		}
		self::reDlink($prev,$current);
		self::reDlink($current,$next);
		self::setRelJmpExpired($prev,N);
		self::setRelJmpExpired($next,P);
		return true;
	}
	// Func(s)

	// 识别 目标指令是否需要ipsp保护
	public static function is_effect_ipsp($inst_array){
		if (isset($inst_array[LABEL])){
			return true;
		}
		if (Instruction::isJmp($inst_array[OPERATION])){ //绝对 或 相对 跳转
			return true;
		}
		$operand_num = 0;
		if (isset($inst_array[P_TYPE])){
			$operand_num = count($inst_array[P_TYPE]);
		}
		$opt = Instruction::getInstructionOpt($inst_array[OPERATION],$operand_num);
		if (isset($opt[STACK])){
			return true;
		}		
		if ((isset($inst_array[PARAMS])) and (is_array($inst_array[PARAMS]))){ //参数，寄存器SP 或 ESP ，读或写 操作	
			foreach ($inst_array[PARAMS] as $a => $b){
				if ('i' !== $inst_array[P_TYPE][$a]){

					if ((isset($opt[$a])) and ($opt[$a] < 1)){ // lea
						continue;
					}
					if ('r' === $inst_array[P_TYPE][$a]){
						if (in_array(Instruction::getGeneralRegIndex($b),self::$_stack_points_index_array)){
							return true;
						}
					}
					if ('m' === $inst_array[P_TYPE][$a]){
						if (preg_match('/'.self::$_stack_points_pattern.'/',$b)){
							return true;
						}
					}
				}
			}
		}
		return false;
	}
	// 栈指针寄存器写操作 判断
	protected static function isWriteEsp($id){
		if (isset(self::$_GPR_effects[$id]['ESP'])){
			if (in_array(W,self::$_GPR_effects[$id]['ESP'])){
				return true;
			}
			if (in_array(R|W,self::$_GPR_effects[$id]['ESP'])){
				return true;
			}
		}
		return false;
	}
	protected static function isIndependWriteSp($id){
		return isset(self::$_SP_writes[$id])?self::$_SP_writes[$id]:false;
	}
	public static function initIpsp(){
		$id = self::getBeginUnit();
		while ($id){
			if (!isset(self::$_unit_ipsp[$id])){
				self::$_unit_ipsp[$id] = self::is_effect_ipsp(self::getCode($id));
			}
			$id = self::next($id);
		}		
	}
	// 搜索目标单位rel属性，并reset之
	private static function seekResetRel($idx){
		if (isset(self::$_unit_inst[$idx][REL])){
			$tmp = self::$_unit_inst[$idx][REL];
			foreach ($tmp as $c_number => $c_rel_info){
				$old_rel_i = $c_rel_info['i'];
				$old_rel_c = $c_rel_info[C];

				$new = RelocInfo::cloneUnit($old_rel_i,$old_rel_c);
				if (false === $new){
					return false;
				}else{
					self::$_unit_inst[$idx][REL][$c_number][C] = $new;
			 		// self::$_unit_inst[$idx][PARAMS][$c_number] = str_replace(UNIQUEHEAD.'RELINFO_'.self::$_sec.'_'.$old_rel_i.'_'.$old_rel_c,UNIQUEHEAD.'RELINFO_'.self::$_sec.'_'.$old_rel_i.'_'.$new,self::$_unit_inst[$idx][PARAMS][$c_number]); 		
			 	}
			}
		}
		return true;
	}

	private static function isReverseJmp($id){
		if (isset(self::$_rel_jmp_range[$id])){ // include self is reverse JMP
			return in_array($id,self::$_rel_jmp_units[$id]);
		}
		return false;
	}
	public static function removeUsableMemByStack($id){
		GenerateFunc::doFilterMemUsable(self::$_unit_usable[$id][P][MEM_OPT_ABLE]);
		GenerateFunc::doFilterMemUsable(self::$_unit_usable[$id][N][MEM_OPT_ABLE]);
	}
	// public static function isUsableStack($id,$dir){
	// 	if (isset(self::$_unit_usable[$id][$dir][STACK])){
	// 		return self::$_unit_usable[$id][$dir][STACK];
	// 	}
	// 	return false;
	// }
	// public static function isUsableStackbyUnit($id){ // 判断单位是否栈可用
	// 	if ((self::isUsableStack($id,P)) or (self::isUsableStack($id,N))){
	// 		return true;
	// 	}
	// 	return false;
	// }
	public static function isValidUnit($id){
		if (!$id){return false;}
		return isset(self::$_unit_dlist[$id])?true:false;
	}			
	public static function isEffectStack($id){
		if (isset(self::$_unit_inst[$id][OPERATION])){
			return Instruction::isStackEffect(self::$_unit_inst[$id][OPERATION]);
		}
		return false;
	}
	public static function isIPSPUnit($id){
		return isset(self::$_unit_ipsp[$id])?self::$_unit_ipsp[$id]:false;
	}
	public static function isSPregParam($id,$p_num){ // 判断指定单位参数是否为sp寄存器
		if (isset(self::$_unit_inst[$id][P_TYPE][$p_num])){
			if ('r' === self::$_unit_inst[$id][P_TYPE][$p_num]){
				$c = Instruction::getGeneralRegIndex(self::$_unit_inst[$id][PARAMS][$p_num]);
				if (in_array($c, self::$_stack_points_index_array)){
					return true;
				}				
			}
		}
		return false;
	}
	private static function isSentinelUnit($id){
		return (false === $id)?true:false;
	}
	public static function isVirtUnit($id){
		return ((isset(self::$_unit_inst[$id])) and (isset(self::$_unit_inst[$id][VIRT_UNIT])));
	}
	private static function isSpGpr($gpr){
		if ($gprIdx = Instruction::getGeneralRegIndex($gpr)){
			if ($gprIdx === STACK_POINTER_REG){
				return true;
			}
		}
		return false;
	}
	// 前(后)是否可插入脂肪(fat)
	// params:  $unit  :  DList's unit
	// 			$direct:  1 prev   2 next
	public static function CheckFatAble($id,$direct){
		if (isset(self::$_unit_fat[$id][$direct])){
			if (true === self::$_unit_fat[$id][$direct]){
				return true;
			}
		}
		return false;			
	}

	// Rel Jmp function(s)
	// init rel jmp (only for Ready)
	public static function initRelJmp(){
		$c = self::getBeginUnit();
		while ($c){
			if ($c_label = self::getLabel($c)){
				self::$_rel_jmp_label[$c] = $c_label;
			}else{
				if ($c_array = self::getRelJmpInst($c)){
					self::$_rel_jmp_dst[$c] = false;
					self::$_rel_jmp_range[$c] = 0;
					if ($c_array[0]){
						self::$_rel_jmp_max[$c] = $c_array[0];
					}
					self::$_rel_jmp_units[$c] = array();
				}
			}
			$c = self::next($c);
		}

		foreach (self::$_rel_jmp_dst as $idx => $v){
			if (false === self::resetRelJmp($idx)){
				return false;
			}
		}
		return true;
	}
	public static function doRelJmpMatchAll(){
		if (in_array(false, self::$_rel_jmp_dst)){
			foreach (self::$_rel_jmp_dst as $jid => $value){
				if (!$value){
					if (!self::doRelJmpMatch($jid)){
						return false;
					}
				}
			}
		}
		return true;
	}
	// check Over Max only
	public static function resetRelJmp4OverMax(){
		if ((!empty(self::$_rel_jmp_expired)) and (!empty(self::$_rel_jmp_max))){
			$objs = array_intersect(self::$_rel_jmp_expired,array_keys(self::$_rel_jmp_max));
			foreach ($objs as $id){				
				if (true !== ($errcode = self::resetRelJmp($id))){
					GeneralFunc::LogInsert('fail to resetRelJmp() called by resetRelJmp4OverMax(), id:'.$id.', errcode:'.$errcode,NOTICE);
					return false;
				}else{
					unset(self::$_rel_jmp_expired[$id]);
				}
			}
		}
		return true;
	}
	protected static function resetRelJmpExpired(){		
		if ((!empty(self::$_rel_jmp_expired)) and (!empty(self::$_rel_jmp_dst))){
			$objs = array_intersect(self::$_rel_jmp_expired,array_keys(self::$_rel_jmp_dst));
			foreach ($objs as $id){				
				unset(self::$_rel_jmp_expired[$id]);
				if (true !== ($errcode = self::resetRelJmp($id))){
					GeneralFunc::LogInsert('fail to resetRelJmp() called by resetRelJmpExpired, id:'.$id.', errcode:'.$errcode,NOTICE);
					return false;
				}
			}
		}
		return true;
	}
	protected static function resetRelJmpRangeExpired(){
		while (!empty(self::$_rel_jmp_range_expired)){
			echo '<br>self::$_rel_jmp_range_expired:';
			var_dump(self::$_rel_jmp_range_expired);
			$tmp = self::$_rel_jmp_range_expired;
			foreach ($tmp as $a){
				$b = self::resetControlTransferInstLenAfterRangeChanged($a);
				if (true !== $b){
					return $b;
				}else{
					unset(self::$_rel_jmp_range_expired[$a]);
				}
			}
		}
		return true;
	}
	private static function removeRelJmpPosEffectsDo($uid,$id){
		if (isset(self::$_rel_jmp_pos_effects[$uid][P])){
			$i = array_search($id,self::$_rel_jmp_pos_effects[$uid][P]);
			if ((false !== $i) and (NULL !== $i)){
				unset (self::$_rel_jmp_pos_effects[$uid][P][$i]);
			}
		}
		if (isset(self::$_rel_jmp_pos_effects[$uid][N])){
			$i = array_search($id,self::$_rel_jmp_pos_effects[$uid][N]);
			if ((false !== $i) and (NULL !== $i)){
				unset (self::$_rel_jmp_pos_effects[$uid][N][$i]);
			}
		}
	}
	private static function removeRelJmpPosEffects($id){
		self::removeRelJmpPosEffectsDo($id,$id);
		if (self::$_rel_jmp_dst[$id]){
			self::removeRelJmpPosEffectsDo(self::$_rel_jmp_dst[$id],$id);
		}
		if (isset(self::$_rel_jmp_units[$id])){			
			foreach (self::$_rel_jmp_units[$id] as $uid){
				self::removeRelJmpPosEffectsDo($uid,$id);
			}
		}
	}
	private static function initRelJmpPosEffects($id){
		if (isset(self::$_rel_jmp_units[$id])){
			$tmp = self::$_rel_jmp_units[$id];
			$first_unit = array_shift($tmp);
			if ($first_unit !== self::$_rel_jmp_dst[$id]){ // first unit is not label
				self::$_rel_jmp_pos_effects[$first_unit][P][] = $id;				
			}
			self::$_rel_jmp_pos_effects[$first_unit][N][] = $id;
			foreach ($tmp as $uid){
				self::$_rel_jmp_pos_effects[$uid][P][] = $id;
				if ($uid === self::$_rel_jmp_dst[$id]){ // last one is label
					self::$_rel_jmp_pos_effects[$id][N][] = $id;
					break;
				}elseif ($uid === $id){ // last one is jcc
					break;
				}
				self::$_rel_jmp_pos_effects[$uid][N][] = $id;
			}
		}
	}
	private static function doRelJmpMatch($id){
		$c_label = self::$_unit_inst[$id][PARAMS][0];
		$c_match_label = array_search($c_label, self::$_rel_jmp_label);
		if (false === $c_match_label){
			return false;
		}else{
			if (false !== array_search($c_match_label,self::$_rel_jmp_dst)){ // need clone
				$c_match_label = self::cloneLabel($c_match_label);
				self::$_unit_inst[$id][PARAMS][0] .= '_CLONE_'.$c_match_label;
			}
			self::$_rel_jmp_dst[$id] = $c_match_label;
		}
		return true;
	}
	private static function resetRelJmp($id){
		self::removeRelJmpPosEffects($id); // remove all $_rel_jmp_pos_effects of $id		
		if (!self::$_rel_jmp_dst[$id]){    // seek the match Label
			if (!self::doRelJmpMatch($id)){
				return self::$_FAIL_MATCH_LABEL;
			}
		}
		// collect all units
		self::$_rel_jmp_units[$id] = array();
		$c_units = self::getAmongUnits($id,self::$_rel_jmp_dst[$id]); // heavy function
		if (!$c_units){
			return self::$_FAIL_GET_UNITS;
		}else{
			if ($id === reset($c_units)){ // jmp before label then ignore itself
				array_shift($c_units);
			}
			self::$_rel_jmp_units[$id] = $c_units;
		}
		// init Pos effects
		self::initRelJmpPosEffects($id);

		return self::resetControlTransferInstLenAfterRangeChanged($id);
	}
	private static function resetControlTransferInstLenAfterRangeChanged($id){
		// calculate total range
		self::$_rel_jmp_range[$id] = 0;
		foreach (self::$_rel_jmp_units[$id] as $mid){
			self::$_rel_jmp_range[$id] += self::getLen($mid);
		}		
		if ((isset(self::$_rel_jmp_max[$id]))and(self::$_rel_jmp_range[$id] > self::$_rel_jmp_max[$id])){ // overflow
			return self::$_FAIL_OVER_RANGE;
		}
		self::getLen($id,true); // re-get inst len
		return true;
	}
	private static function setRelJmpExpired($id,$dir){
		if (self::isValidUnit($id)){
			if (isset(self::$_rel_jmp_pos_effects[$id][$dir])){
				foreach (self::$_rel_jmp_pos_effects[$id][$dir] as $jid){
					self::$_rel_jmp_expired[$jid] = $jid;
				}
			}
		}
	}
	private static function allRelJmpExpired(){
		foreach (self::$_rel_jmp_dst as $jid => $dst){
			self::$_rel_jmp_expired[$jid] = $jid;
		}
	}
	// flush $_rel_jmp
	private static function flushRelJmp($id){
		self::removeRelJmpPosEffects($id);
		unset (self::$_rel_jmp_dst[$id]);
		unset (self::$_rel_jmp_range[$id]);
		if (isset(self::$_rel_jmp_max[$id])){
			unset (self::$_rel_jmp_max[$id]);
		}
		if (isset(self::$_rel_jmp_expired[$id])){
			unset (self::$_rel_jmp_expired[$id]);
		}
		unset (self::$_rel_jmp_units[$id]);
	}	
	// show TODO: delete
	// public static function show(){
	// 	$c = self::getBeginUnit();
	// 	echo '<table border=1>';
	// 	echo '<tr><td>No.</td>';
	// 	echo '<td>prev usable</td><td>prev forbid</td><td>asm</td><td>mem</td><td>len</td><td>GPR effects</td><td>next forbid</td><td>next usable</td><td>RelJmpArray</td><td>RelJmpEffect(<font color=blue>Prev</font> <font color=red>Next</font>)</td></tr>';
	// 	while ($c){
	// 		echo '<tr>';
	// 		echo '<td>'."$c".'</td>';			
	// 		$c_usable = self::getUsable($c);
	// 		$c_inst   = self::getCode($c);
	// 		$c_forbid = self::getForbid($c);
	// 		$c_GPR_effects = self::getGPReffects($c);
	// 		echo '<td>';
	// 		if (isset($c_usable[P])){
	// 			var_dump ($c_usable[P]);
	// 		}
	// 		echo '</td>';
	// 		echo '<td>';
	// 		var_dump ($c_forbid[P]);
	// 		echo '</td>';
	// 		echo '<td>';
	// 		echo 'Comment: ';
	// 		echo '[<font color = blue>';
	// 		echo self::echoComment($c);
	// 		echo '</font>]';
	// 		echo '<br>';
	// 		var_dump ($c_inst);
	// 		echo '</td>';
	// 		echo '<td>';
	// 		if (isset($c_inst[P_M_REG])){
	// 			var_dump(self::$_mem_operand_map[$c_inst[P_M_REG]]);
	// 		}
	// 		echo '</td>';
	// 		$c_len = self::getLen($c);
	// 		echo '<td>'.$c_len.'</td>';
	// 		echo '<td>';
	// 		var_dump ($c_GPR_effects);
	// 		echo '</td>';
	// 		echo '<td>';
	// 		if (isset($c_forbid[N])){
	// 			var_dump ($c_forbid[N]);
	// 		}
	// 		echo '</td>';
	// 		echo '<td>';
	// 		if (isset($c_usable[N])){
	// 			var_dump ($c_usable[N]);
	// 		}
	// 		echo '</td>';
	// 		echo '<td>';
	// 		if (isset(self::$_rel_jmp_dst[$c])){
	// 			echo '<br>$_rel_jmp_dst: ';
	// 			if (false === self::$_rel_jmp_dst[$c]){
	// 				echo '<font color=red><b>FALSE</b></font>';
	// 			}else{
	// 				echo self::$_rel_jmp_dst[$c];
	// 			}
	// 			echo '<br>$_rel_jmp_range: '.self::$_rel_jmp_range[$c];
	// 			echo '<br>$_rel_jmp_max: ';
	// 			if (isset(self::$_rel_jmp_max[$c])){
	// 				echo self::$_rel_jmp_max[$c];
	// 			}else{
	// 				echo 'Null';
	// 			}
	// 			echo '<br>$_rel_jmp_units: ';
	// 			var_dump (self::$_rel_jmp_units[$c]);
	// 		}else{
	// 			echo '-';
	// 		}
	// 		echo '</td>';
	// 		echo '<td>';
	// 		if (isset(self::$_rel_jmp_pos_effects[$c][P])){
	// 			echo '<font color=blue>Prev:';
	// 			var_dump (self::$_rel_jmp_pos_effects[$c][P]);
	// 			echo '</font>';
	// 		}
	// 		if (isset(self::$_rel_jmp_pos_effects[$c][N])){
	// 			echo '<font color=red>Next:';
	// 			var_dump (self::$_rel_jmp_pos_effects[$c][N]);
	// 			echo '</font>';
	// 		}
	// 		echo '</td>';
	// 		echo '</tr>';			
	// 		$c = self::next($c);
	// 	}
	// 	echo '</table>';
	// }

	/////////////////////////////////////////////////////////////////////////
	// all protected attribut function is for Organ Wrapper

	// create a new 
	protected static function Physic_create(){
		self::flushAll();
		// self::$_sec = $sec;
	}
	// reloc jmp array
	protected static function Physic_relocJmpVector($idx,$data,$type){
		if (1 == $type){
			if (0 === self::$_unit_inst[$idx][IMM_IS_LABEL]){
				self::$_unit_inst[$idx][IMM_IS_LABEL] = $data;
				self::$_rel_jmp_dst[$idx] = $data;
				if ($a = Instruction::getJmpRangeLmt(self::$_unit_inst[$idx][INST])){
					self::$_rel_jmp_max[$idx] = $a;
				}
				self::$_rel_jmp_range[$idx] = 0;
				self::$_rel_jmp_units[$idx] = array();
				self::$_rel_jmp_expired[$idx] = $idx;
			}else{
				return false;
			}
		}else{
			if (0 === self::$_unit_inst[$idx][LABEL_FROM]){
				self::$_unit_inst[$idx][LABEL_FROM] = $data;
			}else{
				return false;
			}
		}
		return true;
	}
	// mem 参数 归类索引
	protected static function Physic_replaceMem($unit){
		return self::Physic_new_MemOperation($unit[P_M_REG]);
	}
	private static function Physic_new_MemOperation($array){
		$idx = array_search($array, self::$_mem_operand_map);
		if (false === $idx){
			self::$_mem_operand_map[] = $array;
			return count(self::$_mem_operand_map) - 1;
		}
		return $idx;
	}
	// reloc map
	protected static function Physic_RelocMap_add($relocArr){
		$idx = array_search($relocArr, self::$_reloc_map);
		if (false === $idx){
			self::$_reloc_map[] = $relocArr;
			return count(self::$_reloc_map) - 1;
		}
		return $idx;
	}
	protected static function Physic_Insert($unit,$pos,$comment){
		$idx = self::$_unit_insert_index;
		self::$_unit_insert_index ++;
		
		self::$_unit_inst[$idx] = $unit;

		if (!self::insertUnit($pos,$idx)){
			return false;
		}
		self::Physic_Init_Inst_Effects($idx);
		if (TRACK_COMMENT_ON){
			self::setComment($idx,$comment);
		}
		return $idx;
	}
	// match len predictd with original length only avalid in ready process
	protected static function Physic_recheck_len($id,$sec){
		$ret = false;
		$c_inst = self::getCode($id);
		if (isset($c_inst[ORIGINAL_LEN])){
			if (isset(self::$_unit_inst_len[$id])){
				if (self::$_unit_inst_len[$id] !== $c_inst[ORIGINAL_LEN]){
					GeneralFunc::LogInsert('Diff len between predicted and original,['.self::$_unit_inst_len[$id].'!='.$c_inst[ORIGINAL_LEN].'], sec: '.$sec.', id: '.$id.', ['.$c_inst[INST].'],', NOTICE);
					$ret = self::$_unit_inst_len[$id];
					self::setInstLength($id,$c_inst[ORIGINAL_LEN]);
				}
			}
		}
		return $ret;
	}
	// init inst's effects
	private static function Physic_Init_Inst_Effects($id){
		$c_inst = self::getCode($id);
		if (isset($c_inst[INST])){
			$c_operands_num = 0;
			$c_special_operand_flag = false;
			if (isset($c_inst[OPERAND])){
				$c_operands_num = count($c_inst[OPERAND]);
				if (in_array(T_ORS, $c_inst[P_TYPE])){
					$c_special_operand_flag = true;
				}
			}
			$inst_effects = Instruction::getInstructionOpt($c_inst[INST],$c_operands_num,$c_special_operand_flag);
			foreach ($inst_effects as $key => $value) {
				if (('SRC_MEM' === $key) or ('DST_MEM' === $key)){
					$c_reg = ('SRC_MEM' === $key)?'ESI':'EDI';
					$c_addr_bits = isset($c_inst[PREFIX]['a_resize'])?Instruction::getCurrentBits(true):OPT_BITS;
					$c_mem_con = array(SIB_BASE => $c_reg, ADDR_BITS => $c_addr_bits, OP_BITS => $value[0]);
					$mid = self::Physic_new_MemOperation($c_mem_con);
					self::Physic_new_effects(T_MEM,$id,$mid,$value[1]);
				}elseif (Instruction::isEflag($key)){
					self::Physic_new_effects(T_EFS,$id,$key,$value);
				}elseif (Instruction::getGeneralRegIndex($key)){
					self::Physic_new_effects(T_GPR,$id,$key,$value);
				}elseif (STACK === $key){
					self::$_STACK_effects[$id] = $value;
				}elseif (is_int($key)){ // operand
					if ($c_operands_num > 0){
						if (isset($c_inst[OPERAND][$key+1])){
							if (T_MEM === $c_inst[P_TYPE][$key+1]){
								self::Physic_new_effects($c_inst[P_TYPE][$key+1],$id,self::$_unit_inst[$id][OPERAND][$key+1],$value);
							}else{
								self::Physic_new_effects($c_inst[P_TYPE][$key+1],$id,$c_inst[OPERAND][$key+1],$value);
								if ((T_GPR === $c_inst[P_TYPE][$key+1])and($value & W)){
									if (self::isSpGpr($c_inst[OPERAND][$key+1])){
										self::$_SP_writes[$id] = true;
									}
								}
							}
						}
					}
				}
			}
		}
		if (isset($c_inst[PREFIX])){
			if ($c_prefix = array_search(PREFIX_GROUP_1, $c_inst[PREFIX])){
				if ($c_effects = Instruction::getInstructionOpt($c_prefix,0)){
					foreach ($c_effects as $key => $value){
						if (Instruction::isEflag($key)){
							self::Physic_new_effects(T_EFS,$id,$key,$value);
						}elseif (Instruction::getGeneralRegIndex($key)){
							self::Physic_new_effects(T_GPR,$id,$key,$value);
						}
					}
				}
			}
		}
	}
	//
	private static function Physic_new_effects($type,$id,$key,$opt){
		if (T_GPR === $type){
			$reg_idx = Instruction::getGeneralRegIndex($key);
			$reg_bits = Instruction::getGeneralRegBits($key,true);
			if (isset(self::$_GPR_effects[$id][$reg_idx][$reg_bits])){
				$opt |= self::$_GPR_effects[$id][$reg_idx][$reg_bits];
			}
			self::$_GPR_effects[$id][$reg_idx][$reg_bits] = $opt;
		}elseif (T_MEM === $type){
			if ($c_mem_operand = self::getMemOperandArr($key)){
				if (isset($c_mem_operand[SIB_BASE])){
					self::Physic_new_effects(T_GPR,$id,$c_mem_operand[SIB_BASE],R);
				}
				if (isset($c_mem_operand[SIB_INDEX])){
					self::Physic_new_effects(T_GPR,$id,$c_mem_operand[SIB_INDEX],R);
				}
			}			
			if (isset(self::$_MEM_effects[$id][$key])){
				$opt |= self::$_MEM_effects[$id][$key];
			}
			self::$_MEM_effects[$id][$key] = $opt;
		}elseif (T_EFS === $type){
			if (isset(self::$_EFLAGS_effects[$id][$key])){
				$opt |= self::$_EFLAGS_effects[$id][$key];
			}
			self::$_EFLAGS_effects[$id][$key] = $opt;
		}
	}
}