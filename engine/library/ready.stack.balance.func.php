<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

class StackBalance{
	private static $_cut_points;   	 	 // 切割点
	private static $_uniqu_thread;       // 切割后的流程 
	private static $_uniqu_thread_index; // 切割后的流程 编号
	private static $_stack_effects;      

	private static $_block_array;
	private static $_block_index;
	private static $_unit;       // 数据单位
	private static $_unit_index;
	private static $_unit_reverse;

	// 取得stack 写操作指令 并存入 $_cut_points
	private static function get_cut_points_from_stack_write($asm){
		foreach ($asm as $a => $b){
			$c_stackeffect = Instruction::stackEffect($b);
			if (0 !== $c_stackeffect){ // stack write
				if (true === $c_stackeffect){ // unkown ,set cut flag it
					self::$_cut_points[$a] |= 4;
				}else{
					self::$_stack_effects[$a] = $c_stackeffect;
				}
			}
		}
	}
	// 取得反向跳转的指令(label) 并存入 $_cut_points
	private static function get_cut_points_from_backjmp($jmp_array){
		foreach ($jmp_array as $a => $b){
			if ($b < $a){
				self::$_cut_points[$b] |= 1;
				self::$_cut_points[$a] |= 2;
			}
		}
	}
	// 插入单位到当前 block
	private static function new_block_unit($id,$direct){
		if (isset(self::$_unit_reverse[$id][$direct])){
			$uid = self::$_unit_reverse[$id][$direct];
		}else{
			$uid = self::$_unit_index;
			self::$_unit_reverse[$id][$direct] = $uid;
			self::$_unit[$uid] = array($id,$direct);			
			self::$_unit_index ++;
		}
		self::$_uniqu_thread[self::$_uniqu_thread_index][] = $uid;
	}
	// 执行流程 ，按切割点切割
	private static function enum_list_gen_uniqu_thread($list){
		$prev = false;
		foreach ($list as $a){
			$inc_block_index = false;
			if ('-' === $a){
				$prev = false;
				continue;
			}
			// 上一指令 的 后位
			if (false !== $prev){
				self::new_block_unit($prev,N);
			}
			$prev = $a;
			if (isset(self::$_cut_points[$a])){ // 切割点 属性
				if (self::$_cut_points[$a] & 1){
					self::$_uniqu_thread_index ++;
				}
				if (self::$_cut_points[$a] & 2){
					$inc_block_index = true;
				}
				if (self::$_cut_points[$a] & 4){
					$inc_block_index = true;
				}
			}
			// 当前指令 的 前位
			self::new_block_unit($a,P);
			
			if ($inc_block_index){
				self::$_uniqu_thread_index ++;
			}
		}
		// var_dump (self::$_uniqu_thread);
	}	
	// 去除 相同流程
	private static function filter_same_thread(){
		$tmp = self::$_uniqu_thread;
		self::$_uniqu_thread = array();
		while ($a = array_pop($tmp)){
			if (false === in_array($a,$tmp)){
				self::$_uniqu_thread[] = $a;
			}
		}
	}
	// 
	private static function stack_effects_translate(){
		$tmp = self::$_stack_effects;
		self::$_stack_effects = array();
		foreach (self::$_unit as $a => $b){
			if (10 == $b[1]){
				if (isset($tmp[$b[0]])){
					self::$_stack_effects[$a] = $tmp[$b[0]];
				}
			}
		}
	}
	// 转换生成2个指向表(_unit -> DList_unit[dir])(DList_unit[dir] -> _unit)
	// $table_1 = array(); // [dlist_id][dir]  = universe_id
	// $table_2 = array(); // [universe_id][0] = dlist_id
	//					   // [universe_id][1] = dir
	private static function table_trans($dlist,&$table_1,&$table_2){
		$table_tmp = array(); // [asm_line][dir] = universe_id
		foreach (self::$_unit as $id => $a){
			$table_tmp[$a[0]][$a[1]] = $id;
		}		
		foreach ($dlist as $dlist_id => $a){
			if (isset($a[C])){
				if ($a[C] >= 0){
					$asm_line = $a[C];
					if (isset($table_tmp[$asm_line][P])){
						$table_1[$dlist_id][P] = $table_tmp[$asm_line][P];
						$table_2[$table_tmp[$asm_line][P]][0] = $dlist_id;
						$table_2[$table_tmp[$asm_line][P]][1] = P;
					}					
					if (isset($table_tmp[$asm_line][N])){
						$table_1[$dlist_id][N] = $table_tmp[$asm_line][N];						
						$table_2[$table_tmp[$asm_line][N]][0] = $dlist_id;
						$table_2[$table_tmp[$asm_line][N]][1] = N;
					}
				}
			}
		}
		// echo "<br>table tmp";
		// var_dump ($table_tmp);
		// echo "<br>table 1";
		// var_dump ($table_1);
		// echo "<br>table 2";
		// var_dump ($table_2);
		// var_dump ($dlist);
		// var_dump (self::$_unit);
	}
	// init
	private static function init (){
		self::$_cut_points   = array();
		self::$_stack_effects = array();
		self::$_uniqu_thread = array();
		self::$_uniqu_thread_index = 1;
		self::$_block_array = array();
		self::$_block_index = 1;
		self::$_unit = array();
		self::$_unit_index = 1;
		self::$_unit_reverse = array();
	}	
	// readme.stack.balance.txt
	public static function start($jmp_array,$asm,$list_array,$d_list){
		self::init();
		// 获取 block 切割点
		self::get_cut_points_from_backjmp($jmp_array);		
		self::get_cut_points_from_stack_write($asm);
		// 穷举 执行流程 ，按切割点切割
		// var_dump ($list_array);		
		foreach ($list_array as $a => $b){
			self::enum_list_gen_uniqu_thread($b);
			self::$_uniqu_thread_index ++;
		}
		// 去除 相同流程
		self::filter_same_thread();
		// 转换 stack effect -> units
		if ((count(self::$_stack_effects)) > 0){
			self::stack_effects_translate();
		}
		self::shower_01($asm);
		SteroGraphic::init();
		foreach (self::$_uniqu_thread as $a){
			SteroGraphic::import($a,self::$_stack_effects);
		}
		// 生成2个指向表
		$table_1 = array();
		$table_2 = array();
		self::table_trans($d_list,$table_1,$table_2);
		// SteroGraphic::show();
		SteroGraphic::delete_min_stero(2); // drop all stero space which units less than 2
		$ret['units'] = self::$_unit;
		$ret['universe'] = SteroGraphic::serialize();
		$ret['table_1'] = $table_1;
		$ret['table_2'] = $table_2;
		echo '<br>*********************************************<br>';
		SteroGraphic::show();		
		return $ret;
	}

	// 回显
	// 显示 uniqu block 分配
	private static function shower_01($asm){
		echo '<font color=blue><b>threads</b></font>';
		echo '<table border=1>';
		echo '<tr>';
		echo '<td>thread id</td><td><b>(stack effects)</b>{<i>block_unit</i>|inst id|inst|<font color=blue>Prev</font> | <font color=red>Next</font>}</td>';
		foreach (self::$_uniqu_thread as $a => $b){
			echo '<tr><td>';
			echo "$a";
			echo '</td>';
			echo '<td>';
			foreach ($b as $d){
				if (9 == self::$_unit[$d][1]){
					echo '<font color=blue>';
				}else{
					if (isset(self::$_stack_effects[$d])){
						echo '<b>(';
						echo self::$_stack_effects[$d];
						echo ')</b>';
					}
					echo '<font color=red>';
				}
				echo '{<i>'."$d".'</i>|';
				echo self::$_unit[$d][0];
				echo '|';
				echo $asm[self::$_unit[$d][0]][OPERATION];				
				echo '}</font>';
			}			
			echo '</td>';
			echo '</tr>';
		}

		echo '</tr>';
		echo '</table>';
	}
}

?>