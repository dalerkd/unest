<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

class OrganBone{

	private static $_index = 1; // bone 唯一编号,全目标 唯一

    private static $_bone_model_repo;
	private static $_bone_model_index;
	private static $_bone_model_index_multi;

    private static $_bone_multi_max_size = BONE_MULTI_MAX_SIZE;

	private static $_delay_remove;    //待删除(原始label 多通道后)

	private static $_bone_units;          //骨架位 DListID
	private static $_multi_bone_unit_rate;//多通道 内单位character.Rate 原值
	private static $_multi_bone_map;      //多通道 DListID 对应关系

    public static function init(){
        $cf = @file_get_contents(dirname(__FILE__)."/../templates/bone.tpl");
        if ($cf == false){
			GeneralFunc::LogInsert('fail to open bone templates file: '.dirname(__FILE__)."/../templates/bone.tpl",WARNING);
		}else{
			$tmp = unserialize($cf);//反序列化，并赋值  
			if (BONE_TPL_VER !== $tmp['version']){
			    GeneralFunc::LogInsert('unmatch bone template version: ('.BONE_TPL_VER.' !== '.$tmp['version'].') '.dirname(__FILE__)."/../templates/bone.tpl",WARNING);
			}else{
				self::$_bone_model_index       = $tmp['index'];
				self::$_bone_model_index_multi = $tmp['m_index'];
				self::$_bone_model_repo        = $tmp['repo'];				
			}
		}
	}

	//清除指定 usable中 影响 ipsp的单位 (根据 双向链表 索引 开始 -> 结束 )
	private static function remove_ipsp_from_usable_list($start,$end){

		$c_lp = $start;		
		while ($c_lp){
            OrgansOperator::removeUsableMemByStack($c_lp);
			if ($c_lp === $end){
				break;
			}
			$c_lp = OrgansOperator::next($c_lp);
		}		
	}
	// 从灵魂 处 继承骨架的 usable
	private static function inherit_bone_usable(&$c_bone_result_array,$c_soul_position){

		//第一个 / 最后一个必定是 骨架单位
		foreach ($c_bone_result_array['process'] as $x){ //多通道? 直接覆盖
			$first = false;
			$last  = false;	
			$buff  = array();
			foreach ($x as $b){
				if (isset($b['s'])){
					if (isset($c_soul_position[$b['s']])){
						$first = OrgansOperator::getUsable($c_soul_position[$b['s']]['first'],P);
						$last  = OrgansOperator::getUsable($c_soul_position[$b['s']]['last'],N);					
						if (isset($buff)){
							foreach ($buff as $z){
								if ((isset($z[P])) and (false !== $z[P])){
									$c_bone_result_array[USABLE][$z[P]][P] = $first;
								}elseif ((isset($z[N])) and (false !== $z[N])){
									$c_bone_result_array[USABLE][$z[N]][N] = $first;
								}
							}
							unset ($buff);
							$first = false;
						}
					}
				}else{
					$buff[] = $b; //待处理
				}		
			}
			if ((isset($buff)) && (false !== $last)){
				//			echo '<br>buff_ end:';
				//			var_dump ($buff);
				foreach ($buff as $z){
					if (isset($z[P])){
						$c_bone_result_array[USABLE][$z[P]][P] = $last;
					}elseif (isset($z[N])){
						$c_bone_result_array[USABLE][$z[N]][N] = $last;
					}
				}	   
			}
		}
		//对 IPSP 保护区内单位 所有usable 做 消除 ipsp 处理(含 骨架n 灵魂 骨架p)
		if (isset($c_bone_result_array['ipsp'])){
			//var_dump ($c_bone_result_array);
			foreach ($c_bone_result_array['ipsp'] as $a => $b){
				//echo "<br>$a: ";
				if (true === $b){   //灵魂集
					if (isset($c_soul_position[$a]['first'])){
						self::remove_ipsp_from_usable_list($c_soul_position[$a]['first'],$c_soul_position[$a]['last']);
					}
				}elseif (1 === $b){ //prev
					if (isset($c_bone_result_array[USABLE][$a][P][MEM_OPT_ABLE])){
						GenerateFunc::doFilterMemUsable($c_bone_result_array[USABLE][$a][P][MEM_OPT_ABLE]);
					}			    
				}elseif (2 === $b){ //next
					if (isset($c_bone_result_array[USABLE][$a][N][MEM_OPT_ABLE])){
						GenerateFunc::doFilterMemUsable($c_bone_result_array[USABLE][$a][N][MEM_OPT_ABLE]);
					}			    
				}
			}
		}

		//对fat区域，做stack可用标记
		foreach ($c_bone_result_array[FAT] as $y => $x){
			if (1 == $x){
				$c_bone_result_array[USABLE][$y][P][STACK] = true;
			}else{
				$c_bone_result_array[USABLE][$y][N][STACK] = true;
			}
		}		
	}

    //
	private static function remove_from_DlinkedList($c_lp,&$lp_first,&$lp_last,&$copy_buff){
		$prev = false;
		$next = false;		

		if (isset($copy_buff[$c_lp][P])){
			$prev = $copy_buff[$c_lp][P];
		}else{ //无 P, 摘除目标是 首单位
			$lp_first = false;
		}
		
		if (isset($copy_buff[$c_lp][N])){
			$next = $copy_buff[$c_lp][N];
		}else{ //无 N, 摘除目标是 尾单位
			$lp_last = false;
		}
		unset ($copy_buff[$c_lp]);
		if (false !== $prev)
			$copy_buff[$prev][N] = false;
		if (false !== $next)
			$copy_buff[$next][P] = false;

		if ((false !== $prev)&&(false !== $next)){
			$copy_buff[$prev][N] = $next;
			$copy_buff[$next][P] = $prev;
		}elseif (false !== $next){ // if Prev == false
			$lp_first = $next;
		}elseif (false !== $prev){ // if Next == false
			$lp_last = $prev;
		}else{ // empty
			$copy_buff = array();
		}
	}

	//对骨架多通道 进行副本的复制
	private static function soul_copy($source_first,$source_last,&$dest){

		
		$c_first  = $source_first;
		$c_lp     = $source_first;
		$copy_buff = array(); //副本 缓存区
		
		while (true){
			
			if (!isset(self::$_multi_bone_unit_rate[$c_lp])){
			    self::$_multi_bone_unit_rate[$c_lp] = Character::getAllRate($c_lp);
			}

			$copy_buff[$c_lp] = OrgansOperator::getDListUnit($c_lp);
			if ($c_lp === $source_last){
				break;
			}
			$c_lp = OrgansOperator::next($c_lp);
			if (!$c_lp){
			    break;
			}
		}
		$c_last = $c_lp;

		//去除首个单位 Prev 指针 和 末尾单位 Next 指针
		$copy_buff[$c_first][P] = false;
		$copy_buff[$c_last][N]  = false;
		//副本 和 正本 的标签 部分，2者只保留一处
		$c_lp = $c_first;

		$dest['first'] = $c_first;
		$dest['last']  = $c_last;
		while (true){		
			//echo "b $c_lp !== $c_last ,";
			if (false !== $copy_buff[$c_lp][N]){
				$next = $copy_buff[$c_lp][N];
			}else{
				$next = false;
			}
			if (false !== OrgansOperator::getLabel($c_lp)){ // 标号在 副本产生时，随机保留一份(副本或正本中)
			    if (isset(self::$_delay_remove[$c_lp])){    // 已被标识为待清除，说明已有副本替代，这里直接去掉
					self::remove_from_DlinkedList($c_lp,$dest['first'],$dest['last'],$copy_buff);	
				}else{
					if (mt_rand(0,1)){
						self::$_delay_remove[$c_lp] = $c_lp; // 正本中清除 留到 骨架插入完成后清理
					}else{
						self::remove_from_DlinkedList($c_lp,$dest['first'],$dest['last'],$copy_buff);	
					}
				}
			}			

			if ($c_lp === $c_last){
				break;
			}
			if (false === $next){
				break;
			}
			$c_lp = $next;
		}
		return $copy_buff;
	}

	///////////////////////////////////////////////////////////////
	//副本复制进链表，并完成前后部的链接
	//    对副本中含有重定位 值的单位，重定位信息副本累加
	private static function insert_copy_2_list($c_prev,$copy,$soul_position){
	   
		$c_lp = $soul_position['first'];
		while (true){			
			$c_prev = OrgansOperator::newUnitByClone($c_prev,$c_lp);
			if (false === $c_prev){
				GeneralFunc::LogInsert("newUnitByClone() return fail",ERROR);
			}else{
				OrgansOperator::appendComment($c_prev,'cloneB.'.self::$_index);
				self::$_multi_bone_map[$c_lp][] = $c_prev;

				if (($c_lp === $soul_position['last']) or (!isset($copy[$c_lp][N])) or (false === $copy[$c_lp][N])){
					break;
				}			
				$c_lp = $copy[$c_lp][N];
			}
		}
		return $c_prev;
	}

	//对骨架进行初始化(label & Jcc)
	private static function init_bone_model(&$c_bone,$bone_obj){ 

		$bone_index = self::$_index;
		self::$_index ++;

		//补完骨架中的label 标号
		foreach ($c_bone[CODE] as $a => $b){
			if (isset($b[PARAMS][0])){ //跳转参数 最多只可能 有一个 且 为跳转目标标号
				$c_bone[CODE][$a][PARAMS][0] = UNIQUEHEAD.$c_bone[CODE][$a][PARAMS][0].$bone_index;				
				if ('Jcc' === $b[OPERATION]){
					$c_bone[CODE][$a][OPERATION] = Instruction::randUnLmtJcc();
				}
			}elseif (isset($b[LABEL])){
				$c_bone[CODE][$a][LABEL] = UNIQUEHEAD.$c_bone[CODE][$a][LABEL].$bone_index;				
			}		
		}
		
		//根据['direct'] 整理出 每个 灵魂位 实际分配到的 开始灵魂(和 结束灵魂) 的链表编号,修改链表时要用
		$soul_position = array();
		$z = 1; //
		$c_last = false;
		foreach ($c_bone['direct'] as $a => $b){
			if ($b){
				$soul_position[$a]['first'] = $bone_obj[$z];
				$soul_position[$a]['last']  = $bone_obj[$b];
				$z = $b + 1;
				$c_last = OrgansOperator::next($soul_position[$a]['last']);
			}
		}

		//根据['copy'] 整理出 灵魂 副本
		$copy = array();
		$copy_count = 0;
		self::$_delay_remove = array();
		if (isset($c_bone['copy'])){
			foreach ($c_bone['copy'] as $a => $b){
				$copy[$b] = self::soul_copy($soul_position[$a]['first'],$soul_position[$a]['last'],$soul_position[$b]);
			}
		}

		if (defined('DEBUG_ECHO') && defined('BONE_DEBUG_ECHO')){
			echo "<br>*****************************************<br>";
		}
		//修改 链表，把骨架加入进去
		$c_prev_record = array();
		$c_prev = OrgansOperator::prev($bone_obj[1]);
		$c_soul_ptr = 1;
		foreach ($c_bone[CODE] as $a => $b){
			if (true === $b){ //单位
				if (isset($soul_position[$a]['first'])){ //有效
					if (isset($copy[$a])){ //副本
						if (!empty($copy[$a])){
							$c_prev = self::insert_copy_2_list($c_prev,$copy[$a],$soul_position[$a]);
						}
					}else{
						OrgansOperator::manualDLink($c_prev,$soul_position[$a]['first']);
						$c_prev = $soul_position[$a]['last'];
						OrgansOperator::manualDLink($c_prev,$c_last);
					}
				}
			}else{            //骨架
				$c_prev_record[$a] = $c_prev;
			}
		}

		// 从灵魂 处 继承骨架的 usable
		self::inherit_bone_usable($c_bone,$soul_position);

		// insert bone element into Organs
		$last_prev_record = NULL;
		foreach ($c_prev_record as $key => $value){
			if ($value !== $last_prev_record){
		 		$last_prev_record = $value;
		 		$c_prev = $value;
		 	}
		 	$c_fat = false;
		 	if (isset($c_bone[FAT][$key])){
		 		if (1 == $c_bone[FAT][$key]){
		 			$c_fat = array(P=>true);
		 		}else{
		 			$c_fat = array(N=>true);
		 		}
		 	}
		 	if (!($c_prev = OrgansOperator::newUnitByManual($c_prev,$c_bone[CODE][$key],$c_bone[USABLE][$key],$c_fat))){
				GeneralFunc::LogInsert("OrgansOperator::newUnitByManual() return false!",ERROR);
			}else{
				OrgansOperator::appendComment($c_prev,'b'.$bone_index.'.'.$key);				 	
			 	self::$_bone_units[] = $c_prev;
			}
		}

		//正本中想需要清除的标号s
		foreach (self::$_delay_remove as $a){
			OrgansOperator::removeDLink($a);
		}

		return $bone_index;
	}

	//出错则返回 false
	private static function collect_usable_bone_model ($bone_obj,$last_ipsp,$c_bone_model){
		//echo "<font color=blue>";
		//var_dump ($bone_obj);
		//var_dump ($last_ipsp);
		$c_soul = 1;
		$c_soul_length = count($bone_obj);
		//var_dump ($c_soul_length);
		//echo "</font>";
		$direct_num = count ($c_bone_model['direct']);
		foreach ($c_bone_model['direct'] as $a => $b){
			$direct_num --;			
			if (!$c_soul_length){ //无 可分配灵魂，所有位置都设为0
				$c_bone_model['direct'][$a] = 0;
			}else{
				if (0 == $direct_num){ //最后一个，直接覆盖到末尾
					$c_bone_model['direct'][$a] = $c_soul - 1 + $c_soul_length;
					break;
				}
				if ((1 === $b)||(!$last_ipsp)){               //本位置可包含所有灵魂 或 灵魂中 不含 IP/SP 影响
					$c_position = mt_rand (0,$c_soul_length); 
					//echo "<br>type 1 rand (0,$c_soul_length) $c_position";
				}elseif (2 === $b){                           //本位置 可包含所有灵魂,且所有代码中含有的对IP/SP影响的灵魂，必须全部 在 本块前(含本块) 分配完
					$c_position = mt_rand ($last_ipsp,$c_soul_length);
					//echo "<br>type 2 rand ($last_ipsp,$c_soul_length) $c_position";
				}elseif (0 === $b){                           //本位置不能含有 对IP/SP影响的灵魂
					//确定最后一个不含 对IP/SP影响的灵魂 作为 可选 边界
					for ($i = 0;$i < $c_soul_length;$i++){

						if (OrgansOperator::isIPSPUnit($bone_obj[$c_soul + $i])){
							break;
						}   
					}
					$c_position = mt_rand (0,$i);
					//echo "<br>type 3 rand (0,$i) $c_position";
				}else{ //未知属性，返回错误
					return false;
				}

				if ($c_position){			    
					$c_soul_length -= $c_position;
					if ($last_ipsp > $c_position){
						$last_ipsp -= $c_position;
					}else{
						$last_ipsp = 0;
					}				
					$c_soul += $c_position;
					$c_bone_model['direct'][$a] = $c_soul - 1;
				}else{
					$c_bone_model['direct'][$a] = 0;		
				}			
			}
		}

		if ((0 == $b)&&($c_bone_model['direct'][$a])){ //检查最后一个位置是否含有 不支持的类型 ，因为最后一个位置是未经 过滤，直接赋到末 灵魂的
			//echo "<br>check the last position from $c_soul to ".$c_bone_model['direct'][$a]."<br>";
			for ($i = $c_soul;$i <= $c_bone_model['direct'][$a];$i ++){			    

				if (OrgansOperator::isIPSPUnit($bone_obj[$i])){
					return false;
				}
			}			
		}	

		//检查插入代码/位置【stack禁用】 是否与 bone.templates【stack used】 冲突
		if (false !== self::check_bone_stack_conflict($c_bone_model,$bone_obj)){
			return false;
		}

        self::doTearPosition($c_bone_model,$bone_obj);

		$bone_index = self::init_bone_model($c_bone_model,$bone_obj);		
	
		return true;
		
	}

    //撕裂位(骨架插入位) bone.Rate 减 1 , 最少为 1
	private static function doTearPosition($c_bone_model,$bone_obj){
	    
		$_tear_position = array();
		$s = reset($bone_obj); //头
		$e = end($bone_obj);   //尾

		//位于 ['direct']位 的单位与下一单位(如果有的话,下一单位为下一unit的开头) 
        foreach ($c_bone_model['direct'] as $a){
			if (isset($bone_obj[$a])){
				$_tear_position[$bone_obj[$a]] = $bone_obj[$a];
				if (isset($bone_obj[$a + 1])){
					$_tear_position[$bone_obj[$a + 1]] = $bone_obj[$a + 1];
				}
			}
		} 

        //头尾character.Rate已处理，不重复处理
        unset ($_tear_position[$s]);
        unset ($_tear_position[$e]);

		//bone.Rate - 1
		foreach ($_tear_position as $a){
			Character::modifyRate(BONE,$a,-1);
		}
	}

	// 不冲突? 返回false
	//   冲突? 返回冲突位置 -> 
	private static function check_bone_stack_conflict($c_bone_model,$bone_obj){
		$i = 0;
		$stack_unusable = false; //[P] = false -> 前禁用
		$ret = false;
		foreach ($c_bone_model['direct'] as $a => $b){
			if ($b){
				$i ++;
				if (!OrgansOperator::isUsableStack($bone_obj[$i],P)){
					$stack_unusable[$a][P] = true;
				}				
				$i = $b;
				if (!OrgansOperator::isUsableStack($bone_obj[$i],N)){
					$stack_unusable[$a][N] = true;
				}
			}	    
		}

		$conflict_position = array(); //[BONE] [CODE] ['code_direct'] //冲突点 {只搜集一个冲突点}

		if ($stack_unusable){		
			foreach ($c_bone_model['process'] as $a => $b){
				$stack_use = false;       //当前需要stack 使用 (by $c_bone_model)
				$stack_forbid = false;    //当前stack禁用      (by $stack_unusable)
				foreach ($b as $c => $d){
					if (isset($d[P])){
						$conflict_position[BONE] = $d[P];
						if (isset ($c_bone_model[CODE][$d[P]][OPERATION])){
							$c_operand_count = isset($c_bone_model[CODE][$d[P]][PARAMS])?count($c_bone_model[CODE][$d[P]][PARAMS]):0;
							$tmp = Instruction::getInstructionOpt($c_bone_model[CODE][$d[P]][OPERATION],$c_operand_count);
							if (isset($tmp[STACK])){
								if ($stack_forbid){
									$ret = true;
									break;
								}
								$stack_use = true;
							}else{
								$stack_use = false;
							}
						}
					}elseif (isset($d['s'])){
						if (($stack_use) and(isset($stack_unusable[$d['s']][P]))and (true === $stack_unusable[$d['s']][P])){ //conflict
							$conflict_position[CODE] = $d['s'];
							$conflict_position['code_direct'] = P;
							$ret = true;
							break;
						}
						$conflict_position[CODE] = $d['s'];
						$conflict_position['code_direct'] = N;
						if ((isset($stack_unusable[$d['s']][N]))and(true === $stack_unusable[$d['s']][N])){
							$stack_forbid = true;
						}else{
							$stack_forbid = false;
						}
					}
				}
				if ($ret){
					break;
				}
			}
		}
		if (defined('DEBUG_ECHO') && defined('BONE_DEBUG_ECHO')){
			DebugShowFunc::my_shower_05 ($c_bone_model,$bone_obj,$stack_unusable,$ret,$conflict_position);
		}
		return $ret;
	}

	//为多通道bone单位分配character.Rate, readme.bone.txt [2014.10.22]
	private static function resetRate(){

		//echo '<br> multi bone unit map';
		foreach (self::$_multi_bone_map as $a => $b){						
			//echo "<br>$a :::";
			//var_dump (self::$_multi_bone_unit_rate[$a]);
			$b[] = $a;
			shuffle($b);
			$addRate = 0;
			foreach ($b as $c){				
				if (OrgansOperator::isValidUnit($c)){
					if ($addRate){
						character::cloneRate($c,self::$_multi_bone_unit_rate[$a],$addRate);
					}
					$addRate ++;
				}
			}
		}
	}

	public static function start($bone_obj){

		self::$_delay_remove         = array();
		self::$_bone_units           = array();
		self::$_multi_bone_unit_rate = array();
		self::$_multi_bone_map       = array();

		if (count($bone_obj) <= self::$_bone_multi_max_size){
			$c_bone_model_index = self::$_bone_model_index_multi;
		}else{ //too much obj to use multi bone templates.
		    $c_bone_model_index = self::$_bone_model_index;
		}

		$last_ipsp = false;
		foreach ($bone_obj as $a => $b){
			if (OrgansOperator::isIPSPUnit($b)){
				$last_ipsp = $a; //记录 目标 骨架 数组 中 最后一个 影响 ipsp 
			}
		}
		
		$x = GeneralFunc::my_array_rand($c_bone_model_index);
		$z = $c_bone_model_index[$x];
		if (defined('DEBUG_ECHO') && defined('BONE_DEBUG_ECHO')){
			echo "<br> bone repo index: $z ";
		}
		// echo '<br>HIRO test';
		// $z = 2; // 测试 多通道，强制指定
		if ($z){
			if (self::collect_usable_bone_model ($bone_obj,$last_ipsp,self::$_bone_model_repo[$z])){ //骨架出错,代表 骨架模块 有问题	
                if (!empty(self::$_multi_bone_map)){ // set character.Rate for 多通道 bone                	
					self::resetRate();
				}
				foreach (self::$_bone_units as $a){ // bone init character.Rate
				    Character::initUnit($a,BONE);
				}				
			}else{
				global $language;
				GeneralFunc::LogInsert($language['fail_bone_array'].$z,2);
			}			
		}else{ //骨架数组为空...
			return;   
		}
	}
}

?>