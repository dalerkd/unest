<?php


// eflag  1: status flag  2:control flag  3:system flag(不全)
$eflags = array('OF' => 1,'SF' => 1,'ZF' => 1,'AF' => 1,'CF' => 1,'PF' => 1,'DF' => 2,'TF' => 3,'IF' => 3,'NT' => 3,'RF' => 3);  

// sreg
$segment = array('CS' => 'CS', 'DS' => 'DS', 'SS' => 'SS', 'ES' => 'ES', 'FS' => 'FS', 'GS' => 'GS');

// creg
$creg = array('CR0','CR1','CR2','CR3','CR4','CR5','CR6','CR7','CR8','CR9','CR10','CR11','CR12','CR13','CR14','CR15',);

// treg
$treg = array('TR0','TR1','TR2','TR3','TR4','TR5','TR6','TR7',);

// dreg
$dreg = array('DR0','DR1','DR2','DR3','DR4','DR5','DR6','DR7','DR8','DR9','DR10','DR11','DR12','DR13','DR14','DR15',);

// gpr
$general_register[] = array( 8 => 'AL' , 9 => 'AH' , 16 => 'AX' , 32 => 'EAX' ,);
$general_register[] = array( 8 => 'BL' , 9 => 'BH' , 16 => 'BX' , 32 => 'EBX' ,);
$general_register[] = array( 8 => 'CL' , 9 => 'CH' , 16 => 'CX' , 32 => 'ECX' ,);
$general_register[] = array( 8 => 'DL' , 9 => 'DH' , 16 => 'DX' , 32 => 'EDX' ,);
$general_register[] = array(                         16 => 'BP' , 32 => 'EBP' ,);
$general_register[] = array(                         16 => 'SI' , 32 => 'ESI' ,);
$general_register[] = array(                         16 => 'DI' , 32 => 'EDI' ,);
$general_register[] = array(                         16 => 'SP' , 32 => 'ESP' ,);
$general_register[] = array(                         16 => 'IP' , 32 => 'EIP' ,);

// gpr relationship
$gpr_relation[32] = array(8,9,16);
$gpr_relation[16] = array(8,9);


?>