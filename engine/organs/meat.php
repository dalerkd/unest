<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

class OrganMeat{
	// meat 唯一编号,全目标 唯一
    private static $_index = 1;

	private static $_meat_inst;

    private static $_SEG;
	private static $_IDX;
	private static $_INST;
	private static $_MATCH;

	private static $_C_ARRAY_ID; //当前任务 使用的 meat.tpl 数组号: rand(MEAT_TPL_PER_TASK)
	
	// 任务数组(每次任务清空,not thread-safe)
	private static $_param_num;
	private static $_param_num_array;
	private static $_param_to_inst_num;
	private static $_reg_effect_result_array;

	private static $_reg_effect_counter;
	private static $_param_to_original_num;
	private static $_meat_units_status; //array(num => status); status: [0].usable [>=1].un-usable (reason in func.meat_shower_02())
	
	// 可用内存地址分类
	private static $_mem_writable_array;    // 可写内存地址
	private static $_mem_readable_array;    // 可读内存地址
    private static $_mem_usable_reg_effect; // 内存地址对应reg影响个数统计 
                                            // [P/N][R/W][effect reg Number][bits] = array(r,..)

	// 关联模板中指令[bits][type]到meat.inst编号，同时补充"?"单位(meat templates 生成时未确切定义的,Type or Bits)
	private static $_meat_runtime_define;
	
	// mem 可用方案数组 (readme.meat.txt.2014/12/?)
	private static $_mem_usable_scheme;
	private static $_mem_usable_scheme_index;
	private static $_mem_scheme_ready;
	private static $_max_mem_reg_number;

	private static $_mem_reg_friends;
	private static $_mem_reg_friends_index;

	private static $_chara_meat_mem_prefer;

	private static $_reg_allot_result;

	// 当前血肉单位 构建ing
	private static $_meat_units;

    public static function init(){

		//////////////////
		//初始化 取得meat 可用指令集
		require dirname(__FILE__).'/../include/meat_inst.inc.php';
        self::$_meat_inst = $meat_inst;
		unset ($meat_inst);

		//////////////////
		//初始化 取得 meat repo库
		$meat_tpl_dir = dirname(__FILE__).'/../templates.meat/';
		$tmp = scandir($meat_tpl_dir);
		$suffix = '.meat.tpl';
		$repo_array = array();
		foreach ($tmp as $tpl_name){
			if (strstr($tpl_name,$suffix)){
				$repo_array[] = $tpl_name;
			}
		}
		shuffle($repo_array);
		$tpl_index = 0;
		foreach ($repo_array as $a){
			// var_dump ($a);    
			$cf = @file_get_contents($meat_tpl_dir.$a);
			if ($cf == false){
				GeneralFunc::LogInsert('fail to open meat tpl: '.$a,WARNING);
			}else{
				$meat_array = unserialize($cf);
				if (MEAT_TPL_VER != $meat_array['VERSION']){
				    GeneralFunc::LogInsert('no match meat tpl version ('.$meat_array['VERSION'].' != '.MEAT_TPL_VER.') : '.$a,WARNING);
				}else{
				    // var_dump (array_keys($meat_array));
					self::$_SEG[$tpl_index]   = $meat_array['SEG'];
					self::$_IDX[$tpl_index]   = $meat_array['IDX'];
					self::$_INST[$tpl_index]  = $meat_array['INST'];
					self::$_MATCH[$tpl_index] = $meat_array['MATCH'];
					$tpl_index ++;
				}				
			}
			if ($tpl_index >= MEAT_TPL_PER_TASK){
			    break;
			}
		}
		self::task_variable_flush();
      
		if (0 == $tpl_index){
		    GeneralFunc::LogInsert('no any meat tpl be loaded',WARNING);
		}elseif ($tpl_index < MEAT_TPL_PER_TASK){
			GeneralFunc::LogInsert($tpl_index.' meat tpl has been loaded less than want: '.MEAT_TPL_PER_TASK,NOTICE);
		}
	}
	// 把生成的血肉 插入 链表
	private static function insert_into_list($current_forward,$meat_generated,$direct = P){
		$prev = false;
		if (P === $direct){
			$prev = ConstructionDlinkedListOpt::prevUnit($current_forward);		
		}else{
			$prev = $current_forward;
		}
		$c_meat = self::$_index - $meat_generated;
		for (;$c_meat < self::$_index;$c_meat++){
			$array = array();
			$array[MEAT] = $c_meat;
			$array[C]    = 98;
			if (GenerateFunc::is_effect_ipsp(OrgansOperator::Get(MEAT,$c_meat,CODE,98),0)){
				$array['ipsp'] = true;
			}
			$prev = ConstructionDlinkedListOpt::appendNewUnit($prev,$array);
			// init character for new unit
			Character::initUnit($prev,MEAT);
		}
	}		
	// 根据目标指令获取meat获取点
	private static function get_split_point($opt){
		$ret = false;

		$opt = Instruction::getInstAlias($opt);
		
		$c_opt = Instruction::getMatchCC($opt);
		if (false !== $c_opt){
		    $opt = $c_opt;
		}
		
		$key = array_search ($opt,self::$_IDX[self::$_C_ARRAY_ID]['OPT']);
		if ((false !== $key) and (isset(self::$_MATCH[self::$_C_ARRAY_ID][$key]))){			
			$split_point = GeneralFunc::my_array_rand(self::$_MATCH[self::$_C_ARRAY_ID][$key]);
			$ret = self::$_MATCH[self::$_C_ARRAY_ID][$key][$split_point];			
		}else{
			if (defined('DEBUG_ECHO') and defined('MEAT_DEBUG_ECHO')){
			    echo "<b><font color=blue>[split_point Random]</font></b>";
			}
			$ret = GeneralFunc::my_array_rand(self::$_INST[self::$_C_ARRAY_ID]);
		}

		return $ret;
	}
    // 判断执行指令是否与前后文usable冲突
    private static function isConflictEnv($c_opt,$c_usable,$c_param_num){
		
		$isConflict = false;
		
		$c_opt_write_effect = Instruction::getRegWriteArrayByOpt($c_opt,$c_param_num);
		// var_dump ($c_opt);
		// var_dump ($c_param_num);
		// var_dump ($c_opt_write_effect);
		if (isset($c_opt_write_effect[FLAG_WRITE_ABLE])){
		    foreach ($c_opt_write_effect[FLAG_WRITE_ABLE] as $i => $v){
				if (!isset($c_usable[FLAG][$i])){
					$isConflict = true;
					break;
				}			
			}
		}
		if ((false === $isConflict) and (isset($c_opt_write_effect[NORMAL_WRITE_ABLE]))){
		    foreach ($c_opt_write_effect[NORMAL_WRITE_ABLE] as $i => $v){
				if (!isset($c_usable[NORMAL_WRITE_ABLE][$i])){
					$isConflict = true;
					break;
				}			
			}
		}
        if ((false === $isConflict) and (isset($c_opt_write_effect[STACK]))){
		    if (!isset($c_usable[STACK])){
			    $isConflict = true;
			}
		}
		// var_dump ($isConflict);
		// echo "<br>###########################################<br>";
		return $isConflict;	
	}
	// 将指令定义到meat_templates 编号位，按序填充 meat.templates中未定义部分'?'
	private static function runtimeDefine($opt,$i){
		$ret = false;
		if (isset(self::$_meat_inst[$opt])){
		    foreach (self::$_meat_inst[$opt] as $a => $b){
				$tmp = true;
			    if (count($b['p_type']) == count(self::$_INST[self::$_C_ARRAY_ID][$i]['P_TYPE'])){
				    foreach (self::$_INST[self::$_C_ARRAY_ID][$i]['P_TYPE'] as $c => $d){
						if ('?' !== $d){
							if ($b['p_type'][$c] != $d){
								$tmp = false;
								break;
							}
						}else{
							if ('i' !== $b['p_type'][$c]){
								$tmp = false;
								break;
							}
						}
					}
					if ($tmp){
						foreach (self::$_INST[self::$_C_ARRAY_ID][$i]['P_BITS'] as $c => $d){
							if ('?' !== $d){
								if ($b['p_bits'][$c] != $d){
									$tmp = false;
									break;
								}
							}
						}
					}
					if ($tmp){
						$ret = true;
						self::$_meat_runtime_define[$i] = $a;
						break;
					}
				}				
			}
		}
		return $ret;
	}
	// 获取meat units
	private static function collectMeatUnits($meat_num,$n,$iterator,$c_usable){
		$ret = array();
		// 遍历可用inst(by self::$_meat_inst)
		while ($meat_num > 0){
			$n =  $n + $iterator;
			if (!isset(self::$_INST[self::$_C_ARRAY_ID][$n])){
			    break;
			}
			$c_opt      = self::$_IDX[self::$_C_ARRAY_ID]['OPT'][self::$_INST[self::$_C_ARRAY_ID][$n]['OPERATION']];	
			if (isset(self::$_INST[self::$_C_ARRAY_ID][$n]['P_TYPE'])){
				$c_para_num = count(self::$_INST[self::$_C_ARRAY_ID][$n]['P_TYPE']);
			}else{
				$c_para_num = 0;
			}
			if (isset(self::$_meat_inst[$c_opt])){
				$meat_num--;
				//检查是否为本身影响 冲突 上下文可用单位 的指令 = 2
				if (false !== Instruction::getMemOpt($c_opt)){
					$ret[$n] = 3; //含内存操作指令
				}elseif (self::isConflictEnv($c_opt,$c_usable,$c_para_num)){
				    $ret[$n] = 2; //usable 冲突用
				}else{
					if (self::runtimeDefine($c_opt,$n)){
						$ret[$n] = 0; //可用
					}else{
						$ret[$n] = 5; //无 inst_ 模板可用
					}
				}
			}else{
				$ret[$n] = 1; //不可用 指令
			}			
		}	
		return $ret;	
	}
	// 根据 操作需要权限(读,写,任意) & 指令前后可用单位, 生成 reg_effect_result_array 数组   [reg]
	private static function genUsableRegs($access,$origin_num,$usable,$c_bits){		
        if ($access < W){ //无写权限，全寄存器可用
		    self::$_reg_effect_result_array[$origin_num][1][] = self::$_param_num;
		}else{ //仅有写权限的可用
			$flag = false;			
			if ((isset($usable[NORMAL_WRITE_ABLE])) and (count($usable[NORMAL_WRITE_ABLE]))){
			    foreach ($usable[NORMAL_WRITE_ABLE] as $a => $b){
			    	if (!empty($b)){
			    		foreach ($b as $z => $none){
			    			if ($z >= $c_bits){ //此处判断并不完善,因为某些32位reg无对应的8位reg,在生成时剔除
			    				self::$_reg_effect_result_array[$origin_num][$a][] = self::$_param_num;
			    				$flag = true;
			    				break;
			    			}
			    		}			    		
			    	}
				}
			}
			if (!$flag){ //不可用
			    self::$_reg_effect_result_array[$origin_num][0][] = self::$_param_num;
			    self::$_meat_units_status[self::$_param_to_inst_num[self::$_param_num]] = 4;
			}
		}
	}
	//遍历 数组元素 所有组合
	private static function genAllCombineArray($first = array(), $arr, &$results = array()){
        $len = count($arr);
        if($len == 1) {
        	$t = $first;
        	$t[] = $arr[0];
        	$results[] = $t;
        } else {
        	for($i=0; $i<$len; $i++) {
        		$tmp = $arr[0];
        		$arr[0] = $arr[$i];
        		$arr[$i] = $tmp;
        		$t = $first;
        		$t[] = $arr[0];
        		self::genAllCombineArray($t, array_slice($arr, 1), $results);                
            }
        }
    }	
	// mem 可用方案数组 中间表 (readme.meat.txt.2014/12/?) 生成
	private static function gen_mem_scheme_ready($inst_id,$origin_units,$m_param){
		$tmp = array();
		$eff[$inst_id] = self::$_param_num;
		foreach ($m_param as $b){
		 	$results = array();	 	
		 	self::genAllCombineArray(array(), $b, $results);
		 	foreach ($results as $c){
		 		$c_array = array();
		 		foreach ($origin_units as $d => $e){
		 			$c_array['u'][$e] = $c[$d];
		 		}
		 		$c_array['eff'] = $eff;
		 		self::$_mem_scheme_ready[] = $c_array;
		 	}
		}
	}	
	// 检查是否有指定内存地址可用 （位数遍历）
	private static function seek_mem_usable($bits,$reg_num,$direct,$access,$origin_units,$inst_id){
		$ret = false;
		foreach (Instruction::getBitsArray($bits) as $c_bits){ // 遍历包含的bits
			if (isset(self::$_mem_usable_reg_effect[$direct][$access][$reg_num][$c_bits])){
				$ret = true;
				if (0 == $reg_num){ // 无影响 寄存器, 无需 加入 mem_scheme
					break;
				}
				// echo "reg_num: $reg_num bits: $bits access: $access";
				// var_dump ($origin_units);
				// var_dump (self::$_mem_usable_reg_effect[$direct][$access][$reg_num][$c_bits]);
				self::gen_mem_scheme_ready($inst_id,$origin_units,self::$_mem_usable_reg_effect[$direct][$access][$reg_num][$c_bits]);
			}
		}
		return $ret;
	}
	// 根据 操作需要权限(读,写,任意) & 指令前后可用单位, 生成 reg_effect_result_array 数组   [mem]
	private static function genUsableRegs_Mem($inst,$access,$origin_units,$direct,$inst_id,$bits){		
		if (R > $access){ // 无读写, 任意内存地址, 内含寄存器作为只读处理
			foreach ($origin_units as $origin_num){
				self::$_reg_effect_result_array[$origin_num][1][] = self::$_param_num;
			}
			return true;
		}
		// else 写 或 读
		$a    = count($origin_units);
		$ret  = 6;  // Fail : no legal mem addr
		while ($a <= self::$_max_mem_reg_number){ // enum all more params Mem units		    
			if (isset(self::$_mem_usable_reg_effect[$direct][$access][$a])){
				if (true !== $ret){
					$ret = 7; // Fail : no legal mem bits
				}				
				if (self::seek_mem_usable($bits,$a,$direct,$access,$origin_units,$inst_id)){
					$ret = true;
				}
			}
			$a++;
		}
		// echo '<br>############################################<br>';
		return $ret;	
	}
	// 生成可用数组
	private static function genEffectResultArray($meat_objs_index,$usable,$direct){				
		//生成指令编号 数组
        foreach ($meat_objs_index as $a){
		    if (0 === self::$_meat_units_status[$a]){
				$inst = self::$_IDX[self::$_C_ARRAY_ID]['OPT'][self::$_INST[self::$_C_ARRAY_ID][$a]['OPERATION']];
				$inst_access_array = Instruction::getInstructionOpt($inst,count(self::$_INST[self::$_C_ARRAY_ID][$a]['P_TYPE']));
				// echo '<br>#################################';
				// var_dump (self::$_INST[self::$_C_ARRAY_ID][$a]['P_TYPE']);
				// var_dump (self::$_INST[self::$_C_ARRAY_ID][$a]['P_BITS']);				
				// var_dump (self::$_meat_inst[$inst][self::$_meat_runtime_define[$a]]['p_bits']);
				// var_dump ($inst);
				// var_dump (self::$_INST[self::$_C_ARRAY_ID][$a]['PARAMS']);
				// var_dump (self::$_INST[self::$_C_ARRAY_ID][$a]['P_TYPE']);
				// var_dump ($inst_access_array);
				if (isset(self::$_INST[self::$_C_ARRAY_ID][$a]['PARAMS'])){
					foreach (self::$_INST[self::$_C_ARRAY_ID][$a]['PARAMS'] as $c => $d){
						$c_bits = self::$_meat_inst[$inst][self::$_meat_runtime_define[$a]]['p_bits'][$c];
						self::$_param_num_array[$a][$c] = self::$_param_num;
						self::$_param_to_inst_num[self::$_param_num] = $a;
						self::$_param_to_original_num[self::$_param_num] = $d;
						if ('r' === self::$_meat_inst[$inst][self::$_meat_runtime_define[$a]]['p_type'][$c]){
							//$d = $d[0]; //寄存器只有一个[0]有值
							self::genUsableRegs($inst_access_array[$c],$d[0],$usable,$c_bits);
						
						}elseif ('m' == self::$_meat_inst[$inst][self::$_meat_runtime_define[$a]]['p_type'][$c]){
							if (true === ($f = self::genUsableRegs_Mem($inst,$inst_access_array[$c],$d,$direct,$a,$c_bits))){
							 //    foreach ($d as $z){
								// 	self::$_reg_effect_counter[$z] ++;
								// }
							}else{
							    self::$_meat_units_status[$a] = $f;
							}

						}
						self::$_param_num ++;
					}
				}
			}
		}		
	}
    // 重构 可用内存地址数组
	private static function get_mem_access($c_usable,$d){	
		if (isset($c_usable[$d][MEM_OPT_ABLE])){
		    foreach ($c_usable[$d][MEM_OPT_ABLE] as $a){
		    	// 可写必可读
				self::$_mem_readable_array[$d][$a] = ValidMemAddr::get($a);
				if (ValidMemAddr::is_writable($a)){
				    self::$_mem_writable_array[$d][$a] = self::$_mem_readable_array[$d][$a];
				}
				
			}
		}
	}
	// 计算内存影响reg 数组
	private static function gen_mem_usable_reg_effect($array,$access){
		if (is_array($array)){
		    foreach ($array as $dir => $a){
			    foreach ($a as $b){
					$c = count($b[REG]);
					self::$_mem_usable_reg_effect[$dir][$access][$c][$b[BITS]][] = $b[REG];
					if ($c > self::$_max_mem_reg_number){
						self::$_max_mem_reg_number = $c;
					}
				}
			}
		}	
	}
	// mem 可用方案数组 索引
	private static function get_id_from_mem_scheme($array){
		ksort ($array);
		$index = 0;
		foreach (self::$_mem_usable_scheme_index as $index => $v){
			if ($array === $v)
				return $index;
		}
		$index ++;
		self::$_mem_usable_scheme_index[$index] = $array;
		return $index;
	}
	// mem 可用方案数组 (readme.meat.txt.2014/12/?) 生成
	private static function gen_mem_usable_scheme(){		
		foreach (self::$_mem_scheme_ready as $a){
			$i = self::get_id_from_mem_scheme($a['u']);
			foreach ($a['eff'] as $z => $y){
				self::$_mem_usable_scheme[$i][$y] = $z;
			}
		}
	}
	// 构建 $_reg_effect_counter 计数器 (统计Reg and Mem部分 同时生成 Mem.friends)
	private static function opt_reg_effect_counter_init(){	
		// 统计 Reg 部分
		foreach (self::$_reg_effect_result_array as $a => $b){
			foreach ($b as $c => $d){
				if (0 !== $c){
					foreach ($d as $z){
						if (isset(self::$_meat_units_status[self::$_param_to_inst_num[$z]])){
							if (0 === self::$_meat_units_status[self::$_param_to_inst_num[$z]]){
								if (!isset(self::$_reg_effect_counter[$a][$c])){
									self::$_reg_effect_counter[$a][$c] = 0;
								}
								self::$_reg_effect_counter[$a][$c]++;
							}
						}
					}
				}
			}
		}
		// 统计 Mem 部分 , 同时生成 friends
		$friends_index = 0;

		foreach (self::$_mem_usable_scheme as $index => $b){
			$z = 0;
			foreach ($b as $num => $inst){
				if (0 === self::$_meat_units_status[$inst]){
					$z += self::$_chara_meat_mem_prefer;
				}				
			}
			if ($z > 0){				
				$friends_index ++;
				$friends_flag = true;
				self::$_mem_reg_friends_index[$friends_index]['prefer'] = $z;
			
				foreach (self::$_mem_usable_scheme_index[$index] as $c => $d){
					if (!isset(self::$_reg_effect_counter[$c][$d])){
						self::$_reg_effect_counter[$c][$d] = 0;
					}
					self::$_reg_effect_counter[$c][$d] += $z;
					if ($friends_flag){
						self::$_mem_reg_friends[$c][$d][$friends_index] = true;
						self::$_mem_reg_friends_index[$friends_index][$c] = $d;
					}
				}
			}
		}
	}
	// 删除friends 单位 ,同时 清除 对应的reg分配影响力
	private static function mem_reg_effect_friends_delete($id){
		echo "<br><br><br>Delete: $id";
		if (isset(self::$_mem_reg_friends_index[$id])){
			$prefer = self::$_mem_reg_friends_index[$id]['prefer'];
			unset (self::$_mem_reg_friends_index[$id]['prefer']);
			foreach (self::$_mem_reg_friends_index[$id] as $num => $reg){
				echo "<br>deling... $num : $reg";
				if (isset(self::$_reg_effect_counter[$num][$reg])){
					echo "... deled";
					self::$_reg_effect_counter[$num][$reg] -= $prefer;

				}
			}
			unset (self::$_mem_reg_friends_index[$id]);
		}
	}
	// 获取一个分配结果
	private static function reg_allot_get(){
		$max = 0;
		$objs = array();
		foreach (self::$_reg_effect_counter as $o_num => $a){
			foreach ($a as $reg => $count){
				if (1 !== $reg){
					if ($count > $max){
						$max  = $count;
						$objs = array();
					}
					if ($count == $max){
						$objs[] = array($o_num,$reg,$count);
					}
				}
			}
		}
		if (empty($objs)){
			return false;
		}
		// echo '<br><br>MAX:';
		// var_dump ($max);
		// var_dump ($objs);
		shuffle($objs);
		// var_dump ($objs);
		return $objs[0];
	}
	// 分配 全可单位[1:All]
	private static function reg_allot_all_usable(){
		if (!empty(self::$_reg_effect_counter)){
			// var_dump (self::$_reg_effect_counter);
			foreach (self::$_reg_effect_counter as $o_num => $a){
				if (isset($a[1])){
					// 剩余 regs 中 随机获取 一个
					if (false === ($r = Instruction::getRandomReg(32,self::$_reg_allot_result))){
						$r = Instruction::getRandomReg(32);
					}
					if (false !== $r){
						self::$_reg_allot_result[$o_num] = $r;
					}
				}
			}
		}		
	}
	// friends 分配单位确认(清除),处理关联处理 $deal: true:确定  false:清除
	private static function friends_reinit($num,$reg,$deal = true){
		if (isset(self::$_mem_reg_friends[$num][$reg])){
			foreach (self::$_mem_reg_friends[$num][$reg] as $key => $no){
				if (isset(self::$_mem_reg_friends_index[$key])){
					if ($deal){						
					    self::$_mem_reg_friends_index[$key]['prefer'] += self::$_chara_meat_mem_prefer;
					}else{
						$prefer = self::$_mem_reg_friends_index[$key]['prefer'];
					}

					foreach (self::$_mem_reg_friends_index[$key] as $o_num => $r){
						// echo "<br> num: $num ; reg: $reg ; deal: $deal; $o_num: $r <br>";
						// var_dump ($deal);
						if ('prefer' === $o_num){
							continue;
						}
						if (isset(self::$_reg_effect_counter[$o_num][$r])){
							if ($deal){
								self::$_reg_effect_counter[$o_num][$r] += self::$_chara_meat_mem_prefer;
							}else{
								self::$_reg_effect_counter[$o_num][$r] -= $prefer;
							}
						}

					}
					if ($deal){

					}else{
						unset (self::$_mem_reg_friends_index[$key]);
					}
				}
			}			
		}
	}
	// 根据已分配寄存器，重置 $_reg_effect_counter 变量
	// : 清已分配部分(原始变量号 & 其它原始变量号中此Reg)，并设置friends单位(增/减 影响力)
	private static function mem_reg_effect_counter_reinit($r){
		// friends 确认处理
		self::friends_reinit($r[0],$r[1],true);	
		unset (self::$_reg_effect_counter[$r[0]][$r[1]]);			
		foreach (self::$_reg_effect_counter[$r[0]] as $a => $b){
			if (1 === $a){
				continue;
			}
			// friends 清除处理
			self::friends_reinit($r[0],$a,false);
		}
		unset (self::$_reg_effect_counter[$r[0]]);

		$tmp = self::$_reg_effect_counter;
		foreach ($tmp as $a => $b){
			if (isset(self::$_reg_effect_counter[$a][$r[1]])){
				//friends 清除处理
				self::friends_reinit($a,$r[1],false);
				unset (self::$_reg_effect_counter[$a][$r[1]]);
			}
		}
	}
	// 完成 reg -> 原始变量 的分配
	private static function reg_allot(){
		while (true){			
			$r = self::reg_allot_get();
			if (false === $r){
				break;
			}
			self::$_reg_allot_result[$r[0]] = $r[1];
			self::mem_reg_effect_counter_reinit($r);
			if (defined('DEBUG_ECHO') and defined('MEAT_DEBUG_ECHO')){
				echo '<br>reg allot: '.$r[0].' => '.$r[1];
				self::meat_shower_07(self::$_reg_effect_counter);
			}
		}
		// 分配剩余的 全可用单位
		self::reg_allot_all_usable();
	}
	// 判断目标寄存器位数是否可写 (注,不判断 ->寄存器是否有目标位数的表示方法)
	private static function isWritableReg($reg,$bit,$normal_write_able){
		$ret = false;
		if (isset ($normal_write_able[$reg])){
			foreach ($normal_write_able[$reg] as $b => $none){
				if ($b >= $bit){
					$ret = true;
					break;
				}
			}
		}
		return $ret;
	}
	// 生成无效内存地址
	private static function genInvalidMemAddr($effects){
		$tmp = array(false,false);
		$i   = 0;
		if (!empty($effects)){
		    foreach ($effects as $a){
		    	if (isset(self::$_reg_allot_result[$a])){
		    		$c_reg = self::$_reg_allot_result[$a];
		    		$tmp[$i] = $c_reg;
		    		$i ++;
		    	}
		    }
		}
		shuffle($tmp);
		if (STACK_POINTER_REG === $tmp[1]){ //esp寄存器不能作为 内存地址的 第二参数
			$tmp = array($tmp[1],$tmp[0]);
			if (STACK_POINTER_REG === $tmp[1]){
				$tmp[1] = false;
			}
		}
		$first  = $second = $third = 0;
		$ret = false;
		if (false === $tmp[0]){
			$first = 0;
		}else{
			$first = 1;
			$ret   = $tmp[0];
		}
		if (false === $tmp[1]){
			$second = 0;
		}else{
			if (false !== $ret){
				$ret .= '+';
			}
			$ret .= $tmp[1];
			$rate = GeneralFunc::my_array_rand(array('1'=>true,'2'=>true,'4'=>true,'8'=>true));
			$ret .= '*'.$rate;
			if ((0 === $first) and ($rate > 2)){ //第二寄存器 乘数如大于2 且第一寄存器不存在，影响长度固定为 5 (最大)
				$second = 2;
			}else{
				$second = 1;
			}
		}

		if ((0 === $second) || (0 === $first) or (mt_rand(0,3))){
			if (false !== $ret){
				$ret .= '+';
			}
			$r_int = GenerateFunc::rand_interger();
			$ret .= $r_int['value'];

			$third = GenerateFunc::bits_precision_adjust($r_int[BITS]);
		}

		$ret = '['.$ret.']';

		//生成后 计算影响 指令长度 加入 all_valid_mem_opcode_len 数组
		global $all_valid_mem_opcode_len;		
		
		$len = Instruction::getMemEffectLen($first,$second,$third);
		
		$all_valid_mem_opcode_len[$ret] = $len;

		return $ret;
	}
	// 生成有效内存地址
	private static function genValidMemAddr($effects,$access,$bit,$usable,&$P_M_REG,&$REL){
		global $c_rel_info;
		$mem_array = array();
		foreach ($usable[MEM_OPT_ABLE] as $i){
			$mem = ValidMemAddr::get($i);
			if ($mem[BITS] >= $bit){
				if ($mem[OPT] >= $access){
					$flag = true;
					foreach ($effects as $reg){
						if (isset(self::$_reg_allot_result[$reg])){
							if (false === array_search(self::$_reg_allot_result[$reg],$mem[REG])){
								$flag = false;
								break;
							}
						}elseif (!CHARA_MEAT_DIRTY){
							$flag = false;
							break;
						}
					}
					if ($flag){
						$mem_array[] = $i;
					}
				}
			}
		}
		if (!empty($mem_array)){
			$mKey = GeneralFunc::my_array_rand ($mem_array);
			$mIdx = $mem_array[$mKey];
			$mem = ValidMemAddr::get($mIdx);
			if (isset($mem)){
				// 内存地址 含寄存器
				if (isset($mem[REG])){
					foreach ($mem[REG] as $a){
						$P_M_REG[$a] = 1;
					}
				}
				// 内存地址 含 重定位信息
				if (isset($mem[REL])){
					if (GenerateFunc::reloc_inc_copy($mem[CODE],$old,$new)){
						$mem[CODE] = str_replace(UNIQUEHEAD.'RELINFO_'.$old[0].'_'.$old[1].'_'.$old[2],UNIQUEHEAD.'RELINFO_'.$old[0].'_'.$old[1].'_'.$new,$mem[CODE]);					
						ValidMemAddr::set($mIdx,$mem);
						$REL['i'] = $old[1];
						$REL[C] = $new;
						$c_rel_info[$old[1]][$new] = $c_rel_info[$old[1]][$old[2]];						
					}
				}
				return $mem[CODE];
			}			
		}
		return false;
	}
	// 完成指令的 参数 构建
	private static function genParams($access,$type,$bit,$effects,$usable,$obj,$p_i){
		$ret = false;
		if ('r' === $type){
			if ((empty($effects)) and (CHARA_MEAT_DIRTY)){   // 无影响定义，随机
				if ($access >= W){	// 无影响定义 且 为写权限 丢弃
					// if (false !== ($p = self::getRandRegFromNormalWriteable($usable[NORMAL_WRITE_ABLE],$bit))){
					// 	var_dump ($p);
					// 	self::$_meat_units[$obj][CODE][98][PARAMS][$p_i] = $p;
					// 	$ret = true;					
					// }
				}else{
					// echo "<br>******************** $obj $bit<br>";
					if (false !== ($p = Instruction::getRandomReg($bit))){
						if (false !== ($p = Instruction::getRegByIdxBits($bit,$p))){
							// var_dump ($p);
							self::$_meat_units[$obj][CODE][98][PARAMS][$p_i] = $p;
							$ret = true;
						}
					}					
				}
			}else{
				foreach ($effects as $a){
					if (isset(self::$_reg_allot_result[$a])){
						if ($access >= W){ // 需判断分配的是否可用
							if (!self::isWritableReg(self::$_reg_allot_result[$a],$bit,$usable[NORMAL_WRITE_ABLE])){
								return false;
							}
						}
						if (false !== ($p = Instruction::getRegByIdxBits($bit,self::$_reg_allot_result[$a]))){
							self::$_meat_units[$obj][CODE][98][PARAMS][$p_i] = $p;
							$ret = true;
						}
					}
					break;
				}
			}
		}elseif ('m' === $type){
			if (R > $access){ // invalid mem				
				if (false !== ($p = self::genInvalidMemAddr($effects))){
					self::$_meat_units[$obj][CODE][98][PARAMS][$p_i] = $p;
					$ret = true;
				}
			}else{
				$P_M_REG = false;
				$REL     = false;
				if (false !== ($p = self::genValidMemAddr($effects,$access,$bit,$usable,$P_M_REG,$REL))){
					self::$_meat_units[$obj][CODE][98][PARAMS][$p_i] = $p;
					if (false !== $P_M_REG){
						self::$_meat_units[$obj][CODE][98][P_M_REG][$p_i] = $P_M_REG;
					}
					if (false !== $REL){
						self::$_meat_units[$obj][CODE][98][REL][$p_i] = $REL;
					}
					$ret = true;
				}
			}
		}elseif ('i' === $type){
			$p = GenerateFunc::rand_interger($bit);
			self::$_meat_units[$obj][CODE][98][PARAMS][$p_i] = $p['value'];
			$ret = true;
		}		
		return $ret;		
	}
	// 完成指令的构建(inst,参数) return $_meat[CODE] = ...
	private static function genInstruction($obj,$usable){
		$inst   = self::$_IDX[self::$_C_ARRAY_ID]['OPT'][self::$_INST[self::$_C_ARRAY_ID][$obj]['OPERATION']];
		$types  = self::$_meat_inst[$inst][self::$_meat_runtime_define[$obj]]['p_type']; 
		$bits   = self::$_meat_inst[$inst][self::$_meat_runtime_define[$obj]]['p_bits'];
		$params = self::$_INST[self::$_C_ARRAY_ID][$obj]['PARAMS'];
		$inst_access_array = Instruction::getInstructionOpt($inst,count($types));
		self::$_meat_units[$obj][CODE][98][OPERATION] = $inst;
		foreach ($types as $i => $a){
			if (!isset($params[$i])){
				$params[$i] = array();
			}
			self::$_meat_units[$obj][CODE][98][P_TYPE][$i] = $a;
			self::$_meat_units[$obj][CODE][98][P_BITS][$i] = $bits[$i];
			if (isset(self::$_meat_inst[$inst][self::$_meat_runtime_define[$obj]]['static'][$i])){ // 固定寄存器
				self::$_meat_units[$obj][CODE][98][PARAMS][$i] = self::$_meat_inst[$inst][self::$_meat_runtime_define[$obj]]['static'][$i];
			}elseif (false === self::genParams($inst_access_array[$i],$a,$bits[$i],$params[$i],$usable,$obj,$i)){
				return false;
			}
		}		
		return true;
	}
	// 插入meat_result, offered to debug func
	public static function append($result){
	    OrgansOperator::Set(MEAT,self::$_index,$result);
		$ret = self::$_index;
		self::$_index ++;
		return $ret;
	}
	// 完成meat 单位的最终构建(参数,usable)
	private static function genMeats($objs,$usable){		
		foreach ($objs as $c_obj){
			if (0 === self::$_meat_units_status[$c_obj]){			
				if (false !== self::genInstruction($c_obj,$usable)){				
					self::$_meat_units[$c_obj][USABLE][98][P] = $usable;
					self::$_meat_units[$c_obj][USABLE][98][N] = $usable;
					// 对结果进行stack可用状态设置(根据usable)
					GeneralFunc::soul_stack_set(self::$_meat_units[$c_obj][CODE],self::$_meat_units[$c_obj][USABLE]);
					// insert into MeatList
					self::append(self::$_meat_units[$c_obj]);					
				}else{ // gen Inst fail
					self::$_meat_units_status[$c_obj] = 8;
				}
			}
		}
	}
	// 任务全局 变量 flush
	private static function task_variable_flush(){

		self::$_param_num               = 1;
		self::$_param_num_array         = array();
		self::$_param_to_inst_num       = array();
		self::$_reg_effect_result_array = array();

		self::$_reg_effect_counter   = array();
		self::$_param_to_original_num   = array();
		self::$_meat_units_status       = array();
		//
		self::$_mem_writable_array    = false; //可写内存地址
		self::$_mem_readable_array    = false; //可读内存地址
		self::$_mem_usable_reg_effect = false;		
		//
		self::$_meat_runtime_define     = array();
		self::$_mem_usable_scheme       = array();
		self::$_mem_usable_scheme_index = array();
		self::$_mem_scheme_ready        = array();
		self::$_max_mem_reg_number      = 0;

		self::$_mem_reg_friends       = array();
		self::$_mem_reg_friends_index = array();

		self::$_chara_meat_mem_prefer = 1;
		self::$_reg_allot_result      = array();

		self::$_meat_units = array();
	}
	// start
	public static function start($objs){

        // about meat.obj
		$obj = $objs[1];
		$b = ConstructionDlinkedListOpt::getUnit($obj);
		$c_obj    = OrgansOperator::GetByDListUnit($b,CODE);
		$c_usable = OrgansOperator::GetByDListUnit($b,USABLE);
		$c_fat    = OrgansOperator::GetByDListUnit($b,FAT);

        // usable of meat.obj
		self::get_mem_access($c_usable,P);
		self::get_mem_access($c_usable,N);	
        // calculate mem usable  
		self::gen_mem_usable_reg_effect(self::$_mem_readable_array,R);
		self::gen_mem_usable_reg_effect(self::$_mem_writable_array,W);		
        // 可用meat.tpl中rand一个当前任务用
		self::$_C_ARRAY_ID = GeneralFunc::my_array_rand(self::$_MATCH);
        // 随机获取 & 分配 meat.units
		$split_point = self::get_split_point($c_obj[OPERATION]);
		$rate = Character::getAllRate($obj);
		$meat_strength = ($rate[MEAT] > 1)?$rate[MEAT]:1;
		$split_size  = rand (1,$meat_strength * MEAT_MAX_SINGLE_UNIT);
		if (!$c_fat){
			$front_meat_num  = rand (0,$split_size);			
		}elseif (1 == $c_fat){
			$front_meat_num  = 0;
		}else{
			$front_meat_num  = $split_size;
		}
		$behind_meat_num = $split_size - $front_meat_num;
		// echo
		if (defined('DEBUG_ECHO') and defined('MEAT_DEBUG_ECHO')){
			self::meat_shower_01($obj,$c_obj,$c_usable,$c_fat,$split_point,$split_size,$meat_strength,$front_meat_num,$behind_meat_num);
			self::meat_shower_04();
			echo '<br>Max count reg number in Mem: <b>'.self::$_max_mem_reg_number.'</b><br>';
			self::meat_shower_05();
		}
		// 分配meat.units
		$tmp  = self::collectMeatUnits($front_meat_num,$split_point,-1,$c_usable[P]);
        $tmp  = array_reverse($tmp,true);
		$front_index  = array_keys($tmp);
		self::$_meat_units_status += $tmp;

		$tmp = self::collectMeatUnits($behind_meat_num,$split_point,1,$c_usable[N]);	
		$behind_index = array_keys($tmp);
		self::$_meat_units_status += $tmp;
		self::$_meat_units_status[$split_point] = 99;
		// meat.obj 自身包含的reg -> 分配列表
		if (isset(self::$_INST[self::$_C_ARRAY_ID][$split_point]['PARAMS'])){
			foreach (self::$_INST[self::$_C_ARRAY_ID][$split_point]['PARAMS'] as $a => $b){
				$r = array();
				if (isset($c_obj[P_TYPE])){
					if ('m' === $c_obj[P_TYPE][$a]){
					    $r = array_keys($c_obj[P_M_REG][$a]);
					}
					if ('r' === $c_obj[P_TYPE][$a]){
						$r[] = $c_obj[PARAMS][$a];
					}
				}
				// var_dump ($r);
				foreach ($b as $c => $d){
					self::$_param_num_array[$split_point][$a]    = self::$_param_num;
					self::$_param_to_inst_num[self::$_param_num] = $split_point;
					self::$_param_to_original_num[self::$_param_num][]  = $d;
					foreach ($r as $e => $f){
						self::$_reg_effect_result_array[$d][$f][] = self::$_param_num;

					}
					self::$_param_num ++;
				}			
			}
		}
		// 构造reg分配列表
        self::genEffectResultArray($front_index ,$c_usable[P],P);
		self::genEffectResultArray($behind_index,$c_usable[N],N);
		// var_dump (self::$_mem_scheme_ready);
		// 根据 $_mem_scheme_ready 生成 $_mem_usable_scheme
		self::gen_mem_usable_scheme();
		// var_dump (self::$_mem_usable_scheme_index);
		// var_dump (self::$_mem_usable_scheme);

		// mem 操作权重
		self::$_chara_meat_mem_prefer = rand(CHARA_MEAT_MEM_PREFER_MIN,CHARA_MEAT_MEM_PREFER_MAX);

		self::opt_reg_effect_counter_init();
		
		if (defined('DEBUG_ECHO') and defined('MEAT_DEBUG_ECHO')){
			self::meat_shower_06();
			echo "<br>HARA_MEAT_MEM_PREFER: ".self::$_chara_meat_mem_prefer.'<br>';
			if (!empty(self::$_mem_reg_friends)){
				// var_dump (self::$_mem_reg_friends);
				// var_dump (self::$_mem_reg_friends_index);
				self::meat_shower_09();
			}
			echo '<br><b>- init</b> 指令编号:<b>指令行号</b>';
			self::meat_shower_03(self::$_reg_effect_result_array);
			echo '<br><b>final Result before Reg Distribution</b>';
			self::meat_shower_07(self::$_reg_effect_counter);
		}

		// 分配reg
        self::reg_allot();

        // 生成 最终指令
        $start_index = self::$_index;
        self::genMeats($front_index ,$c_usable[P]);
        if ($start_index < self::$_index){
        	// var_dump (OrgansOperator::Printer());
        	// var_dump (self::$_meat_units);
        	
            self::insert_into_list($obj,self::$_index - $start_index,P);
            // self::insert_into_list($start_index,P);
        }

        $start_index = self::$_index;
        self::genMeats($behind_index,$c_usable[N]);
        if ($start_index < self::$_index){
        	// var_dump (OrgansOperator::Printer());
        	
        	self::insert_into_list($obj,self::$_index - $start_index,N);
            // self::insert_into_list($start_index,N);
        }
	
        if (defined('DEBUG_ECHO') and defined('MEAT_DEBUG_ECHO')){
        	self::meat_shower_08();
			self::meat_shower_02($front_index,$split_point,$c_obj,$behind_index);
		}		
		
		self::task_variable_flush();


		// var_dump (CHARA_MEAT_DIRTY);
		 // exit;

		// exit;
        return;
	
	}
    
	// debug echo ...
	private static function meat_shower_09(){
		echo '<br><b>$_mem_reg_friends and index</b>';
		echo '<table border=1>';
		echo '<tr><td>index</td><td>Contents</td></tr>';
		foreach (self::$_mem_reg_friends_index as $key => $a){
			echo '<tr>';
			echo '<td>';
			echo "$key";
			echo '</td>';
			echo '<td>';
			var_dump ($a);
			echo '</td>';
			foreach ($a as $o_num => $reg){
				if ('prefer' === $o_num){
					continue;
				}
				echo '<td>';
				echo $o_num.' : '.$reg;
				echo '</td>';				
				echo '<td>';
				var_dump (self::$_mem_reg_friends[$o_num][$reg]);
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
	}
	private static function meat_shower_08(){
		echo '<table border =1>';
		echo '<tr><td bgcolor=green>';
		echo '<b>Register allot result</b>';
		echo '</td>';
		foreach (self::$_reg_allot_result as $i => $reg){
			echo '<td>';
			echo $i.': <b>'.$reg.'</b>';
			echo '</td>';
		}
		echo '</tr>';
		echo '</table>';
	}
	private static function meat_shower_07($reg_counter){
		$i = array();		
		echo '<table border=1>';
		echo '<tr>';
		foreach ($reg_counter as $a => $b){
			echo '<td>';
			echo $a;
			echo '</td>';
			$i[] = $a;
		}		
		echo '</tr>';
		echo '<tr>';
		foreach ($i as $a){
			echo '<td>';
			var_dump ($reg_counter[$a]);
			echo '</td>';
		}		
		echo '</tr>';
        echo '</table>';
	}
	private static function meat_shower_06(){
		echo '<br><b>$_mem_usable_scheme</b>';
		echo '<table border=1>';
		echo '<tr>';		
		foreach (self::$_mem_usable_scheme_index as $a){
			echo '<td>';
			foreach ($a as $num => $reg){
				echo '{'."$num: $reg".'}';
			}
			echo '</td>';
		}
		echo '</tr>';
		echo '<tr>';
		foreach (self::$_mem_usable_scheme_index as $a => $b){
			echo '<td>';
			foreach (self::$_mem_usable_scheme[$a] as $num => $inst){
				// foreach ($z as $inst => $num){
					echo '<br>';
					if (0 !== self::$_meat_units_status[$inst]){
						echo '<del>';
						echo $inst.' -> '.$num;
						echo '</del>';
					}else{
						echo '<b>';
						echo $inst.' -> '.$num;
						echo '</b>';
					}
				// }
			}			
			echo '</td>';
		}
		echo '</tr>';		
		echo '</table>';
	}
	private static function meat_shower_05(){
		echo '<br>self::$_mem_usable_reg_effect';
		echo '<table border=1>';
		echo '<tr><td>Pos</td><td>Access</td><td>Count</td><td>Bits</td><td>rid</td><td>Regs</td></tr>';
		if ((isset(self::$_mem_usable_reg_effect)) and (is_array(self::$_mem_usable_reg_effect))){
			foreach (self::$_mem_usable_reg_effect as $pos => $a){
				foreach ($a as $b => $c){				
					foreach ($c as $count => $d){					
						foreach ($d as $bits => $e){
							foreach ($e as $f => $g){
								echo '<tr>';
								echo '<td>'.$pos.'</td>';
								echo '<td>'.$b.'</td>';
								echo '<td>'.$count.'</td>';
								echo '<td>'.$bits.'</td>';							
								echo '<td>'.$f.'</td>';
								echo '<td>';
								var_dump ($g);
								echo '</td>';
								echo '</tr>';
							}
						}
					}
				}
			}		
	    }
		echo '</table>';
	}
	private static function meat_shower_04_01($array,$dir,$access){
		if ((isset($array[$dir])) and (is_array($array[$dir]))){
			foreach ($array[$dir] as $a => $b){
				if (P == $dir)
					echo '<tr bgcolor="#c0c0c0"><td>P';
				else
					echo "<tr><td>N";
				echo '</td><td>';
				if ('W' == $access)
					echo "<font color = blue><b>W</b><font>";
				else
					echo "$access";
				echo '</td><td>';
				echo $b[OPT];
				echo '</td><td>';
				echo $a;
				echo '</td><td>';
				var_dump ($b[REG]);
				echo '</td><td>';
				echo $b[BITS];
				echo '</td><td>';
				echo $b[CODE];
				echo '</td></tr>';
			}
		}	
	}
	private static function meat_shower_04(){
		echo '<br>Mem usable';
		echo '<table border=1>';
		echo '<tr><td>Pos</td><td>Opt</td><td>Opt</td><td>id</td><td>Reg</td><td>Bits</td><td>Code</td></tr>';
		self::meat_shower_04_01(self::$_mem_readable_array,P,'R');
		self::meat_shower_04_01(self::$_mem_writable_array,P,'W');
		self::meat_shower_04_01(self::$_mem_readable_array,N,'R');
		self::meat_shower_04_01(self::$_mem_writable_array,N,'W');
		echo '</table>';
	}
	private static function meat_shower_03($reg_effect_result_array){
		echo '<table border=1>';
		echo '<tr><td></td>';
		$num = array();
		foreach ($reg_effect_result_array as $a => $b){
			echo '<td>'."$a".'</td>';
			$num[] = $a;
		}
        echo '</tr>';
        $side_bar = array();
        foreach ($num as $a){
		    $side_bar += array_flip(array_keys($reg_effect_result_array[$a]));
		}
		
		foreach ($side_bar as $title => $tmp){
			echo '<tr';
			if (($tmp+1)%2){
				echo ' bgcolor=#c0c0c0';
			}
			echo '><td>'.$title.'</td>';
			foreach ($num as $a){
				$counter = 0;
				echo '<td>';
				if (isset($reg_effect_result_array[$a][$title])){
					foreach ($reg_effect_result_array[$a][$title] as $z => $y){
					    if (0 !== self::$_meat_units_status[self::$_param_to_inst_num[$y]]){
					    	echo '<del>';
					    }else{
					    	echo '<b>';
					    }
					        echo '{'."$y".':';
					        echo self::$_param_to_inst_num[$y].'}';
					        $counter ++;
					        if ($counter >= 3){
					        	$counter = 0;
					        	echo '<br>';
					        }
						if (0 !== self::$_meat_units_status[self::$_param_to_inst_num[$y]]){
					    	echo '</del>';
					    }else{
					    	echo '</b>';
					    }
					}					
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table>';	
	}
	private static function meat_shower_01($obj,$c_obj,$c_usable,$c_fat,$split_point,$split_size,$meat_strength,$front_meat_num,$behind_meat_num){
		echo '<table border = 1><tr><td>meat obj ID</td><td>meat obj</td><td>usable</td><td>Fat?</td><td>split_point</td><td>split_obj</td><td>split_inst</td><td>split_size/meat_strength * MEAT_MAX_SINGLE_UNIT (front,behind)</td><tr>';
		echo '<tr><td>';
		echo "$obj";
		echo '</td><td>';
		var_dump ($c_obj);
		echo '</td><td>';
		var_dump ($c_usable[P]);
		var_dump ($c_usable[N]);
		echo '</td><td>';			
		var_dump ($c_fat);
		echo '</td><td>';
		var_dump ($split_point);
		echo '</td><td>';
		var_dump (self::$_INST[self::$_C_ARRAY_ID][$split_point]);
		echo '</td><td>';
		var_dump (self::$_IDX[self::$_C_ARRAY_ID]['OPT'][self::$_INST[self::$_C_ARRAY_ID][$split_point]['OPERATION']]);
		echo '</td><td>';
		echo "$split_size".'/'.$meat_strength.' * '.MEAT_MAX_SINGLE_UNIT.' ('.$front_meat_num.','.$behind_meat_num.')';
		echo '</td></tr>';
		echo '</table>';	
	}
	private static function meat_shower_02_01($index,$pos){
		foreach ($index as $a){
			$b = self::$_meat_units_status[$a];
			$c_opt = self::$_IDX[self::$_C_ARRAY_ID]['OPT'][self::$_INST[self::$_C_ARRAY_ID][$a]['OPERATION']];
			if (0 === $b){
				echo '<tr><td>';					
			}else{
				echo '<tr bgcolor="#c0c0c0"><td>';
			}
			echo '<b>'."$pos".'</b></td><td>';
			echo $a.'</td><td><b>'.$c_opt;
			echo '</b></td><td>';			
			if (1 !== $b)
				var_dump (self::$_INST[self::$_C_ARRAY_ID][$a]['P_TYPE']);
			echo '</td><td>';
			if (1 !== $b)
				var_dump (self::$_meat_inst[$c_opt][self::$_meat_runtime_define[$a]]['p_type']);
			echo '</td><td>';
			if (1 !== $b)
				var_dump (self::$_INST[self::$_C_ARRAY_ID][$a]['P_BITS']);
			echo '</td><td>';
			if (1 !== $b)
				var_dump (self::$_meat_inst[$c_opt][self::$_meat_runtime_define[$a]]['p_bits']);
			echo '</td><td>';
			if (1 !== $b)
				var_dump (self::$_INST[self::$_C_ARRAY_ID][$a]['PARAMS']);				
			echo '</td><td>';
			if ($b > 0){
				echo "<b>$b</b>";	
			}else{
				var_dump (self::$_meat_units[$a][CODE][98]);
			}
			echo '</td>';
			echo '</tr>';
		}			
	}
	private static function meat_shower_02($front_index,$split_point,$c_obj,$behind_index){
		echo '<font color=blue><b>';
		echo '<br>Fail.1:not in $_meat_inst';
		echo '<br>Fail.2:Instruction usable conflict';
		echo '<br>Fail.3:included memory opt';
		echo '<br>Fail.4:no register can be writed';
		echo '<br>Fail.5:no meat_inst can be used';
		echo '<br>Fail.6:no legal mem address to fit';		
		echo '<br>Fail.7:no legal mem bits to fit';
		echo '<br>Fail.8:fail to generate Meat Instruction';
		echo '</b></font>';
		echo '<table border = 1><tr><td>Type</td><td>ID</td><td>inst</td><td>type</td><td>inst_types</td><td>bits</td><td>inst_bits</td><td>params</td><td>doit</td></tr>';
		self::meat_shower_02_01($front_index,'F');		
		echo '<tr bgcolor="yellow"><td><b>C</b></td><td>'.$split_point.'</td><td><b>';
		echo self::$_IDX[self::$_C_ARRAY_ID]['OPT'][self::$_INST[self::$_C_ARRAY_ID][$split_point]['OPERATION']];
		echo '</b></td><td>';
		var_dump (self::$_INST[self::$_C_ARRAY_ID][$split_point]['P_TYPE']);
		echo '</td><td>-</td><td>';
		var_dump (self::$_INST[self::$_C_ARRAY_ID][$split_point]['P_BITS']);
		echo '</td><td>-</td><td>';
		if (isset(self::$_INST[self::$_C_ARRAY_ID][$split_point]['PARAMS'])){
			var_dump (self::$_INST[self::$_C_ARRAY_ID][$split_point]['PARAMS']);
		}else{
			echo '-';
		}
		echo '</td><td>';
		var_dump ($c_obj);
		echo '</td></tr>';
		self::meat_shower_02_01($behind_index,'N');
		echo '</table>';
	}
}



?>