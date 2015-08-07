<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

define ('DEBUG_ECHO',true);

//显示
$INFO_SHOW = true;

echo '<head><meta charset="utf-8"></head>';

class DebugShowFunc{

	//显示 sp_define 变量
	public static function my_shower_09($sp_define_pattern,$sp_define,$sp_array,$sp_index_array){
		echo '<br>======== sp_define 变量 =======<br>';
		echo '<table border=1><tr bgcolor=pink><td>';
		var_dump ($sp_define);
		echo '</td><td>'.$sp_define_pattern.'</td>';	
		echo '<td>';
		var_dump ($sp_index_array);
		echo '</td>';
		echo '<td>';
		var_dump ($sp_array);
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}

	//显示 灵魂 gen阶段 受 用户设置 stock 指针影响 而 ipsp 被设置的情况
	public static function my_shower_08($sp_define,$array,$list,$soul){
		echo '<br>======== 显示 灵魂 gen阶段 受 用户设置 stock 指针影响 而 ipsp 被设置的情况 =======<br>';
		echo "<table border = 1>";
		$color = '#C0C0C0';
		echo '<tr><td>line</td><td>prefix</td><td>instruction</td><td>p0</td><td>p1</td><td>p2</td><td>ipsp</td><td>sp defined</td></tr>';
		foreach ($array as $a => $b){
			if ('#C0C0C0' == $color){
				$color = '#FFFFFF';
			}else{
				$color = '#C0C0C0';
			}
			$c = $list[$a][C];
			echo '<tr bgcolor='.$color.'><td>'.$c.'</td><td>';
			if (isset($soul[$c][PREFIX])){
				echo $soul[$c][PREFIX];
			}
			echo '</td><td>';
			if (isset($soul[$c][OPERATION])){
				echo $soul[$c][OPERATION];
			}
			echo '</td><td>';
			echo $soul[$c][PARAMS][0];
			echo '</td><td>';
			echo $soul[$c][PARAMS][1];
			echo '</td><td>';
			if (isset($soul[$c][PARAMS][2])){
				echo $soul[$c][PARAMS][2];
			}
			echo '</td>';
			if (true === $list[$a]['ipsp']){
				echo '<td bgcolor=red>保护';
			}
			echo '</td><td bgcolor=yellow>'.$sp_define.'</td></tr>';
		}	
		echo '</table>';
	}

	//显示 处理 目标段 信息 (比较 usr config 的配置信息)
	public static function my_shower_07($array,$ignore_sec){
		echo '<br>============= 处理目标段信息 ( $myTables[CodeSectionArray] ) 比较 -> usr.config配置段信息 ============= <br>';
		echo "<table border=1>";
		echo '<tr bgcolor="yellow"><td>ID</td><td><font color=blue>name</font></td><td>base</td><td>sec_name</td><td>PointerToRawData</td><td>SizeOfRawData</td><td>PointerToRelocation</td><td>NumberOfRelocation</td><td>Characteristics_L</td><td>Characteristics_H</td></tr>';
		foreach ($array as $a => $b){
			echo '<tr>';
			echo '<td>'.$a.'</td>';
			echo '<td><font color=blue>'.$b['name'].'</font></td>';
			echo '<td>';
			if (isset($b['base'])){
				echo $b['base'];
			}
			echo '</td>';
			echo '<td>';
			if (isset($b['sec_name'])){
				echo $b['sec_name'];
			}
			echo '</td>';
			echo '<td>';
			if (isset($b['PointerToRawData'])){
				echo $b['PointerToRawData'];
			}
			echo '</td>';
			echo '<td>';
			if (isset($b['SizeOfRawData'])){
				echo $b['SizeOfRawData'];
			}
			echo '</td>';
			echo '<td>';
			if (isset($b['PointerToRelocation'])){
				echo $b['PointerToRelocation'];
			}
			echo '</td>';
			echo '<td>';
			if (isset($b['NumberOfRelocation'])){
				echo $b['NumberOfRelocation'];
			}
			echo '</td>';
			echo '<td>';
			if (isset($b['Characteristics_L'])){
				echo $b['Characteristics_L'];
			}
			echo '</td>';
			echo '<td>';
			if (isset($b['Characteristics_H'])){
				echo $b['Characteristics_H'];
			}
			echo '</td>';
			echo '</tr>';
		}
		foreach ($ignore_sec as $a => $b){
			echo '<tr>';
			echo '<td><font color=red>忽略段</font></td>';
			echo '<td><font color=red>';
			echo "$a";
			echo '</font></td>';
			echo '<td>-</td>';
			echo '<td>-</td>';
			echo '<td>-</td>';
			echo '<td>-</td>';
			echo '<td>-</td>';
			echo '<td>-</td>';
			echo '<td>-</td>';
			echo '<td>-</td>';
			echo '</tr>';
		}
		echo '</table>';    
	}

	//显示骨架(预分配方案(ipsp已完成)，以及STACK冲突判断结果)
	public static function my_shower_05($c_bone_model,$bone_obj,$stack_unusable,$isConflict,$conflict_position){
		// global $soul_writein_Dlinked_List;
		echo "<br>============= 显示骨架(预分配方案(ipsp已完成)，以及STACK冲突判断结果) ============= <br>";
		
		if (!$isConflict){
			unset($conflict_position);
			echo "<br><font color='blue'><b>conflict !! NO</b></font>";
		}else{
			echo "<br><font color='red'><b>conflict !! YES</b></font>";
		}

		$boned_soul_array = array();
		
		$i = 0;
		foreach ($c_bone_model['direct'] as $a => $b){
			if ($b){
				$i ++;
				for (;$i<=$b;$i++){
					$boned_soul_array[$a][$i] = $bone_obj[$i];
				}
				$i = $b;			
			}	    
		}

		//var_dump ($c_bone_model['direct']);
		//var_dump ($bone_obj);
		echo '<table border = 1><tr><td>Bone Number</td><td>BoneObj编号</td><td>链表编号</td><td>Type</td><td>Code</td><td>Params</td><td>usable[stack]</td><td>stack_unusable结果[同项]</td><td>冲突</td></tr>';
		foreach ($c_bone_model[CODE] as $a => $b){
			if (true === $b){
				if ((isset($boned_soul_array[$a]))and(count ($boned_soul_array[$a]) > 0)){
					$rowspan = count($boned_soul_array[$a]);
					$show_rowspan = true;
					foreach ($boned_soul_array[$a] as $z => $y){
						echo '<tr>';
						if ($show_rowspan){
							echo '<td rowspan='.$rowspan.'>';
							echo $a;
							echo '</td>';
						}
						echo '<td>';
						echo "$z".'</td><td>';
						echo "$y".'</td><td>';
						$comment = OrgansOperator::echoComment($y);
						echo "$comment";						
						echo '</td><td>';						
						$c_code    = OrgansOperator::getCode($y);
						$c_usable  = OrgansOperator::getUsable($y);
						if (isset($c_code[LABEL])){
							var_dump ($c_code[LABEL]);
						}
						if (isset($c_code[OPERATION])){
							var_dump ($c_code[OPERATION]);
						}
						echo '</td><td>';
						if (isset($c_code[PARAMS])){
							var_dump ($c_code[PARAMS]);
						}
						// if (false !== strpos($comment, "s33")){
						// 	echo '<br>HIRO test:';							
						// 	var_dump ($c_code);
						// 	var_dump($y);
						// 	var_dump (OrgansOperator::isIPSPUnit($y));
						// }
						echo '</td>';
						echo '<td>';

						if (OrgansOperator::isUsableStack($y,P)){
							echo '<font color=blue>前方Stack可用</font>';
						}else{
							echo '<font color=red>前方Stack禁用</font>';
						}					
						if (OrgansOperator::isUsableStack($y,N)){
							echo '<br><font color=blue>后方Stack可用</font>';
						}else{
							echo '<br><font color=red>后方Stack禁用</font>';
						}	
						echo '</td>';
						if ($show_rowspan){
							echo '<td rowspan='.$rowspan.'>';
							var_dump ($stack_unusable[$a]);
							echo '</td>';
							echo '<td rowspan='.$rowspan.'>';
							if ((isset($conflict_position[CODE]))and($a === $conflict_position[CODE])){
								var_dump ($conflict_position);
							}
							echo '</td>';
						}
						$show_rowspan = false;
					}
				}else{
					echo '<tr><td>'.$a.'</td><td>???</td><td>???</td><td>???</td><td>???</td><td>Empty or Not Copy Yet</td>';
				}
				echo '</tr>';
			}else{ //骨架本身
				echo '<tr bgcolor="yellow"><td>'.$a.'</td><td></td><td></td><td>当前骨架</td><td>';
				var_dump ($b);
				echo '</td><td></td>';
				$c_count = 0;
				if (isset($b[PARAMS])){
					$c_count = count($b[PARAMS]);
				}
				if (isset($b[OPERATION])){
					$tmp = Instruction::getInstructionOpt($b[OPERATION],$c_count);
				}
				echo '<td>';
				if (isset($tmp[STACK])){
					echo '<font color=blue>Stack需要</font>';
				}
				echo '</td>';
				echo '<td></td>';
				echo '<td>';
				if ((isset($conflict_position[BONE]))and($a === $conflict_position[BONE])){
					var_dump ($conflict_position);
				}
				echo '</td>';
				echo '</tr>';
			}
		}
		echo '</table>';

	}

	//多重数组比较
	public static function compare_multi_array(&$a,&$b){
		$tmp = $a;
		foreach ($tmp as $k => $v){
			if (isset($b[$k])){
				if (is_array($v)){
					if (true === self::compare_multi_array($a[$k],$b[$k])){
						unset($a[$k]);
						unset($b[$k]);
					}
				}elseif ($b[$k] === $v){
					unset($a[$k]);
					unset($b[$k]);
				}
			}
		}
		if ((empty($a)) and (empty($b))){
			return true;
		}
		return false;

	}

	//显示链表(按序)以及rel_jmp结构
	public static function my_shower_04($sec,$rel_jmp_range,$rel_jmp_pointer,$soul_writein_Dlinked_List_start,$soul_writein_Dlinked_List){

		echo "<br>============= 显示链表(按序),OpCode(without Fat) 以及rel_jmp结构 [Section Number: $sec] ============= <br>";
		echo '<table border = 1><tr><td>List Number</td><td>List contents</td><td>opcode</td><td>range</td><td>pointer</td></tr>';
		$c_unit = $soul_writein_Dlinked_List_start;
		while (true){
			echo '<tr><td>';
			echo '['."$c_unit".']';
			echo '</td><td>';
			var_dump ($soul_writein_Dlinked_List[$c_unit]);
			echo '</td><td>';
			//抽取代码
			if (isset($soul_writein_Dlinked_List[$c_unit][LABEL])){
				echo $soul_writein_Dlinked_List[$c_unit][LABEL];
			}else{
				$c_opt = OrgansOperator::getCode($soul_writein_Dlinked_List[$c_unit][C]);
				var_dump ($c_opt);
			}
			echo '</td><td>';
			var_dump ($rel_jmp_range[$c_unit]);
			echo '</td><td>';
			var_dump ($rel_jmp_pointer[$c_unit]);
			echo '</td></tr>';
			if (false === $soul_writein_Dlinked_List[$c_unit][N]){
				break;
			}
			$c_unit = $soul_writein_Dlinked_List[$c_unit][N];
		}
		echo '</table>';
	}




	//显示多态 (支持 随机的 强度 表示),取代 function my_shower_02
	public static function my_shower_03($org_List_index,$insert_List_array){
		// global $soul_writein_Dlinked_List;
		
		echo "<br>============= 多态 结构 ===============<br>";
		//foreach ($StandardAsmResultArray as $a => $b){
		echo "<br>";
		echo '<table border = 1><tr><td>sub tree</td><td>line</td><td>Instruction</td><td>Prev usable</td><td>Next usable</td></tr>';
		
		$type   = '[';
		$type  .= OrgansOperator::echoComment($org_List_index);
		$type  .= ']';
		$d      = OrgansOperator::getCode($org_List_index);
		$usable = OrgansOperator::getUsable($org_List_index);

		echo '<tr><td>'.'0'.'</td><td>'.$org_List_index.'</td><td>';
		echo "$type";
		if (isset($d[PREFIX])){
			foreach ($d[PREFIX] as $z => $y){
				echo "$y ";
			}
		}
		echo $d[OPERATION];
		if (isset($d[PARAMS])){
			foreach ($d[PARAMS] as $z => $y){
				echo " $y ,";
			}
		}
		if (isset($d[P_TYPE])){
			var_dump ($d[P_TYPE]);
		}
		if (isset($d[P_BITS])){
			var_dump ($d[P_BITS]);
		}
		echo "<br>Stack:";
		if (OrgansOperator::isEffectStack($org_List_index)){	
			echo "影响";
		}else{
			echo "不影响";
		}
		echo '</td><td>';
		///////////////////////////////////////////////////////////////////// Prev
		if (isset($usable[P][FLAG_WRITE_ABLE])){
			echo '<font color=pink>';
			foreach ($usable[P][FLAG_WRITE_ABLE] as $z => $y){
				echo " $z;";
			}
			echo '</font>';
		}
		if (isset($usable[P][NORMAL_WRITE_ABLE])){
			echo '<font color=blue>';
			foreach ($usable[P][NORMAL_WRITE_ABLE] as $z => $y){
				echo " $z{";
				foreach ($y as $v => $w){
					echo $v.',';  
				}
				echo "};";
			}
			echo '</font>';
		}
		if (isset($usable[P][MEM_OPT_ABLE])){
			echo '<font color=red>';
			foreach ($usable[P][MEM_OPT_ABLE] as $z => $y){
				$v  = ValidMemAddr::get($y);
				$zz = $v[CODE];
				echo '<br>'.$y.':'.$zz.' {'.$v[BITS].'位 - ';
				if ($v[OPT] == 1)
					echo "读; ";
				elseif ($v[OPT] == 2)
					echo "写; ";
				elseif ($v[OPT] == 3)
					echo "读写; ";
				else
					echo "<font color=red><b>未知?</b></font>; ";	

				if (isset($v[REG])){
					echo '(';
					foreach ($v[REG] as $u => $t){
						echo "$t,";    
					}
					echo ')';
				}
			}
			echo '</font>';
		}
		if (!OrgansOperator::isUsableStack($org_List_index,P)){
			echo ' <b>堆栈禁用</b> ';
		}
		echo '</td><td>';
		///////////////////////////////////////////////////////////////////// Next
		if (isset($usable[N][FLAG_WRITE_ABLE])){
			echo '<font color=pink>';
			foreach ($usable[N][FLAG_WRITE_ABLE] as $z => $y){
				echo " $z;";
			}
			echo '</font>';
		}
		if (isset($usable[N][NORMAL_WRITE_ABLE])){
			echo '<font color=blue>';
			foreach ($usable[N][NORMAL_WRITE_ABLE] as $z => $y){
				echo " $z{";
				foreach ($y as $v => $w){
					echo $v.',';  
				}
				echo "};";
			}
			echo '</font>';
		}
		if (isset($usable[N][MEM_OPT_ABLE])){
			echo '<font color=red>';
			foreach ($usable[N][MEM_OPT_ABLE] as $z => $y){
				$v  = ValidMemAddr::get($y);
				$zz = $v[CODE];					
				echo '<br>'.$y.':'.$zz.' {'.$v[BITS].'位 - ';
				if ($v[OPT] == 1)
					echo "读; ";
				elseif ($v[OPT] == 2)
					echo "写; ";
				elseif ($v[OPT] == 3)
					echo "读写; ";
				else
					echo "<font color=red><b>未知?</b></font>; ";	
				
				if (isset($v[REG])){
					echo '(';
					foreach ($v[REG] as $u => $t){
						echo "$t,";    
					}
					echo ')';
				}
			}
			echo '</font>';
		}
		if (!OrgansOperator::isUsableStack($org_List_index,N)){
			echo ' <b>堆栈禁用</b> ';
		}
		echo '</td></tr>';
		
		self::my_shower_03_func_1 ($insert_List_array);

		echo "</table>";
	}


	//递归 多态 子数
	private static function my_shower_03_func_1($insert_List_array){
        // foreach ($c_poly_array[CODE] as $a => $d){    
        foreach ($insert_List_array as $z){
        	$a = $z;
        	$d = OrgansOperator::getCode($a);
        	$u = OrgansOperator::getUsable($a);
		
			echo '<tr bgcolor=\'#C0C0C0\'><td>';
			echo '>';
			echo '</td><td>'.$a.'</td><td>';		
			if (isset($d[LABEL])){
				echo $d[LABEL];
			}else{
				if (isset($d[PREFIX])){
					foreach ($d[PREFIX] as $z => $y){
						echo "$y ";
					}
				}
				echo $d[OPERATION];
				if (isset($d[PARAMS])){
					foreach ($d[PARAMS] as $z => $y){
						echo " $y ,";
					}
				}
			}
			if (isset($d[P_TYPE])){
				var_dump ($d[P_TYPE]);
			}
			if (isset($d[P_BITS])){
				var_dump ($d[P_BITS]);
			}
			echo "Stack:";
			if (OrgansOperator::isEffectStack($a)){
				echo "影响";
			}else{
				echo "不影响";
			}
			echo '</td><td>';
			///////////////////////////////////////////////////////////////////// Prev
			if (OrgansOperator::CheckFatAble($a,P)){
				echo "<b>FAT</b>";
			}else{		
				if (isset($u[P][FLAG_WRITE_ABLE])){
					echo '<font color=pink>';
					foreach ($u[P][FLAG_WRITE_ABLE] as $z => $y){
						echo " $z;";
					}
					echo '</font>';
				}
				if (isset($u[P][NORMAL_WRITE_ABLE])){
					echo '<font color=blue>';
					foreach ($u[P][NORMAL_WRITE_ABLE] as $z => $y){
						echo " $z{";
						foreach ($y as $v => $w){
							echo $v.',';  
						}
						echo "};";
					}
					echo '</font>';
				}

				if (isset($u[P][MEM_OPT_ABLE])){
					echo '<font color=red>';
					foreach ($u[P][MEM_OPT_ABLE] as $z => $y){
						$v  = ValidMemAddr::get($y);
						$zz = $v[CODE];							
						echo '<br>'.$y.':'.$zz.' {'.$v[BITS].'位 - ';
						if ($v[OPT] == 1)
							echo "读; ";
						elseif ($v[OPT] == 2)
							echo "写; ";
						elseif ($v[OPT] == 3)
							echo "读写; ";
						else
							echo "<font color=red><b>未知?</b></font>; ";	

						if (isset($v[REG])){
							echo '(';
							foreach ($v[REG] as $t){
								echo "$t,";    
							}
							echo ')';
						}
						echo "}";
					}
					echo '</font>';
				}
				if ((!isset($u[P][STACK]))or(true !== ($u[P][STACK]))){
					echo ' <b>堆栈禁用</b> ';
				}
			}
			echo '</td><td>';
			///////////////////////////////////////////////////////////////////// Next
			if (OrgansOperator::CheckFatAble($a,N)){
				echo "<b>FAT</b>";
			}else{		
				if (isset($u[N][FLAG_WRITE_ABLE])){
					echo '<font color=pink>';
					foreach ($u[N][FLAG_WRITE_ABLE] as $z => $y){
						echo " $z;";
					}
					echo '</font>';
				}
				if (isset($u[N][NORMAL_WRITE_ABLE])){
					echo '<font color=blue>';
					foreach ($u[N][NORMAL_WRITE_ABLE] as $z => $y){
						echo " $z{";
						foreach ($y as $v => $w){
							echo $v.',';  
						}
						echo "};";
					}
					echo '</font>';
				}
				if (isset($u[N][MEM_OPT_ABLE])){
					echo '<font color=red>';
					foreach ($u[N][MEM_OPT_ABLE] as $z => $y){
						$v  = ValidMemAddr::get($y);
						$zz = $v[CODE];						
						echo '<br>'.$y.':'.$zz.' {'.$v[BITS].'位 - ';
						if ($v[OPT] == 1)
							echo "读; ";
						elseif ($v[OPT] == 2)
							echo "写; ";
						elseif ($v[OPT] == 3)
							echo "读写; ";
						else
							echo "<font color=red><b>未知?</b></font>; ";	

						if (isset($v[REG])){
							echo '(';
							foreach ($v[REG] as $t){
								echo "$t,";    
							}
							echo ')';
						}
						echo "}";
					}
					echo '</font>';
				}
				if ((!isset($u[N][STACK]))or(true !== ($u[N][STACK]))){
					echo ' <b>堆栈禁用</b> ';
				}
			}
			echo '</td></tr>';		
		}
	}

	// 指令 一览 表
	public static function my_shower_01($sec,$exec_thread_list){
		global $flag_register_opt_array;
		global $valid_mem_opt_array;

		echo "<br>";
		echo "<b><font color=red>注: </font>可用寄存器(通用/标志)  不可用则不显示  <font color=red><del>显式不可用</del></font></b>";
		echo '<br>################## section: '.$sec.' ##################<br>';
		echo '<table border = 1><tr><td>thread id</td><td>line number</td></tr>';
		if (isset($exec_thread_list)){
			foreach ($exec_thread_list as $c => $d){
				echo "<tr><td>$c</td><td>";
				if (is_array($d)){
					foreach ($d as $e => $f){
						echo "$f , ";
					}
				}
				echo "</td></tr>";
			}
			echo '</table>';
		}
	
		$color = 'white';
		echo '<table border = 1><tr><td>[line]Comment</td><td>prefix</td><td>instruction</td><td>p0</td><td>p1</td><td>p2</td><td>normal regs</td><td>flag regs</td><td>valid mem addr</td><td>stack</td><td>ipsp(ready未定义)</td></tr>';
		$c = OrgansOperator::getBeginUnit();
		while ($c){			
			$c_inst = OrgansOperator::getCode($c);
			$c_usable = OrgansOperator::getUsable($c);
			$c_forbid = OrgansOperator::getForbid($c);
			$c_ipsp   = OrgansOperator::isIPSPUnit($c);
			if ($color == 'white')
				$color = '#C0C0C0';
			else
				$color = 'white';
			//prev
			echo '<tr bgcolor='."$color".'><td><b><-</b></td><td><b>Prev</b></td>';
			echo '<td><b>usable</b></td><td><b>record</b></td><td></td><td></td><td>';
			if (isset($c_usable[P][NORMAL_WRITE_ABLE])){
				foreach ($c_usable[P][NORMAL_WRITE_ABLE] as $z => $v){
					foreach ($v as $x => $w){
						echo Instruction::getRegByIdxBits($x,$z);
						echo " , ";
					}					
				}
			}
			if (isset($c_forbid[P][NORMAL])){
				echo '<font color = red>';
				foreach ($c_forbid[P][NORMAL] as $z => $v){
					echo '<del>';
					foreach ($v as $x => $w){
						if (!Instruction::getRegByIdxBits($x,$z)){
							echo "$x - $z";
						}else
							echo Instruction::getRegByIdxBits($x,$z);
						echo " , ";
					}					
					echo '</del>';
				}
				echo '</font>';
			}
			echo '</td><td>';
			if (isset($c_usable[P][FLAG_WRITE_ABLE])){
				foreach ($c_usable[P][FLAG_WRITE_ABLE] as $z => $v){
					echo " $z ,";
				}
			}
			if ((isset($c_forbid[P][FLAG]))and(is_array($c_forbid[P][FLAG]))){
				echo '<font color = red>';
				foreach ($c_forbid[P][FLAG] as $z => $v){
					echo '<del>';
					echo " $z";
					echo '</del>';
					echo ',';
				}
				echo '</font>';
			}			
			echo '</td><td>';
			if ((isset($c_usable[P][MEM_OPT_ABLE]))and(is_array($c_usable[P][MEM_OPT_ABLE]))){
				foreach ($c_usable[P][MEM_OPT_ABLE] as $z => $v){
					$v = ValidMemAddr::get($v);						
					$z = $v[CODE];
					echo $z.' {'.$v[BITS].'位 - ';
					if ($v[OPT] == 1)
						echo "读; ";
					elseif ($v[OPT] == 2)
						echo "写; ";
					elseif ($v[OPT] == 3)
						echo "读写; ";
					else
						echo "<font color=red><b>未知?</b></font>; ";	

					if ((isset($v[REG]))and(is_array($v[REG]))){
						echo '(';
						foreach ($v[REG] as $u => $t){
							echo "$t,";    
						}
						echo ')';
					}
					echo "}<br>";
				}
			}
			echo '</td>';
			if (OrgansOperator::isUsableStack($c,P)){
				echo '<td>可用';
			}else{
				echo '<td bgcolor=red> 禁用';				
			}
			echo '</td>';
			echo '<td></td>';
			echo '</tr>';
			///////////////////////////////////////////////////
			//main
			echo '<tr bgcolor='."$color".'><td>';
			echo "[$c]";
			$c_comment = OrgansOperator::echoComment($c);
			echo "$c_comment";
			echo '</td><td>';

			if ($tmp = OrgansOperator::getLabel($c)){
				echo '<b><font color = red>Label</font></b></td><td>'.$tmp.'</td><td></td><td></td><td></td><td></td><td></td><td></td>';
			}else{
				if ((isset($c_inst[PREFIX]))and(is_array($c_inst[PREFIX]))){
					foreach ($c_inst[PREFIX] as $z => $y){
						echo "$y ";
					}
				}
				echo '</td><td>';
				if (isset($c_inst[OPERATION])){
					echo $c_inst[OPERATION];
				}
				for ($w = 0;$w < 3;$w++){
					echo '</td><td>';
					if (isset($c_inst[P_TYPE][$w])){
						if ('r' == $c_inst[P_TYPE][$w]){
							echo "<font color=red>";
						}elseif('m' == $c_inst[P_TYPE][$w]){
							echo "<font color=blue>";
						}elseif('i' == $c_inst[P_TYPE][$w]){
							echo "<font color=black>";
						}
					}
					if (isset($c_inst[PARAMS][$w])){
						if ($c_inst[PARAMS][$w]){
							echo $c_inst[PARAMS][$w];
							if (isset($c_inst[P_BITS][$w])){
								echo '[<b>';
								echo $c_inst[P_BITS][$w];
								echo '</b> 位]';
							}
							if ((isset($c_inst[P_M_REG][$w]))and($c_inst[P_M_REG][$w])){
								echo ' {<b>';
								foreach ($c_inst[P_M_REG][$w] as $z => $y){
									echo "$z ";
								}
								echo '</b>}';
							}
						}
					}
					if (isset($c_inst[OPERATION][P_TYPE][$w])){
						echo "</font>";
					}
				}	
				echo '</td><td>';
				$c_GPR_effects = OrgansOperator::getGPReffects($c);
				foreach ($c_GPR_effects as $z => $y){
					echo "$z {";
					foreach ($y as $w => $v){
						echo "$w".'位';
						if ($v == 1)
							echo "读; ";
						elseif ($v == 2)
							echo "写; ";
						elseif ($v == 3)
							echo "读写; ";
						else
							echo "<font color=red><b>未知?</b></font>; ";	
					}
					echo "}<br>";
				}
				echo '</td><td>';
				if (isset($flag_register_opt_array[$sec][$c])){
					foreach ($flag_register_opt_array[$sec][$c] as $z => $v){
						echo "$z {";
							if ($v == 1)
								echo "读; ";
							elseif ($v == 2)
								echo "写; ";
							elseif ($v == 3)
								echo "读写; ";
							else
								echo "<font color=red><b>未知?</b></font>; ";	
						
						echo "}<br>";
					}
				}
				echo '</td><td>';
				if (isset($valid_mem_opt_array[$sec][$c])){
					foreach ($valid_mem_opt_array[$sec][$c] as $z => $v){
						echo $v[CODE].' {'.$v[BITS].'位 - ';
						if ($v[OPT] == 1)
							echo "读; ";
						elseif ($v[OPT] == 2)
							echo "写; ";
						elseif ($v[OPT] == 3)
							echo "读写; ";
						else
							echo "<font color=red><b>未知(".$v[OPT].")?</b></font>; ";	

						if (isset($v[REG])){
							echo '(';
							foreach ($v[REG] as $u => $t){
								echo "$t,";    
							}
							echo ')';
						}
						echo "}<br>";
					}
				}
				echo '</td>';
			}
			if (OrgansOperator::isEffectStack($c)){
				echo '<td bgcolor=yellow><b>影响</b>';
			}else{
				echo '<td>不影响';
			}				
			
			echo '</td>';

			
			if (true === $c_ipsp){
				echo '<td bgcolor=red> 保护';
			}else{
				echo '<td bgcolor=green> 不保护';
			}
			echo '</td></tr>';
			//next
			echo '<tr bgcolor='."$color".'><td><b>-></b></td><td><b>Next</b></td>';
			echo '<td><b>usable</b></td><td><b>record</b></td><td></td><td></td><td>';
			if (isset($c_usable[N][NORMAL_WRITE_ABLE])){
				foreach ($c_usable[N][NORMAL_WRITE_ABLE] as $z => $v){
					foreach ($v as $x => $w){
						echo Instruction::getRegByIdxBits($x,$z);
						echo " , ";
					}					
				}
			}
			if (isset($c_forbid[N][NORMAL])){
				echo '<font color = red>';
				foreach ($c_forbid[N][NORMAL] as $z => $v){
					echo '<del>';
					foreach ($v as $x => $w){
						if (!Instruction::getRegByIdxBits($x,$z)){
							echo "$x - $z";
						}else
							echo Instruction::getRegByIdxBits($x,$z);
						echo " , ";
					}					
					echo '</del>';
				}
				echo '</font>';
			}
			echo '</td><td>';
			if (isset($c_usable[N][FLAG_WRITE_ABLE])){
				foreach ($c_usable[N][FLAG_WRITE_ABLE] as $z => $v){
					echo " $z ,";
				}
			}
			if (isset($c_forbid[N][FLAG])){
				echo '<font color = red>';
				foreach ($c_forbid[N][FLAG] as $z => $v){
					echo '<del>';
					echo " $z";
					echo '</del>';
					echo ',';
				}
				echo '</font>';
			}
			echo '</td><td>';
			if (isset($c_usable[N][MEM_OPT_ABLE])){
				foreach ($c_usable[N][MEM_OPT_ABLE] as $z => $v){
					$v = ValidMemAddr::get($v);
					$z = $v[CODE];
					echo $z.' {'.$v[BITS].'位 - ';
					if ($v[OPT] == 1)
						echo "读; ";
					elseif ($v[OPT] == 2)
						echo "写; ";
					elseif ($v[OPT] == 3)
						echo "读写; ";
					else
						echo "<font color=red><b>未知?</b></font>; ";	

					if ((isset($v[REG]))and(is_array($v[REG]))){
						echo '(';
						foreach ($v[REG] as $u => $t){
							echo "$t,";    
						}
						echo ')';
					}
					echo "}<br>";
				}
			}
			echo '</td>';
			if (OrgansOperator::isUsableStack($c,N)){
				echo '<td>可用';
			}else{
				echo '<td bgcolor=red> 禁用';				
			}				
			echo '</td>';
			echo '<td></td>';
			echo '</tr>';			
			
			$c = OrgansOperator::next($c);
		} 
		echo '</table>';
		
		return;
	}
}



?>