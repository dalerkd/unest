<?php

class OrgansOperator{

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
	private static $_GPR_effects; // general purpose register

	private static $_rel_jmp_dst;
	private static $_rel_jmp_range;
	private static $_rel_jmp_max;
	private static $_rel_jmp_units;
	private static $_rel_jmp_pos_effects;
	private static $_rel_jmp_label;
	private static $_rel_jmp_expired; // wait to reset

	private static $_sec;

	// independent data (without rollback)
	private static $_data4rollback;
	private static $_stack_points_pattern;
	private static $_stack_points_array;
	private static $_stack_points_index_array;

	// const
	const FAIL_MATCH_LABEL = -1;
	const FAIL_GET_UNITS   = -2;
	const FAIL_OVER_RANGE  = -3;

	// Export & Import
	public static function export(){
		return serialize(array(
			self::$_unit_begin_index,
			self::$_unit_insert_index,
			self::$_unit_dlist,
			self::$_unit_inst,
			self::$_unit_usable,
			self::$_unit_forbid,
			self::$_unit_fat,
			self::$_unit_inst_len,
			self::$_GPR_effects,
			self::$_unit_ipsp,
			self::$_unit_comment,
			self::$_rel_jmp_dst,
			self::$_rel_jmp_range,
			self::$_rel_jmp_max,
			self::$_rel_jmp_units,
			self::$_rel_jmp_pos_effects,
			self::$_rel_jmp_label,
			self::$_sec,
			self::$_rel_jmp_expired,
		));		
	}
	public static function import($str){
		self::flush4Rollback();
		$tmp = unserialize($str);
		self::$_unit_begin_index = $tmp[0];
		self::$_unit_insert_index = $tmp[1];
		self::$_unit_dlist = $tmp[2];
		self::$_unit_inst = $tmp[3];
		self::$_unit_usable = $tmp[4];
		self::$_unit_forbid = $tmp[5];
		self::$_unit_fat = $tmp[6];
		self::$_unit_inst_len = $tmp[7];
		self::$_GPR_effects  = $tmp[8];
		self::$_unit_ipsp    = $tmp[9];
		self::$_unit_comment = $tmp[10];
		self::$_rel_jmp_dst  = $tmp[11];
		self::$_rel_jmp_range = $tmp[12];
		self::$_rel_jmp_max = $tmp[13];
		self::$_rel_jmp_units = $tmp[14];
		self::$_rel_jmp_pos_effects = $tmp[15];
		self::$_rel_jmp_label = $tmp[16];
		self::$_sec = $tmp[17];
		self::$_rel_jmp_expired = $tmp[18];
	}
	// flush for RollBack
	private static function flush4Rollback(){
		self::$_unit_begin_index = 0;
		self::$_unit_insert_index = 0;
		self::$_unit_dlist  = array();

		self::$_unit_inst   = array();
		self::$_unit_usable = array();
		self::$_unit_forbid = array();
		self::$_unit_fat    = array();
		self::$_unit_inst_len = array();
		self::$_GPR_effects  = array();
		self::$_unit_ipsp    = array();
		self::$_unit_comment = array();
		self::$_rel_jmp_dst = array();
		self::$_rel_jmp_range = array();
		self::$_rel_jmp_max = array();
		self::$_rel_jmp_units = array();
		self::$_rel_jmp_pos_effects = array();
		self::$_rel_jmp_label = array();

		self::$_rel_jmp_expired = array();

	    self::$_sec = 0;
        
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
    public static function init($sec,$sl_dlist,$sl_inst,$sl_usable,$sl_forbid,$sl_len,$sl_GPR_effects){
    	self::flushAll();

        self::$_unit_inst     = $sl_inst;       
		self::$_unit_usable   = $sl_usable;
		self::$_unit_forbid   = $sl_forbid;
		self::$_unit_inst_len = $sl_len;
		self::$_unit_dlist    = $sl_dlist;
		self::$_GPR_effects   = $sl_GPR_effects;

		self::$_unit_begin_index = DEFAULT_DLIST_FIRST_NUM;
		self::$_unit_insert_index = 1 + max(array_keys(self::$_unit_inst));

		self::$_sec = $sec;

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
	// get method(s)
	public static function getBeginUnit(){
		return self::$_unit_begin_index;
	}
	public static function getCode($id){
		return isset(self::$_unit_inst[$id])?self::$_unit_inst[$id]:false;
	}
	public static function getLen($id){ // TODO: 跳转指令range改变会改变指令长度
		if (self::isValidUnit($id)){
			if (!isset(self::$_unit_inst_len[$id])){
				$c_len = OpLen::code_len(self::getCode($id),false,self::getRelJmpRange($id));
				self::$_unit_inst_len[$id] = $c_len;
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

	// set method(s)
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
	public static function setGPReffects($id,$reg,$bits,$perm){
		if (isset(self::$_GPR_effects[$id][$reg][$bits])){
			self::$_GPR_effects[$id][$reg][$bits] |= $perm;
		}else{
			self::$_GPR_effects[$id][$reg][$bits] = $perm;
		}		
	}
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
	public static function removeDLink($id){
		if (self::isValidUnit($id)){
			self::reDlink(self::prev($id),self::next($id));
			self::setRelJmpExpired($id,P);
			self::setRelJmpExpired($id,N);
			Character::removeRate($id); //清character.Rate
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
			 		self::$_unit_inst[$idx][PARAMS][$c_number] = str_replace(UNIQUEHEAD.'RELINFO_'.self::$_sec.'_'.$old_rel_i.'_'.$old_rel_c,UNIQUEHEAD.'RELINFO_'.self::$_sec.'_'.$old_rel_i.'_'.$new,self::$_unit_inst[$idx][PARAMS][$c_number]); 		
			 	}
			}
		}
		return true;
	}
	// init single units' general purpose register effects
	// TODO
	// private static function initGPReffects($id,$inst_array){
	// 	if (isset($inst_array[PREFIX])){
	// 		foreach ($inst_array[PREFIX] as $a){
	// 			Instruction::getInstructionOpt($a);
	// 		}
	// 	}
	// 	// if (isset($inst_array[OPERATION])){

	// 	// }
	// 	// if (isset())

	// 	// self::$_GPR_effects[$id][reg][bits] = 1|2|3
	// }

	public static function removeUsableMemByStack($id){
		GenerateFunc::doFilterMemUsable(self::$_unit_usable[$id][P][MEM_OPT_ABLE]);
		GenerateFunc::doFilterMemUsable(self::$_unit_usable[$id][N][MEM_OPT_ABLE]);
	}
	public static function isUsableStack($id,$dir){
		if (isset(self::$_unit_usable[$id][$dir][STACK])){
			return self::$_unit_usable[$id][$dir][STACK];
		}
		return false;
	}
	public static function isUsableStackbyUnit($id){ // 判断单位是否栈可用
		if ((self::isUsableStack($id,P)) or (self::isUsableStack($id,N))){
			return true;
		}
		return false;
	}
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
	private static function resetRelJmpExpired(){
		if ((!empty(self::$_rel_jmp_expired)) and (!empty(self::$_rel_jmp_dst))){
			$objs = array_intersect(self::$_rel_jmp_expired,array_keys(self::$_rel_jmp_dst));
			foreach ($objs as $id){
				if (true !== ($errcode = self::resetRelJmp($id))){
					GeneralFunc::LogInsert('fail to resetRelJmp() called by resetRelJmpExpired, id:'.$id.', errcode:'.$errcode,NOTICE);
					return false;
				}else{
					unset(self::$_rel_jmp_expired[$id]);
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
		self::removeLen($id); // any changed will effect jcc inst length
		if (!self::$_rel_jmp_dst[$id]){ // seek the match Label
			if (!self::doRelJmpMatch($id)){
				return self::FAIL_MATCH_LABEL;
			}
		}
		// collect all units
		self::$_rel_jmp_units[$id] = array();
		$c_units = self::getAmongUnits($id,self::$_rel_jmp_dst[$id]);
		if (!$c_units){
			return self::FAIL_GET_UNITS;
		}else{
			if ($id === reset($c_units)){ // jmp before label then ignore itself
				array_shift($c_units);
			}
			self::$_rel_jmp_units[$id] = $c_units;
		}
		// init Pos effects
		self::initRelJmpPosEffects($id);
		// calcuate total range
		self::$_rel_jmp_range[$id] = 0;
		foreach ($c_units as $mid){
			self::$_rel_jmp_range[$id] += self::getLen($mid);
		}
		if ((isset(self::$_rel_jmp_max[$id]))and(self::$_rel_jmp_range[$id] > self::$_rel_jmp_max[$id])){ // overflow
			return self::FAIL_OVER_RANGE;
		}
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
	// show
	public static function show(){
		$c = self::getBeginUnit();
		echo '<table border=1>';
		echo '<tr><td>No.</td>';
		echo '<td>prev usable</td><td>prev forbid</td><td>asm</td><td>len</td><td>GPR effects</td><td>next forbid</td><td>next usable</td><td>RelJmpArray</td><td>RelJmpEffect(<font color=blue>Prev</font> <font color=red>Next</font>)</td></tr>';
		while ($c){
			echo '<tr>';
			echo '<td>'."$c".'</td>';			
			$c_usable = self::getUsable($c);
			$c_inst   = self::getCode($c);
			$c_forbid = self::getForbid($c);
			$c_GPR_effects = self::getGPReffects($c);
			echo '<td>';
			if (isset($c_usable[P])){
				var_dump ($c_usable[P]);
			}
			echo '</td>';
			echo '<td>';
			var_dump ($c_forbid[P]);
			echo '</td>';
			echo '<td>';
			echo 'Comment: ';
			echo '[<font color = blue>';
			echo self::echoComment($c);
			echo '</font>]';
			echo '<br>';
			var_dump ($c_inst);
			echo '</td>';
			$c_len = self::getLen($c);
			echo '<td>'.$c_len.'</td>';
			echo '<td>';
			var_dump ($c_GPR_effects);
			echo '</td>';
			echo '<td>';
			if (isset($c_forbid[N])){
				var_dump ($c_forbid[N]);
			}
			echo '</td>';
			echo '<td>';
			if (isset($c_usable[N])){
				var_dump ($c_usable[N]);
			}
			echo '</td>';
			echo '<td>';
			if (isset(self::$_rel_jmp_dst[$c])){
				echo '<br>$_rel_jmp_dst: ';
				if (false === self::$_rel_jmp_dst[$c]){
					echo '<font color=red><b>FALSE</b></font>';
				}else{
					echo self::$_rel_jmp_dst[$c];
				}
				echo '<br>$_rel_jmp_range: '.self::$_rel_jmp_range[$c];
				echo '<br>$_rel_jmp_max: ';
				if (isset(self::$_rel_jmp_max[$c])){
					echo self::$_rel_jmp_max[$c];
				}else{
					echo 'Null';
				}
				echo '<br>$_rel_jmp_units: ';
				var_dump (self::$_rel_jmp_units[$c]);
			}else{
				echo '-';
			}
			echo '</td>';
			echo '<td>';
			if (isset(self::$_rel_jmp_pos_effects[$c][P])){
				echo '<font color=blue>Prev:';
				var_dump (self::$_rel_jmp_pos_effects[$c][P]);
				echo '</font>';
			}
			if (isset(self::$_rel_jmp_pos_effects[$c][N])){
				echo '<font color=red>Next:';
				var_dump (self::$_rel_jmp_pos_effects[$c][N]);
				echo '</font>';
			}
			echo '</td>';
			echo '</tr>';			
			$c = self::next($c);
		}
		echo '</table>';
	}
}

?>