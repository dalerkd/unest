<?php


//###########################################################################################################################
//
// 可用 血肉指令 (手工输入部分)
//
// 为支持模糊定位，同指令按可能性大小排序 32 > 16 > 8
//
// 例：
//$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('r','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));
//                  指令             出现率        指令                类型            'i'       位数                    固定设定值                  
//                                                                                     'm' 
//
//
//###########################################################################################################################
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['MOV'][] = array('rate' => 0,'operation' => 'MOV','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','i'),'p_bits' => array(32,32));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','m'),'p_bits' => array(16,16));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','i'),'p_bits' => array(8,8));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['CMP'][] = array('rate' => 0,'operation' => 'CMP','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['LEA'][] = array('rate' => 0,'operation' => 'LEA','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['LEA'][] = array('rate' => 0,'operation' => 'LEA','p_type' => array('r','m'),'p_bits' => array(16,16));
//###########################################################################################################################
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['XOR'][] = array('rate' => 0,'operation' => 'XOR','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['TEST'][] = array('rate' => 0,'operation' => 'TEST','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['SUB'][] = array('rate' => 0,'operation' => 'SUB','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['INC'][] = array('rate' => 0,'operation' => 'INC','p_type' => array('r'),'p_bits' => array(32));		    
$meat_inst['INC'][] = array('rate' => 0,'operation' => 'INC','p_type' => array('m'),'p_bits' => array(32));
$meat_inst['INC'][] = array('rate' => 0,'operation' => 'INC','p_type' => array('r'),'p_bits' => array(16));	    
$meat_inst['INC'][] = array('rate' => 0,'operation' => 'INC','p_type' => array('m'),'p_bits' => array(16));
$meat_inst['INC'][] = array('rate' => 0,'operation' => 'INC','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['INC'][] = array('rate' => 0,'operation' => 'INC','p_type' => array('m'),'p_bits' => array(8));	
//###########################################################################################################################	
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['ADD'][] = array('rate' => 0,'operation' => 'ADD','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['DEC'][] = array('rate' => 0,'operation' => 'DEC','p_type' => array('r'),'p_bits' => array(32));		    
$meat_inst['DEC'][] = array('rate' => 0,'operation' => 'DEC','p_type' => array('m'),'p_bits' => array(32));
$meat_inst['DEC'][] = array('rate' => 0,'operation' => 'DEC','p_type' => array('r'),'p_bits' => array(16));	    
$meat_inst['DEC'][] = array('rate' => 0,'operation' => 'DEC','p_type' => array('m'),'p_bits' => array(16));
$meat_inst['DEC'][] = array('rate' => 0,'operation' => 'DEC','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['DEC'][] = array('rate' => 0,'operation' => 'DEC','p_type' => array('m'),'p_bits' => array(8));	
//###########################################################################################################################
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['AND'][] = array('rate' => 0,'operation' => 'AND','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['MOVZX'][] = array('rate' => 0,'operation' => 'MOVZX','p_type'=> array('r','r'),'p_bits' => array(32,16));
$meat_inst['MOVZX'][] = array('rate' => 0,'operation' => 'MOVZX','p_type'=> array('r','m'),'p_bits' => array(32,16));
$meat_inst['MOVZX'][] = array('rate' => 0,'operation' => 'MOVZX','p_type'=> array('r','r'),'p_bits' => array(32,8));
$meat_inst['MOVZX'][] = array('rate' => 0,'operation' => 'MOVZX','p_type'=> array('r','m'),'p_bits' => array(32,8));
$meat_inst['MOVZX'][] = array('rate' => 0,'operation' => 'MOVZX','p_type'=> array('r','r'),'p_bits' => array(16,8));
$meat_inst['MOVZX'][] = array('rate' => 0,'operation' => 'MOVZX','p_type'=> array('r','m'),'p_bits' => array(16,8));
//###########################################################################################################################
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['OR'][] = array('rate' => 0,'operation' => 'OR','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['NEG'][] = array('rate' => 0,'operation' => 'NEG','p_type' => array('r'),'p_bits' => array(32));		    
$meat_inst['NEG'][] = array('rate' => 0,'operation' => 'NEG','p_type' => array('m'),'p_bits' => array(32));
$meat_inst['NEG'][] = array('rate' => 0,'operation' => 'NEG','p_type' => array('r'),'p_bits' => array(16));	    
$meat_inst['NEG'][] = array('rate' => 0,'operation' => 'NEG','p_type' => array('m'),'p_bits' => array(16));
$meat_inst['NEG'][] = array('rate' => 0,'operation' => 'NEG','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['NEG'][] = array('rate' => 0,'operation' => 'NEG','p_type' => array('m'),'p_bits' => array(8));	
//###########################################################################################################################
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['SBB'][] = array('rate' => 0,'operation' => 'SBB','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('r','i'),'p_bits' => array(32,8));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('m','i'),'p_bits' => array(32,8));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('r','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('m','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));

$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('r','i'),'p_bits' => array(16,8));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('m','i'),'p_bits' => array(16,8));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('r','r'),'p_bits' => array(16,8),'static' => array('1'=>'CL'));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('m','r'),'p_bits' => array(16,8),'static' => array('1'=>'CL'));

$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('r','i'),'p_bits' => array(8,8));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('m','i'),'p_bits' => array(8,8));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('r','r'),'p_bits' => array(8,8),'static' => array('1'=>'CL'));
$meat_inst['SHR'][] = array('rate' => 0,'operation' => 'SHR','p_type' => array('m','r'),'p_bits' => array(8,8),'static' => array('1'=>'CL'));
//###########################################################################################################################
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r'),'p_bits' => array(32));
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r'),'p_bits' => array(16));
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r'),'p_bits' => array(8));

$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('m'),'p_bits' => array(32));
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('m'),'p_bits' => array(16));
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('m'),'p_bits' => array(8));

$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r','r'),'p_bits' => array(16,16));

$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r','m'),'p_bits' => array(16,16));

$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r','r','i'),'p_bits' => array(32,32,32));
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r','r','i'),'p_bits' => array(16,16,16));

$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r','m','i'),'p_bits' => array(32,32,32));
$meat_inst['IMUL'][] = array('rate' => 0,'operation' => 'IMUL','p_type' => array('r','m','i'),'p_bits' => array(16,16,16));
//###########################################################################################################################
$meat_inst['SETZ'][] = array('rate' => 0,'operation' => 'SETZ','p_type' => array('r'),'p_bits' => array(8));
$meat_inst['SETZ'][] = array('rate' => 0,'operation' => 'SETZ','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['IDIV'][] = array('rate' => 0,'operation' => 'IDIV','p_type' => array('r'),'p_bits' => array(32));		    
$meat_inst['IDIV'][] = array('rate' => 0,'operation' => 'IDIV','p_type' => array('m'),'p_bits' => array(32));
$meat_inst['IDIV'][] = array('rate' => 0,'operation' => 'IDIV','p_type' => array('r'),'p_bits' => array(16));	    
$meat_inst['IDIV'][] = array('rate' => 0,'operation' => 'IDIV','p_type' => array('m'),'p_bits' => array(16));
$meat_inst['IDIV'][] = array('rate' => 0,'operation' => 'IDIV','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['IDIV'][] = array('rate' => 0,'operation' => 'IDIV','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('r','i'),'p_bits' => array(32,8));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('m','i'),'p_bits' => array(32,8));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('r','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('m','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));

$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('r','i'),'p_bits' => array(16,8));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('m','i'),'p_bits' => array(16,8));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('r','r'),'p_bits' => array(16,8),'static' => array('1'=>'CL'));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('m','r'),'p_bits' => array(16,8),'static' => array('1'=>'CL'));

$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('r','i'),'p_bits' => array(8,8));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('m','i'),'p_bits' => array(8,8));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('r','r'),'p_bits' => array(8,8),'static' => array('1'=>'CL'));
$meat_inst['SHL'][] = array('rate' => 0,'operation' => 'SHL','p_type' => array('m','r'),'p_bits' => array(8,8),'static' => array('1'=>'CL'));
//###########################################################################################################################
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('r','i'),'p_bits' => array(32,8));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('m','i'),'p_bits' => array(32,8));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('r','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('m','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));

$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('r','i'),'p_bits' => array(16,8));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('m','i'),'p_bits' => array(16,8));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('r','r'),'p_bits' => array(16,8),'static' => array('1'=>'CL'));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('m','r'),'p_bits' => array(16,8),'static' => array('1'=>'CL'));

$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('r','i'),'p_bits' => array(8,8));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('m','i'),'p_bits' => array(8,8));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('r','r'),'p_bits' => array(8,8),'static' => array('1'=>'CL'));
$meat_inst['SAR'][] = array('rate' => 0,'operation' => 'SAR','p_type' => array('m','r'),'p_bits' => array(8,8),'static' => array('1'=>'CL'));
//###########################################################################################################################
$meat_inst['SETNZ'][] = array('rate' => 0,'operation' => 'SETNZ','p_type' => array('r'),'p_bits' => array(8));
$meat_inst['SETNZ'][] = array('rate' => 0,'operation' => 'SETNZ','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['MOVSX'][] = array('rate' => 0,'operation' => 'MOVSX','p_type' => array('r','r'),'p_bits' => array(32,16));
$meat_inst['MOVSX'][] = array('rate' => 0,'operation' => 'MOVSX','p_type' => array('r','r'),'p_bits' => array(32,8));

$meat_inst['MOVSX'][] = array('rate' => 0,'operation' => 'MOVSX','p_type' => array('r','m'),'p_bits' => array(32,16));
$meat_inst['MOVSX'][] = array('rate' => 0,'operation' => 'MOVSX','p_type' => array('r','m'),'p_bits' => array(32,8));

$meat_inst['MOVSX'][] = array('rate' => 0,'operation' => 'MOVSX','p_type' => array('r','r'),'p_bits' => array(16,8));
$meat_inst['MOVSX'][] = array('rate' => 0,'operation' => 'MOVSX','p_type' => array('r','m'),'p_bits' => array(16,8));
//###########################################################################################################################
$meat_inst['NOT'][] = array('rate' => 0,'operation' => 'NOT','p_type' => array('r'),'p_bits' => array(32));		    
$meat_inst['NOT'][] = array('rate' => 0,'operation' => 'NOT','p_type' => array('m'),'p_bits' => array(32));
$meat_inst['NOT'][] = array('rate' => 0,'operation' => 'NOT','p_type' => array('r'),'p_bits' => array(16));	    
$meat_inst['NOT'][] = array('rate' => 0,'operation' => 'NOT','p_type' => array('m'),'p_bits' => array(16));
$meat_inst['NOT'][] = array('rate' => 0,'operation' => 'NOT','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['NOT'][] = array('rate' => 0,'operation' => 'NOT','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['CDQ'][] = array('rate' => 0,'operation' => 'CDQ');
//###########################################################################################################################
$meat_inst['CWD'][] = array('rate' => 0,'operation' => 'CWD');
//###########################################################################################################################
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','i'),'p_bits' => array(32,32));	    
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('m','i'),'p_bits' => array(32,32));

$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','i'),'p_bits' => array(16,16));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('m','i'),'p_bits' => array(16,16));

$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','r'),'p_bits' => array(8,8));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','i'),'p_bits' => array(8,8));	    
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('m','r'),'p_bits' => array(8,8));
$meat_inst['ADC'][] = array('rate' => 0,'operation' => 'ADC','p_type' => array('m','i'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('r','r'),'p_bits' => array(32,32));    
$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('r','m'),'p_bits' => array(32,32));
$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('m','r'),'p_bits' => array(32,32));

$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('r','m'),'p_bits' => array(16,16));    
$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('m','r'),'p_bits' => array(16,16));

$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('r','r'),'p_bits' => array(8,8));    
$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('r','m'),'p_bits' => array(8,8));
$meat_inst['XCHG'][] = array('rate' => 0,'operation' => 'XCHG','p_type' => array('m','r'),'p_bits' => array(8,8));
//###########################################################################################################################
$meat_inst['SAHF'][] = array('rate' => 0,'operation' => 'SAHF');
//###########################################################################################################################
$meat_inst['DIV'][] = array('rate' => 0,'operation' => 'DIV','p_type' => array('r'),'p_bits' => array(32));		    
$meat_inst['DIV'][] = array('rate' => 0,'operation' => 'DIV','p_type' => array('m'),'p_bits' => array(32));
$meat_inst['DIV'][] = array('rate' => 0,'operation' => 'DIV','p_type' => array('r'),'p_bits' => array(16));	    
$meat_inst['DIV'][] = array('rate' => 0,'operation' => 'DIV','p_type' => array('m'),'p_bits' => array(16));
$meat_inst['DIV'][] = array('rate' => 0,'operation' => 'DIV','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['DIV'][] = array('rate' => 0,'operation' => 'DIV','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['BT'][] = array('rate' => 0,'operation' => 'BT','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['BT'][] = array('rate' => 0,'operation' => 'BT','p_type' => array('r','i'),'p_bits' => array(32,8));
//$meat_inst['BT'][] = array('rate' => 0,'operation' => 'BT','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['BT'][] = array('rate' => 0,'operation' => 'BT','p_type' => array('m','i'),'p_bits' => array(32,8));

$meat_inst['BT'][] = array('rate' => 0,'operation' => 'BT','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['BT'][] = array('rate' => 0,'operation' => 'BT','p_type' => array('r','i'),'p_bits' => array(16,8));
//$meat_inst['BT'][] = array('rate' => 0,'operation' => 'BT','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['BT'][] = array('rate' => 0,'operation' => 'BT','p_type' => array('m','i'),'p_bits' => array(16,8));
//###########################################################################################################################
$meat_inst['SETNLE'][] = array('rate' => 0,'operation' => 'SETNLE','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['SETNLE'][] = array('rate' => 0,'operation' => 'SETNLE','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['CLD'][] = array('rate' => 0,'operation' => 'CLD');
//###########################################################################################################################
$meat_inst['CLC'][] = array('rate' => 0,'operation' => 'CLC');
//###########################################################################################################################
$meat_inst['STC'][] = array('rate' => 0,'operation' => 'STC');
//###########################################################################################################################
$meat_inst['BTS'][] = array('rate' => 0,'operation' => 'BTS','p_type' => array('r','r'),'p_bits' => array(32,32));
$meat_inst['BTS'][] = array('rate' => 0,'operation' => 'BTS','p_type' => array('r','i'),'p_bits' => array(32,8));
//$meat_inst['BTS'][] = array('rate' => 0,'operation' => 'BTS','p_type' => array('m','r'),'p_bits' => array(32,32));
$meat_inst['BTS'][] = array('rate' => 0,'operation' => 'BTS','p_type' => array('m','i'),'p_bits' => array(32,8));

$meat_inst['BTS'][] = array('rate' => 0,'operation' => 'BTS','p_type' => array('r','r'),'p_bits' => array(16,16));
$meat_inst['BTS'][] = array('rate' => 0,'operation' => 'BTS','p_type' => array('r','i'),'p_bits' => array(16,8));
//$meat_inst['BTS'][] = array('rate' => 0,'operation' => 'BTS','p_type' => array('m','r'),'p_bits' => array(16,16));
$meat_inst['BTS'][] = array('rate' => 0,'operation' => 'BTS','p_type' => array('m','i'),'p_bits' => array(16,8));
//###########################################################################################################################
$meat_inst['SETNBE'][] = array('rate' => 0,'operation' => 'SETNBE','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['SETNBE'][] = array('rate' => 0,'operation' => 'SETNBE','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('r','i'),'p_bits' => array(32,8));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('m','i'),'p_bits' => array(32,8));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('r','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('m','r'),'p_bits' => array(32,8),'static' => array('1'=>'CL'));

$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('r','i'),'p_bits' => array(16,8));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('m','i'),'p_bits' => array(16,8));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('r','r'),'p_bits' => array(16,8),'static' => array('1'=>'CL'));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('m','r'),'p_bits' => array(16,8),'static' => array('1'=>'CL'));

$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('r','i'),'p_bits' => array(8,8));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('m','i'),'p_bits' => array(8,8));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('r','r'),'p_bits' => array(8,8),'static' => array('1'=>'CL'));
$meat_inst['RCL'][] = array('rate' => 0,'operation' => 'RCL','p_type' => array('m','r'),'p_bits' => array(8,8),'static' => array('1'=>'CL'));
//###########################################################################################################################
$meat_inst['SETB'][] = array('rate' => 0,'operation' => 'SETB','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['SETB'][] = array('rate' => 0,'operation' => 'SETB','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['SETL'][] = array('rate' => 0,'operation' => 'SETL','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['SETL'][] = array('rate' => 0,'operation' => 'SETL','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['SETLE'][] = array('rate' => 0,'operation' => 'SETLE','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['SETLE'][] = array('rate' => 0,'operation' => 'SETLE','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['SETNL'][] = array('rate' => 0,'operation' => 'SETNL','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['SETNL'][] = array('rate' => 0,'operation' => 'SETNL','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['MUL'][] = array('rate' => 0,'operation' => 'MUL','p_type' => array('r'),'p_bits' => array(32));		    
$meat_inst['MUL'][] = array('rate' => 0,'operation' => 'MUL','p_type' => array('m'),'p_bits' => array(32));
$meat_inst['MUL'][] = array('rate' => 0,'operation' => 'MUL','p_type' => array('r'),'p_bits' => array(16));	    
$meat_inst['MUL'][] = array('rate' => 0,'operation' => 'MUL','p_type' => array('m'),'p_bits' => array(16));
$meat_inst['MUL'][] = array('rate' => 0,'operation' => 'MUL','p_type' => array('r'),'p_bits' => array(8));			    
$meat_inst['MUL'][] = array('rate' => 0,'operation' => 'MUL','p_type' => array('m'),'p_bits' => array(8));
//###########################################################################################################################
$meat_inst['AAM'][] = array('rate' => 0,'operation' => 'AAM');
//###########################################################################################################################
$meat_inst['CMC'][] = array('rate' => 0,'operation' => 'CMC');
//###########################################################################################################################
$meat_inst['STD'][] = array('rate' => 0,'operation' => 'STD');
//###########################################################################################################################
$meat_inst['NOP'][] = array('rate' => 0,'operation' => 'NOP');
//###########################################################################################################################
$meat_inst['DAA'][] = array('rate' => 0,'operation' => 'DAA');
//###########################################################################################################################
$meat_inst['CPUID'][] = array('rate' => 0,'operation' => 'CPUID');
//###########################################################################################################################
$meat_inst['RDTSC'][] = array('rate' => 0,'operation' => 'RDTSC');
//###########################################################################################################################

?>