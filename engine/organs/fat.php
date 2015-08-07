<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}


class OrganFat{


	//输入 最大容许的 脂肪 字节数
	public static function start($unit,$direct,&$reserveSize){		

		$fat_size_array = array(  //脂肪字节长度 可选范围
			0,1,1,1,2,2,2,3,3,3,4,4,4,5,5,5,6,7,8,9,10,
		);

		$ret = '';
		
		$fat_size = GeneralFunc::my_array_rand($fat_size_array);

		$fat_size = $fat_size_array[$fat_size];

		if ($fat_size > 0){
			if ($reserveSize > $fat_size){
				$reserveSize -= $fat_size;
				if (OrgansOperator::setPseudoAlloc($unit,$direct,$fat_size)){
					$ret .= 'db ';
					for ($i = 0;$i < $fat_size;$i++){
						$ret .= mt_rand (0,255);
						$ret .= ',';
					}
				}else{
					// echo '<br>Pseudo fail';
				}
				// $ret .= ' ; Fat.'.$direct;
			}
		}
		return $ret;
	}


}



?>