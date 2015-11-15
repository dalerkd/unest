<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

class DebugFunc{	

	private static function gen_code_4_debug_usable_array($usable,$next,$usable_id){
		
		OrganOptWrapper::job_begin();

		$i = 0;
		$pos = $next;
		$gpr4memread = array();

		// pay attention to the sort of do
		if (!empty($usable[GPR_WRITE_ABLE])){
			foreach ($usable[GPR_WRITE_ABLE] as $regIdx => $b){
				if ('EIP' === $regIdx){continue;}
				foreach ($b as $regBits => $true){					
					$c_reg = Instruction::getRegByIdxBits($regBits,$regIdx);
					$gpr4memread[$regBits] = $c_reg;					
				}
			}
		}
		if (!empty($usable[MEM_WRITE_ABLE])){
			foreach ($usable[MEM_WRITE_ABLE] as $memIdx){
				$organ = array();
				$organ[INST] = 'MOV';
				$organ[OPERAND][1] = $memIdx;
				$organ[OPERAND][2] = '0xcccccccc';
				$organ[P_TYPE][1] = T_MEM;
				$organ[P_TYPE][2] = T_IMM;
				$pos = OrganOptWrapper::organ_insert($organ,$pos,$i,array('dbg',$usable_id));
				$i ++;
			}
		}
		if (!empty($usable[MEM_READ_ABLE])){
			foreach ($usable[MEM_READ_ABLE] as $memIdx){
				$t = OrgansOperator::getMemOperandArr($memIdx);
				if (isset($gpr4memread[$t[OP_BITS]])){
					$organ = array();
					$organ[INST] = 'MOV';
					$organ[OPERAND][1] = $gpr4memread[$t[OP_BITS]];
					$organ[OPERAND][2] = $memIdx;
					$organ[P_TYPE][1] = T_GPR;
					$organ[P_TYPE][2] = T_MEM;
					$pos = OrganOptWrapper::organ_insert($organ,$pos,$i,array('dbg',$usable_id));
					$i ++;
				}				
			}
		}
		if (!empty($usable[GPR_WRITE_ABLE])){
			foreach ($usable[GPR_WRITE_ABLE] as $regIdx => $b){
				if ('EIP' === $regIdx){continue;}
				foreach ($b as $regBits => $true){					
					$c_reg = Instruction::getRegByIdxBits($regBits,$regIdx);
					$organ = array();
					$organ[INST] = 'MOV';
					$organ[OPERAND][1] = $c_reg;
					$organ[OPERAND][2] = '0xcccccccc';    				
					$organ[P_TYPE][1] = T_GPR;
					$organ[P_TYPE][2] = T_IMM;
					$pos = OrganOptWrapper::organ_insert($organ,$pos,$i,array('dbg',$usable_id));
					$i ++;
				}
			}
		}

		if (false === OrganOptWrapper::job_commit()){ // fail 
			GeneralFunc::LogInsert('a job_commit() returns fail',WARNING);
		}
		return;
	}

	public static function debug_usable_array(){
		$objs = array();
		$pos  = false;
		$c_lp = OrgansOperator::getBeginUnit();
		while ($c_lp){
			$c_usable = OrgansOperator::Get_Unit_Usable($c_lp,P);
			var_dump($c_usable);
			$objs[] = array($c_usable,$pos,$c_lp);
			$pos = $c_lp;
			$c_lp = OrgansOperator::next($c_lp);
		}

		foreach ($objs as $c){
			self::gen_code_4_debug_usable_array($c[0],$c[1],$c[2]);
		}

		echo '<br><br><br><br><br><br><br><br><br>';
		OrganOptWrapper::show();
	}
}

?>