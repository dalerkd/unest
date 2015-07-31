<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

class DebugFunc{

	//////////////////////////////////////////////////////
	//
	// 生成 血肉
	// $usable : 可用范围
	// $prev   : 上一个链表 index，无则为false;
	// $type   : 调试用 ... 特殊标记 产生特殊血肉
	private static function gen_code_4_debug_usable_array($usable,$prev,$type = '0x0cccccccc'){
		
		$result = false;
		$i = 0;    


		if (!empty($usable[NORMAL_WRITE_ABLE])){
			foreach ($usable[NORMAL_WRITE_ABLE] as $a => $b){
				if (isset($b[32])){ //有32位的只处理32位即可
					$result[CODE][$i][OPERATION] = 'MOV';
					$result[CODE][$i][PARAMS][0] = Instruction::getRegByIdxBits(32,$a);
					$result[CODE][$i][PARAMS][1] = $type;    				
					$result[CODE][$i][P_TYPE][0] = 'r';
					$result[CODE][$i][P_TYPE][1] = 'i';
					$result[CODE][$i][P_BITS][0] = 32;
					$result[CODE][$i][P_BITS][1] = 32;
					$i ++;			
				}else{
					foreach ($b as $c => $d){				
						$result[CODE][$i][OPERATION] = 'MOV';
						$result[CODE][$i][PARAMS][0] = Instruction::getRegByIdxBits($c,$a);
						$result[CODE][$i][PARAMS][1] = $type;    
						$result[CODE][$i][P_TYPE][0] = 'r';
						$result[CODE][$i][P_TYPE][1] = 'i';
						$result[CODE][$i][P_BITS][0] = $c;
						$result[CODE][$i][P_BITS][1] = $c;				
						$i ++;				
					}
				}
			}
		}
		//elseif (false !== $usable[STACK]){
		//	$result[CODE][$i][OPERATION] = 'push';
		//	$result[CODE][$i][PARAMS][0] = 'eax';
		//	$i ++;
		//	$result[CODE][$i][OPERATION] = 'mov';
		//	$result[CODE][$i][PARAMS][0] = 'eax';
		//	$result[CODE][$i][PARAMS][1] = $type;    				
		//	$i ++;
		//	$result[CODE][$i][OPERATION] = 'pop';
		//	$result[CODE][$i][PARAMS][0] = 'eax';
		//	$i ++;
		//}

		
		if (isset($usable[MEM_OPT_ABLE])){
			foreach ($usable[MEM_OPT_ABLE] as $a => $b){
				$v = ValidMemAddr::get($b);
				$z = $v[CODE];
				if ($v[OPT] >= 2){
					if ($v[BITS] == 32){
						if (false === strpos($z,'_RELINFO_')){ //含重定位的内存地址，暂不处理
							$result[CODE][$i][OPERATION] = 'MOV';
							$result[CODE][$i][PARAMS][0] = $z;
							$result[CODE][$i][PARAMS][1] = $type;       
							$result[CODE][$i][P_TYPE][0] = 'm';
							$result[CODE][$i][P_TYPE][1] = 'i';
							$result[CODE][$i][P_BITS][0] = 32;
							$result[CODE][$i][P_BITS][1] = 32;	 				
							$i ++; 
						}else{
							
						}
					}
				}
			}
		}

		if (false !== $result){			
			foreach ($result[CODE] as $b){
				$c_id = OrgansOperator::newUnitNaked($prev,$b);
				OrgansOperator::appendComment($c_id,'gen4debug01');
			} 			
		}
		return;
	}

	public static function debug_usable_array(){
		$p_lp = false;
		$c_lp = OrgansOperator::getBeginUnit();

		while ($c_lp){

			$c_usable = OrgansOperator::getUsable($c_lp);

			if ($c_usable){
				if (isset($c_usable[P])){
					self::gen_code_4_debug_usable_array($c_usable[P],$p_lp,'0xaaaaaaaa');
				}
				if (isset($c_usable[N])){
					self::gen_code_4_debug_usable_array($c_usable[N],$c_lp,'0xbbbbbbbb');
				}
			}
			$p_lp = $c_lp;
			$c_lp = OrgansOperator::next($c_lp);
		}
	}
}

?>