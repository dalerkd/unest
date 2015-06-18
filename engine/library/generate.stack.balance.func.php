<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

class StackBalance{
	private static $_obj_pool;
	// [dlist id][reg][bits] = write | read
	private static $_GPR_effects;
	// $table_1 = array(); // [dlist_id][dir]  = universe_id
	private static $_table_1;
	// $table_2 = array(); // [universe_id][0] = dlist_id
	//					   // [universe_id][1] = direction
	private static $_table_2;
	// no stack usable univers unit, cant insert pair(push/pop)
	private static $_ill_unit;
	// 插入位 (对)
	private static $_pairs;
	// 待处理 ['mono'] = array(
	//		   'dim' = ..., // 维度，未限定为unset
	//         'lhs' = ., // 左侧可用起始单位(含)
	//         'rhs' = ,  // 右侧可用起始单位(含)
	//         'lhs_ins_pos' = ., // 左侧插入单位 ，未限定为unset
	//         'rhs_ins_pos' = ., // 右侧插入单位 ，未限定为unset
	//)
	private static $_work_buff;
	// 已处理完成 [mono id] = mono id
	private static $_work_buff_completed_mono;
	// [mono id][unit id] = queue number
	private static $_work_queue_cache;
	// available ? conflict? false this flag
	private static $_work_alive;
	private static $_work_ignore_forbid;


	const GAP_BITS = 4; // n 字节 (落差)

	// 导入2个指针表
	private static function import($a){
		self::$_table_1 = $a['table_1'];
		self::$_table_2 = $a['table_2'];
	}
	// 
	private static function init(){
		self::$_obj_pool = array();
		self::$_table_1  = array();
		self::$_table_2  = array();
		self::$_ill_unit = array();
		self::$_pairs    = array();
	}
	//
	private static function initwork(){
		self::$_work_buff = array();
		self::$_work_buff_completed_mono = array();
		self::$_work_queue_cache = array();
		self::$_work_alive = true;
		self::$_work_ignore_forbid = false;
	}
	// 	生成可用目标池
	private static function genpool($units,$charates){
		// var_dump ($units);
		// var_dump ($charates);
		self::$_obj_pool = $units;
		foreach ($charates as $a){
			foreach ($a as $b){
				foreach ($b as $c){
					self::$_obj_pool[] = $c;
				}
			}
		}
	}
	// 生成由于stack禁用而不能作为插入点的 universe units
	private static function genIllUnit($units){
		foreach ($units as $a){
			$b = ConstructionDlinkedListOpt::getUnit($a);
			$c = OrgansOperator::GetByDListUnit($b,USABLE);
			if (!isset($c[P][STACK]) or (true !== $c[P][STACK])){
				if (isset(self::$_table_1[$a][P])){
					$i = self::$_table_1[$a][P];
					self::$_ill_unit[$i] = $i;
				}
			}
			if (!isset($c[N][STACK]) or (true !== $c[N][STACK])){
				if (isset(self::$_table_1[$a][N])){
					$i = self::$_table_1[$a][N];
					self::$_ill_unit[$i] = $i;
				}
			}
		}
	}
	// 获取目标单位，返回stero.graphic 的 mono id & unit id
	private static function getObj(&$mono,&$unit_1,&$unit_2){
		$a = GeneralFunc::my_array_rand(self::$_obj_pool);
		$b = self::$_obj_pool[$a];		
		// prev & next of c unit
		if ((isset(self::$_table_1[$b][P])) and (isset(self::$_table_1[$b][N]))){
			$j = SteroGraphic::get_unit_mono_ids(self::$_table_1[$b][P]);
			$k = SteroGraphic::get_unit_mono_ids(self::$_table_1[$b][N]);
			$z = array_intersect($j, $k);
			if (!empty($z)){
				$mono = reset($z);
				$unit_1 = self::$_table_1[$b][P];
				$unit_2 = self::$_table_1[$b][N];
				return true;
			}
		}
		return false;
	}
	// 搜集方向(Prev or Next)上有可插入维度(目前最低维)包含的所有单位
	private static function collectValidDimUnits($mono,$c_unit,$dimRoof,$direction){
		$ret = array();
		$include_self = true;
		while (true){
			if ($include_self){
				$include_self = false;
			}else{
				if (P === $direction){
					$c_unit = SteroGraphic::get_prev_unit($mono,$c_unit);
				}else{
					$c_unit = SteroGraphic::get_next_unit($mono,$c_unit);
				}
			}
			$c_dim = SteroGraphic::get_dim($c_unit);
			if (false === $c_dim){
				break;
			}			
			if (isset(self::$_ill_unit[$c_uint])){
				continue;
			}
			if ($dimRoof >= $c_dim){
				$dimRoof = $c_dim;
				$ret[$c_dim][] = $c_unit;
			}
		}
		return $ret;
	}	
	// 搜集方向(Prev or Next)上指定维度的所有单位(except Block mono)
	private static function collectValidUnit($mono,$direction){
		if (P === $direction){
			$c_unit = self::$_work_buff[$mono]['lhs'];			
		}else{
			$c_unit = self::$_work_buff[$mono]['rhs'];
		}
		// echo "<br>$$$$$$$$$$$$$$$$$$<br>start to collectValidUnit: $mono . $c_unit";
		$dim = self::$_work_buff[$mono]['dim'];
		$validUnits = array();
		$break = false;	
		while (0 !== $c_unit){
			// echo "<br>c_unit : $c_unit";			
			/* ILLEGAL */
			if (!isset(self::$_ill_unit[$c_uint])){
				/* DIM */
				$c_dim = SteroGraphic::get_dim($c_unit);
				if (false === $c_dim){
					break;
				}	
				if ($c_dim < $dim){ // readme.stack.balance [3.0]
					// echo " - break!!! $c_unit: c_dim: $c_dim < dim: $dim";
					break;
				}
				if ($c_dim !== $dim){ // readme.stack.balance [3.0]
					// echo ' - not same dim...continue.';
				}else{
					/* EFFECTS */
					$c_effects_mono = SteroGraphic::get_unit_mono_ids($c_unit);	

					$valid = true;
					// 影响其它mono id: readme.stack.balance.txt [step.4.0]
					if (1 < count($c_effects_mono)){
						echo '<br><font color =red><b>multi mono effects:</b>';
						foreach ($c_effects_mono as $multi_mono){
							if ($multi_mono !== $mono){
								// echo "<br>mono id: $multi_mono";
								if (isset(self::$_work_buff[$multi_mono])){
									// readme.stack.balcne [4.1]
									if (P === $direction){
										if (isset(self::$_work_buff[$multi_mono]['lhs_ins_pos'])){
											$break = true;
											break;
										}
									}else{
										if (isset(self::$_work_buff[$multi_mono]['rhs_ins_pos'])){
											$break = true;
											break;
										}
									}
									// readme.stack.balance [4.2]
									if (isset(self::$_work_buff[$multi_mono]['dim'])){
										if ($c_dim !== self::$_work_buff[$multi_mono]['dim']){
											// echo ' - dim conflict!';
											$valid = false;
											break;
										}
									}
									// readme.stack.balance [4.2]
									if (self::isConflictInsertMono($multi_mono,$c_unit,$direction)){
										// echo ' - unit conflict!';
										$valid = false;
										break;
									}
								}
							}
						}
						// echo '</font><br>';
					}
					if ($break){
						break;
					}
					/* COMPLETED */ 
					if ($valid){
						// echo ' - valid';
						$validUnits[$c_unit] = $c_unit;
					}
				}
			}
			if (P === $direction){
				$c_unit = SteroGraphic::get_prev_unit($mono,$c_unit);
			}else{
				$c_unit = SteroGraphic::get_next_unit($mono,$c_unit);
			}		
		}
		if (!empty($validUnits)){
			$o = GeneralFunc::my_array_rand($validUnits);
			// echo "<br><font color = red>mono: $mono , direction: $direction , ins_point: $o dim: $dim</font>";
			// var_dump ($validUnits);
			return self::insertPosition($o,$direction);
		}else{
			echo '<font color =red><br> no any unit is valid!</font>';
		}
		return false;
	}
	// 插入 位置 (插入前已判断可用)
	private static function insertPosition($pos,$direction){
		$effect_monos = SteroGraphic::get_unit_mono_ids($pos);
		if ($effect_monos){
			$dim = SteroGraphic::get_dim($pos);
			foreach ($effect_monos as $mono){
				self::$_work_buff[$mono]['dim'] = $dim;
				if (P === $direction){					
					self::$_work_buff[$mono]['lhs'] = $pos;
					self::$_work_buff[$mono]['lhs_ins_pos'] = $pos;
				}else{
					self::$_work_buff[$mono]['rhs'] = $pos;
					self::$_work_buff[$mono]['rhs_ins_pos'] = $pos;
				}
			}
			return true;
		}
		return false;
	}
	// 是否冲突 预插入位和已存在的影响范围 (预先插入位必须位于范围边界或之外)
	// true : 冲突,不可用
	// false: 可用
    private static function isConflictInsertMono($mono,$c_unit,$direction){
    	$ret = true;
    	if ((!isset(self::$_work_buff[$mono]['lhs'])) and (!isset(self::$_work_buff[$mono]['rhs']))){
    		return false;
    	}
    	if (P === $direction){
    		if (isset(self::$_work_buff[$mono]['lhs'])){
    			$c = self::$_work_buff[$mono]['lhs'];
    		}else{
    			$c = SteroGraphic::get_prev_unit($mono,self::$_work_buff[$mono]['rhs']);
    		}
    	}else{
    		if (isset(self::$_work_buff[$mono]['rhs'])){
    			$c = self::$_work_buff[$mono]['rhs'];
    		}else{
    			$c = SteroGraphic::get_next_unit($mono,self::$_work_buff[$mono]['lhs']);
    		}
    	}    	
    	while (0 !== $c){
    		if ($c_unit === $c){
    			$ret = false;
    			break;
    		}
    		if (P === $direction){
    			$c = SteroGraphic::get_prev_unit($mono,$c);
    		}else{
    			$c = SteroGraphic::get_next_unit($mono,$c);
    		}
    	}
    	return $ret;
    }
    // get dim roof between two units
    private static function getDimRoof($mono,$lhs,$rhs){
    	$dimRoof = false;
    	$c_unit = $lhs;
    	while (true){
    	  	if ($c_dim = SteroGraphic::get_dim($c_unit)){
    	  		if ((!$dimRoof) or ($dimRoof > $c_dim)){
    	  			$dimRoof = $c_dim;
    	  		}
    	  	}else{
    	  		$dimRoof = false;
    	  		break;
    	  	}
    	  	if ($c_unit == $rhs){
    	  		break;
    	  	}
    	  	$c_unit = SteroGraphic::get_next_unit($mono,$c_unit);
    	  	if (!$c_unit){
    	  		break;
    	  	} 
    	}
    	return $dimRoof;
    }
	// init buff's dim
	private static function initBuffDim($mono){
		if ((isset(self::$_work_buff[$mono]['lhs'])) and (isset(self::$_work_buff[$mono]['rhs']))){
			$dimRoof = self::getDimRoof($mono,self::$_work_buff[$mono]['lhs'],self::$_work_buff[$mono]['rhs']);
			$lhs = self::$_work_buff[$mono]['lhs'];
			$rhs = self::$_work_buff[$mono]['rhs'];
			$lhs_aero = self::collectValidDimUnits($mono,$lhs,$dimRoof,P);
			$rhs_aero = self::collectValidDimUnits($mono,$rhs,$dimRoof,N);	
			// var_dump ($lhs_aero,$rhs_aero);
			$a = array_keys($lhs_aero);
			$b = array_keys($rhs_aero);
			// var_dump ($a,$b);
			$d = array_diff($a,$b);			
			if (!empty($d)){
				foreach ($d as $e){
					unset ($lhs_aero[$e]);
				}
			}
			$j = array_keys($lhs_aero);
			if (empty($j)){
				return false;
			}
			$k = GeneralFunc::my_array_rand($j);
			self::$_work_buff[$mono]['dim'] = $j[$k];	
			return true;
		}
		return false;
	}	
	// 调整影响位($new_xxx 必有效)
	private static function adjustEffectsPos($mono,$new_lhs,$new_rhs){
		// echo "<br><font color = red>effects adjust: mono: $mono ($new_lhs,$new_rhs)</font>";
		$new_lhs_id = 0;
		$new_rhs_id = 0;
		$now_lhs_id = 0;		
		$now_rhs_id = 0;
		if (isset(self::$_work_buff[$mono]['lhs'])){
			$now_lhs_id = self::$_work_queue_cache[$mono][self::$_work_buff[$mono]['lhs']];
		}
		if (isset(self::$_work_buff[$mono]['rhs'])){
			$now_rhs_id = self::$_work_queue_cache[$mono][self::$_work_buff[$mono]['rhs']];
		}
		if (isset(self::$_work_queue_cache[$mono][$new_lhs])){
			$new_lhs_id = self::$_work_queue_cache[$mono][$new_lhs];
		}
		if (isset(self::$_work_queue_cache[$mono][$new_rhs])){
			$new_rhs_id = self::$_work_queue_cache[$mono][$new_rhs];
		}
		if ((!$new_rhs_id) or (!$new_lhs_id)){
			return false;
		}
		
		if ($now_lhs_id){
			if ($new_rhs_id <= $now_lhs_id){
				return false;
			}			
			if ($new_lhs_id < $now_lhs_id){
				self::$_work_buff[$mono]['lhs'] = $new_lhs;	
			}
		}else{
			self::$_work_buff[$mono]['lhs'] = $new_lhs;	
		}

		if ($now_rhs_id){
			if ($new_lhs_id >= $now_rhs_id){
				return false;
			}
			if ($new_rhs_id > $now_rhs_id){
				self::$_work_buff[$mono]['rhs'] = $new_rhs;	
			}
		}else{
			self::$_work_buff[$mono]['rhs'] = $new_rhs;
		}

		return true;
	}
	// 计算一对新的插入点(不含本身)造成的影响
	private static function calEffectsNewMono($mono){
		self::$_work_buff_completed_mono[$mono] = $mono;
		// [mono id] = {'l' => // 影响 mono 最左 单位
		//              'r' => // 影响 mono 最右 单位
		// }
		$effects = array();

		// start to collect all units which be effected!
		$c_unit = self::$_work_buff[$mono]['lhs_ins_pos'];
		while (true){
			$c_unit = SteroGraphic::get_next_unit($mono,$c_unit);

			if ((!$c_unit) or ($c_unit === self::$_work_buff[$mono]['rhs_ins_pos'])){
				break;
			}

			$c_effects_mono = SteroGraphic::get_unit_mono_ids($c_unit);
			if (1 < count($c_effects_mono)){
				foreach ($c_effects_mono as $effected_mono_id){
					if (!isset(self::$_work_buff_completed_mono[$effected_mono_id])){
						if (!isset($effects[$effected_mono_id]['l'])){
							$effects[$effected_mono_id]['l'] = $c_unit;
						}
						$effects[$effected_mono_id]['r'] = $c_unit;
					}
				}
			}			
		}		
		if (!empty($effects)){
			foreach ($effects as $mono => $v){
				$lhs = SteroGraphic::get_prev_unit($mono,$v['l']);
				$rhs = SteroGraphic::get_next_unit($mono,$v['r']);
				if (($lhs) and ($rhs)){
					self::$_work_alive = self::adjustEffectsPos($mono,$lhs,$rhs);
				}else{
					self::$_work_alive = false;
				}
				if (!self::$_work_alive){
					break;
				}
			}
		}
	}
	// 生成指定mono的插入点并完成并行影响计算
	private static function genBuffPair($mono){
		// echo "<br>########################<br>######################## $mono";
		if ((self::$_work_alive) and (!isset(self::$_work_buff[$mono]['lhs_ins_pos']))){ // 左侧插入点定位
			self::$_work_alive = self::collectValidUnit($mono,P);
		}
		if ((self::$_work_alive) and (!isset(self::$_work_buff[$mono]['rhs_ins_pos']))){ // 右侧插入点定位
			self::$_work_alive = self::collectValidUnit($mono,N);
		}
		if (self::$_work_alive){ // 一对插入点造成的影响			
			// 取得所有 需要处理的 mono (!$_work_buff_completed_mono[mono] && ['lhs_ins_pos'] && ['rhs_ins_pos'])
			$tmp = self::$_work_buff;
			foreach ($tmp as $mono => $v){
				if ((isset($v['lhs_ins_pos'])) and (isset($v['rhs_ins_pos']))){					
					if (!isset(self::$_work_buff_completed_mono[$mono])){
						self::calEffectsNewMono($mono);
						if (!self::$_work_alive){
							break;
						}
					}
				}
			}			
		}		
		return;
	}
	// return 0:completed success / 1:success,comeon / -1:conflict,giveup
	private static function genPairWork(){
		$tmp = self::$_work_buff;		
		foreach ($tmp as $mono => $buff){
			if (!isset($buff['dim'])){
				if (!self::initBuffDim($mono)){
					return -1;
				}
			}
			if ((!isset($buff['lhs_ins_pos'])) or (!isset($buff['rhs_ins_pos']))){
				self::genBuffPair($mono);
				if (!self::$_work_alive){
					return -1;
				}else{
					return 1;
				}
			}
		}
		return 0;
	}
	// $work_queue_cache 初始化,方便起见,处理当前stero中所有mono
	private static function initWorkQueueCache($mono){
		if ($monos = SteroGraphic::get_parallel_monos($mono)){
			foreach ($monos as $mono){				
				$line = 1;
				$c_unit = 0;
				while (true){
					$c_unit = SteroGraphic::get_next_unit($mono,$c_unit);
					if (!$c_unit){
						break;
					}
					self::$_work_queue_cache[$mono][$c_unit] = $line;
					$line ++;
				}
			}		
		}		
	}
	// 生成可插入位
	private static function genPair(){
		if (false !== self::getObj($mono,$unit_1,$unit_2)){
			// pseudo data
			// $mono = 3;
			// $unit_1 = 25;
			// $unit_2 = 26;
			//
			self::initWorkQueueCache($mono);
			// echo "<br>mono: $mono";
			// echo "<br>unit_1: $unit_1";
			// echo "<br>unit_2: $unit_2";
			// var_dump (self::$_work_queue_cache);
			self::$_work_buff[$mono]['lhs'] = $unit_1;
			self::$_work_buff[$mono]['rhs']  = $unit_2;
			// var_dump (self::$_work_buff);
			while (0 < ($r = self::genPairWork())){
				// echo '<br>######################## continue...';
			}
			if (0 === $r){ // success
				// echo '<br>######################## success';
				return true;				
			}
			return false;
		}
	}
	// 根据Unit在指令的位置，生成插入位的方向
	private static function genInsertDirection(){
		// array[mono]['l'] = array(n,isInclude) // dir = P 左侧 指令前单位开始(  含自身)
		//                                       //     = N 左侧 指令后单位开始(不含自身)
		//            ['r'] = array(n,isInclude) // dir = P 右侧 指令前单位结束(不含自身)
		//                                       //     = N 右侧 指令后单位结束(  含自身)
		$ret = array();
		foreach (self::$_work_buff as $mono => $v){
			if (P === self::$_table_2[$v['lhs_ins_pos']][1]){
				$ret[$mono]['l'] = array($v['lhs_ins_pos'],true);
			}else{
				$ret[$mono]['l'] = array($v['lhs_ins_pos'],false);
			}			
			if (P === self::$_table_2[$v['rhs_ins_pos']][1]){
				$ret[$mono]['r'] = array($v['rhs_ins_pos'],false);
			}else{
				$ret[$mono]['r'] = array($v['rhs_ins_pos'],true);
			}
		}
		return $ret;
	}
	// merge all insert pos
	private static function mergeInsertPos($insert_balance_pair){
		$left  = array();
		$right = array();
		foreach ($insert_balance_pair as $value){
			$left [$value['l'][0]] = $value['l'][1];
			$right[$value['r'][0]] = $value['r'][1];
		}
		return array($left,$right);
	}
	// Inheritance usable (type  0:push 1:pop)
	private static function inheritanceUsable($unit,$type,$dst){
		$tmp = ConstructionDlinkedListOpt::getUnit($unit);
		if ($tmp){
			$source_usable = OrgansOperator::GetByDListUnit($tmp,USABLE);
			if (isset($tmp[C])){
				if ($type){ // pop
					OrgansOperator::cloneUsables($tmp[C],N,$dst);
				}else{				
					OrgansOperator::cloneUsables($tmp[C],P,$dst);
				}
			}
		}
	}
	// insert new unit to DList
	private static function insertDList($result_array,$reg,$loop_num){
		$ret = array();
		//construct new organ unit
		$push = array(
			OPERATION => 'PUSH',
			P_TYPE => array('r'),
			P_BITS => array(OPT_BITS),
			PARAMS => array($reg),
			STACK  => true,
		);			

		foreach ($result_array[0] as $uid => $isInclude){
			$cid = self::$_table_2[$uid][0];
			if ($isInclude){
				$cid = ConstructionDlinkedListOpt::prevUnit($cid);
			}			
			$new_idx = OrgansOperator::newUnit($push);
			$next_unit = ConstructionDlinkedListOpt::nextUnit($cid);
			self::inheritanceUsable($next_unit,0,$new_idx);			
			OrgansOperator::addUsableReg($new_idx,N,$reg,self::$_work_ignore_forbid);
			$dlink_unit = array(
				'ipsp'  => true,
				C => $new_idx,
				COMMENT => ' !sb.'.$loop_num,
			);
			$j = ConstructionDlinkedListOpt::appendNewUnit($cid,$dlink_unit);
			self::$_GPR_effects[$j][$reg][OPT_BITS] = R;
			self::$_GPR_effects[$j][STACK_POINTER_REG][OPT_BITS] = W;
			$ret[$uid] = $j;
		}
		
		$pop = array(
			OPERATION => 'POP',
			P_TYPE => array('r'),
			P_BITS => array(OPT_BITS),
			PARAMS => array($reg),
			STACK  => true,
		);
		foreach ($result_array[1] as $uid => $isInclude){
			$cid = self::$_table_2[$uid][0];
			if (!$isInclude){
				$cid = ConstructionDlinkedListOpt::prevUnit($cid);
			}
			$new_idx = OrgansOperator::newUnit($pop);
			self::inheritanceUsable($cid,1,$new_idx);
			OrgansOperator::addUsableReg($new_idx,P,$reg,self::$_work_ignore_forbid);
			$dlink_unit = array(
				'ipsp'  => true,
				C => $new_idx,
				COMMENT => ' !sb.'.$loop_num,
			);
			$j = ConstructionDlinkedListOpt::appendNewUnit($cid,$dlink_unit);
			self::$_GPR_effects[$j][$reg][OPT_BITS] = W;
			self::$_GPR_effects[$j][STACK_POINTER_REG][OPT_BITS] = W;
			$ret[$uid] = $j;
		}
		return $ret;
	}
	// effects中是否有无任何读指定$reg操作 (是: True | 否: False)
	private static function isNotReadRegister($effects,$reg){
		$effects_units = array();
		foreach ($effects as $unit){			
			$dlist_unit = self::$_table_2[$unit][0];
			$dlist_dir  = self::$_table_2[$unit][1];
			$effects_units[$dlist_unit] = true;
		}		
		$effects_units = array_keys($effects_units);
		foreach ($effects_units as $unit){
			if (!isset(self::$_GPR_effects[$unit])){
			// TODO: 代码尚未探测对通用寄存器的影响 (应不存在此情况)
				GeneralFunc::LogInsert("StackBalance::collectUsableRegister() found no GPR effects record!",ERROR);
			}else{				
				if (isset(self::$_GPR_effects[$unit][$reg])){
					foreach (self::$_GPR_effects[$unit][$reg] as $c){					
						if ($c & R){
							return false;
						}
					}					
				}
			}
		}
		return true;
	}
	// collect usable register 
	// TODO: already usable reg has less possible to be choosen!
	private static function collectUsableRegister($effects){
		$effects_units = array();
		foreach ($effects as $unit){			
			$dlist_unit = self::$_table_2[$unit][0];
			$dlist_dir  = self::$_table_2[$unit][1];
			$effects_units[$dlist_unit] = true;
		}
		$effects_units = array_keys($effects_units);
		$blocks = array(); // write operation regiser -> block it!
		foreach ($effects_units as $units){
			if (!isset(self::$_GPR_effects[$units])){
				// TODO: 代码尚未探测对通用寄存器的影响 (应不存在此情况)
				GeneralFunc::LogInsert("StackBalance::collectUsableRegister() found no GPR effects record!",ERROR);
			}else{				
				if (!empty(self::$_GPR_effects[$units])){
					foreach (self::$_GPR_effects[$units] as $cGPR => $a){
						foreach ($a as $b => $c){
							if ($c & W){
								$blocks[$cGPR] = true;
								break;
							}
						}
					}
				}
			}
		}
		$blocks[STACK_POINTER_REG] = true; // block stack pointer!
		$blocks = array_keys($blocks);
		return Instruction::getRandomReg(OPT_BITS,$blocks);
	}
    // add the usable reg to all effects units
	private static function addEffectsReg($effects,$reg){
		foreach ($effects as $a){
			$DList_unit = ConstructionDlinkedListOpt::getUnit(self::$_table_2[$a][0]);
			if (isset($DList_unit[C])){
				OrgansOperator::addUsableReg($DList_unit[C],self::$_table_2[$a][1],$reg,self::$_work_ignore_forbid);
			}
		}
	}
	// add new units to _table_1 and _table_2
	public static function insertTableMap($inserted_universe_units,$inserted_list){
		foreach ($inserted_list as $key => $dlist_id){
			$universe_unit_lhs = $inserted_universe_units[$key][0];
			$universe_unit_rhs = $inserted_universe_units[$key][1];
			self::$_table_1[$dlist_id][P]  = $universe_unit_lhs;
			self::$_table_1[$dlist_id][N]  = $universe_unit_rhs;
			self::$_table_2[$universe_unit_lhs] = array($dlist_id,P);
			self::$_table_2[$universe_unit_rhs] = array($dlist_id,N);
		}
	}
	// start ...
	public static function start($stackBalanceArray,$GPReffects,$units,$charates){
		SteroGraphic::unserialize($stackBalanceArray['universe']);
		SteroGraphic::show();
		self::init();
		self::$_GPR_effects = $GPReffects;		
		self::import($stackBalanceArray);
		self::genpool($units,$charates);
		self::genIllUnit($units);
		for ($i = 10;$i>0;$i--){
			self::initwork();
			if (self::genPair()){
				$result_array = self::genInsertDirection();
				$result_array = self::mergeInsertPos($result_array);
				// 1. collect effects
				$effects = SteroGraphic::collect_insert_effects($result_array);
				if (false === $effects){
					GeneralFunc::LogInsert("SteroGraphic::collect_insert_effects() return false!",WARNING);
					continue;
				}
				// 2.check register
				$usableRegister = self::collectUsableRegister($effects);
				if (false === $usableRegister){
					continue;
				}
				echo "<br><br>usableRegister: $usableRegister";
				// 3.insert stero space and change effects' dims
				$inserted_universe_units = SteroGraphic::insert_balance_pair($result_array,$effects,self::GAP_BITS);
				if (false === $inserted_universe_units){
					GeneralFunc::LogInsert("SteroGraphic::insert_balance_pair() return false!",WARNING);
					continue;
				}
				SteroGraphic::show();
				// 识别是否可忽略当前范围内register forbid(s)
				// TODO: 忽略 register forbid 需同时 去掉涉及的usable valid address(s)
				self::$_work_ignore_forbid = self::isNotReadRegister($effects,$usableRegister);
				self::$_work_ignore_forbid = false;
				// 4.insert DList
				$inserted_list = self::insertDList($result_array,$usableRegister,$i);
				// 5.add register usable to all effects unit of DList
				self::addEffectsReg($effects,$usableRegister);
				// 6.insert _table_1 & _table_2
				self::insertTableMap($inserted_universe_units,$inserted_list);
			}
		}
	}
}
?>