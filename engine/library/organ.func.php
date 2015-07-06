<?php

//维护，操作 Organs 产生的数据结构
class OrgansOperator{

	private static $_Asm_Result_Index;
	private static $_Asm_Result;
	private static $_soul_usable;
	private static $_soul_forbid;
	private static $_fat_usable;
    
    // init
	public static function init($sec){
        global $StandardAsmResultArray;
        global $soul_usable;
        global $soul_forbid;        

        self::$_Asm_Result  = $StandardAsmResultArray[$sec];       
		self::$_soul_usable = $soul_usable[$sec];
		self::$_soul_forbid = $soul_forbid[$sec];

		self::$_fat_usable = array(); // TODO: any valid Fat in ready ?
		
		// var_dump (max(array_keys(self::$_Asm_Result)));
		self::$_Asm_Result_Index = 1 + max(array_keys(self::$_Asm_Result));
	}

	// add usable reg (['EAX'][BITS] = true)
	public static function addUsableReg($unit,$dir,$reg,$ignor_forbid){
		if (isset(self::$_Asm_Result[$unit])){
			if (!isset(self::$_soul_forbid[$unit][$dir][NORMAL][$reg])){
				self::$_soul_usable[$unit][$dir][NORMAL_WRITE_ABLE][$reg][OPT_BITS] = true;
				return true;
			}else{
				if ($ignor_forbid){
					unset (self::$_soul_forbid[$unit][$dir][NORMAL][$reg]);
					self::$_soul_usable[$unit][$dir][NORMAL_WRITE_ABLE][$reg][OPT_BITS] = true;
					// cancel all mem address valid flag (which include $reg)
					if (isset(self::$_soul_usable[$unit][$dir][MEM_OPT_ABLE])){
						$tmp = self::$_soul_usable[$unit][$dir][MEM_OPT_ABLE];
						foreach ($tmp as $a => $mem_idx){
							if (ValidMemAddr::is_reg_include($mem_idx,$reg)){
								unset(self::$_soul_usable[$unit][$dir][MEM_OPT_ABLE][$a]);
							}
						}
						if (empty(self::$_soul_usable[$unit][$dir][MEM_OPT_ABLE])){
							unset (self::$_soul_usable[$unit][$dir][MEM_OPT_ABLE]);
						}
					}
					return true;
				}
			}
		}
		return false;
	}
	// new unit
	public static function newUnit($organ,$usable=false,$fat=false){
		$idx = self::$_Asm_Result_Index;
		self::$_Asm_Result_Index ++;
		if (false !== $usable){
			GeneralFunc::soul_stack_set_single($organ,$usable[P],$usable[N]);
		}
		self::$_Asm_Result[$idx] = $organ;
		if (isset($usable[P])){
			self::$_soul_usable[$idx][P] = $usable[P];
		}
		if (isset($usable[N])){
			self::$_soul_usable[$idx][N] = $usable[N];
		}
		if (false !== $fat){
			self::$_fat_usable[$idx] = $fat;
		}
		return $idx;
	}
	// clone usable(s)
	public static function cloneUsables($src,$direction,$dst){
		self::$_soul_usable[$dst][P] = self::$_soul_usable[$src][$direction];
		self::$_soul_usable[$dst][N] = self::$_soul_usable[$src][$direction];
		self::$_soul_forbid[$dst][P] = self::$_soul_forbid[$src][$direction];
		self::$_soul_forbid[$dst][N] = self::$_soul_forbid[$src][$direction];
	}
	// gen DList Unit array by organ unit
	public static function getDListUnitArray($unit){
		$ret = array();
		$ret[C] = $unit;
		if (isset(self::$_Asm_Result[$unit][LABEL])){
			$ret[LABEL] = self::$_Asm_Result[$unit][LABEL];
		}elseif (GenerateFunc::is_effect_ipsp(self::$_Asm_Result[$unit],1)){
			$ret['ipsp'] = true;
		}
		return $ret;
	}

	// get CODE
	public static function getCode($id){
		return isset(self::$_Asm_Result[$id])?self::$_Asm_Result[$id]:false;
	}

	// get usable
	public static function getUsable($id){
		return isset(self::$_soul_usable[$id])?self::$_soul_usable[$id]:false;
	}

	// get fat
	public static function getFat($id){
		return isset(self::$_fat_usable[$id])?self::$_fat_usable[$id]:false;
	}

	public static function FilterMemUsable($unit){
		GenerateFunc::doFilterMemUsable(self::$_soul_usable[$unit[C]][P][MEM_OPT_ABLE]);	
		GenerateFunc::doFilterMemUsable(self::$_soul_usable[$unit[C]][N][MEM_OPT_ABLE]);
	}

	// 前(后)是否可插入脂肪(fat)
	// params:  $unit  :  DList's unit
	// 			$direct:  1 prev   2 next
	//
	public static function CheckFatAble($unit,$direct = 1){
		if (isset(self::$_fat_usable[$unit])){			
			if ($direct == self::$_fat_usable[$unit]){
				return true;
			}
		}
		return false;			
	}

	// 生成 organs 处理流程 数组
	public static function GenOrganProcess($user_strength,$count,$max_strength){

			$default_max = 0;
			if (isset($user_strength['default'])){
				$default_max = intval(ceil(($count * $user_strength['default'])/100));
			}
			//
			if (!isset($user_strength[POLY]['max'])){
				$user_strength[POLY]['max'] = $default_max;			
			}
			if (!isset($user_strength[POLY]['min'])){
				$user_strength[POLY]['min'] = intval($user_strength[POLY]['max']/2);
			}elseif ($user_strength[POLY]['max'] < $user_strength[POLY]['min']){
				$user_strength[POLY]['max'] = $user_strength[POLY]['min'];
			} 
			//			
			if (!isset($user_strength[BONE]['max'])){
				$user_strength[BONE]['max'] = $default_max;			
			}
			if (!isset($user_strength[BONE]['min'])){
				$user_strength[BONE]['min'] = intval($user_strength[BONE]['max']/2);
			}elseif ($user_strength[BONE]['max'] < $user_strength[BONE]['min']){
				$user_strength[BONE]['max'] = $user_strength[BONE]['min'];
			}
			//
			if (!isset($user_strength[MEAT]['max'])){
				$user_strength[MEAT]['max'] = $default_max;			
			}
			if (!isset($user_strength[MEAT]['min'])){
				$user_strength[MEAT]['min'] = intval($user_strength[MEAT]['max']/2);
			}elseif ($user_strength[MEAT]['max'] < $user_strength[MEAT]['min']){
				$user_strength[MEAT]['max'] = $user_strength[MEAT]['min'];
			}

			$c_poly_strength = mt_rand($user_strength[POLY]['min'],$user_strength[POLY]['max']);
			$c_bone_strength = mt_rand($user_strength[BONE]['min'],$user_strength[BONE]['max']);
			$c_meat_strength = mt_rand($user_strength[MEAT]['min'],$user_strength[MEAT]['max']);

			//是否有强度超过 最大强度设置
			if (false !== $max_strength){
				if ($c_poly_strength > $max_strength){
					GeneralFunc::LogInsert('the strength number exceeds maximum of '.POLY.', ('.$c_poly_strength.' -> '.$max_strength.')',3);
					$c_poly_strength = $max_strength;
				}
				if ($c_bone_strength > $max_strength){
					GeneralFunc::LogInsert('the strength number exceeds maximum of '.BONE.', ('.$c_bone_strength.' -> '.$max_strength.')',3);
					$c_bone_strength = $max_strength;
				}
				if ($c_meat_strength > $max_strength){
					GeneralFunc::LogInsert('the strength number exceeds maximum of '.MEAT.', ('.$c_meat_strength.' -> '.$max_strength.')',3);
					$c_meat_strength = $max_strength;
				}
			}

			$process = array();	

			
			for ($i = $c_poly_strength;$i > 0;$i--){		    
				$process[] = POLY;
			}    

			for ($i = $c_bone_strength;$i > 0;$i--){		    
				$process[] = BONE;
			}
			
			for ($i = $c_meat_strength;$i > 0;$i--){		    
				$process[] = MEAT;
			}
			
			shuffle($process);

			return $process;
	}

	public static function show($idx){
		echo '<table border=1>';
		echo '<tr><td>prev usable</td><td>prev forbid</td><td>asm</td><td>next forbid</td><td>next uable</td></tr>';
		echo '<tr>';
		echo '<td>';
		var_dump (self::$_soul_usable[$idx][P]);
		echo '</td>';
		echo '<td>';
		var_dump (self::$_soul_forbid[$idx][P]);
		echo '</td>';
		echo '<td>';
		var_dump (self::$_Asm_Result[$idx]);
		echo '</td>';
		echo '<td>';
		var_dump (self::$_soul_forbid[$idx][N]);
		echo '</td>';
		echo '<td>';
		var_dump (self::$_soul_usable[$idx][N]);
		echo '</td>';		
		echo '</table>';
	}
   
}


?>