<?php


class Instruction{
    //*** 标志寄存器
    private static $_eflags;
    private static $_eflags_reserve;
    //*** 段寄存器
	private static $_segment;
	//*** 通用寄存器
	private static $_register;        //$array[bits]  = array('AL' => 8, ...);
	private static $_register_total;  //$array = $_register[8] + $_register[9] + ...
	private static $_register_index;  //$array['8'] = array ('index' => 'AL', 'index' => 'CL',  'EDX'=> 'DL',  'EBX' => 'BL'); ...
	private static $_register_asort;  //$array = array ('AL' => 'index', ...

	//*** 其它数组
	private static $_mem_effect_len_array;
	private static $_cant_deal_inst;         //目前无法处理指令
	private static $_range_limit_static_jmp; 
	private static $_cc_insts;
	private static $_jcc_without_limit;
	private static $_con_abs_jmp;
	private static $_eip_instruction;
	private static $_mem_opt;
	private static $_alias_inst;
	private static $_bits;

	private static $_pattern_regs; //全 通用寄存器 匹配
   
    //*** 指令集
	private static $_instruction;

    //创建 所有通用寄存器 通配字符串
	private static function genRegPattern(){
		$ret = "";
	    foreach (self::$_register_total as $a => $b){
			$ret .=  '(?<![0-9A-Z])'.'('."$a".')'.'(?![0-9A-Z]+)'.'|';
		}
		$ret = substr ($ret,0,strlen($ret) - 1);
		return $ret;
	}

	public static function show(){		
		echo '<br>self::$_register';
	    var_dump (self::$_register);	
		echo '<br>self::$_register_total';
		var_dump (self::$_register_total);
		echo '<br>self::$_register_index';
		var_dump (self::$_register_index);
		echo '<br>self::$_register_asort';
		var_dump (self::$_register_asort);
	}
	
	public static function init(){
		//***
		require dirname(__FILE__)."/../instructions/register.intl.inc.php";
	    self::$_eflags         = $eflags;
		self::$_eflags_reserve = array_keys(self::$_eflags);
		self::$_segment        = $segment;
		//init general-registers Array
		foreach ($general_register as $v){
			$index = $v[32];
		    foreach ($v as $bits => $reg_name){
				self::$_register[$bits][$reg_name] = $bits;
				self::$_register_total[$reg_name]  = $bits;				
				self::$_register_asort[$reg_name] = $index;
				if ("EIP" === $index){
				    continue;
				}
				self::$_register_index[$bits][$index] = $reg_name;
			}
		}
		//***
		require dirname(__FILE__)."/../instructions/array.intl.inc.php";
		self::$_mem_effect_len_array   = $mem_effect_len_array;
		self::$_cant_deal_inst         = $can_not_deal_operation;
		self::$_range_limit_static_jmp = $range_limit_static_jmp;
		self::$_cc_insts               = $my_cc;
		self::$_jcc_without_limit      = $Jcc_without_limit;
		self::$_con_abs_jmp            = $con_abs_jmp;
		self::$_eip_instruction        = $eip_instruction;
		self::$_mem_opt                = $mem_opt;
		self::$_alias_inst             = $inst_alias;
		self::$_bits                   = $bits_array;

		//***
		require dirname(__FILE__)."/../instructions/intl.inc.php";
		self::$_instruction = $Intel_instruction;

		//***
		self::$_pattern_regs = self::genRegPattern();
	}

	//位数统计
	// $dir: 1 : 大于等于 $bits 的所有位
	public static function getBitsArray($bits=0,$dir=1){
		if (9 >= $bits){ // 8,9 是 16位 的 高低表示
			$bits = 8;
		}
		$ret = array();
		foreach (self::$_bits as $a){
			if ($a >= $bits){
				$ret[] = $a;
			}
		}
		return $ret;
	}

    //是否是prefix
	public static function isPrefixInst($inst){
	    return (isset(self::$_instruction[$inst]['isPrefix']));
	}
    //是否是数据定义指令
	public static function isDataInst($inst){
	    return (isset(self::$_instruction[$inst]['data']));
	}
	//获取指令 数组 / $par 忽略参数个数 (肯定此inst无多参，如prefix;数据定义 或 仅需判断inst是否有效)
	public static function getInstructionOpt($inst,$par=false){
		$ret = false;
	    if (isset(self::$_instruction[$inst])){
		    if (isset(self::$_instruction[$inst]['multi_op'])){
				if (false === $par){
				    $ret = true;
				}else if (isset(self::$_instruction[$inst][$par])){
					$ret = self::$_instruction[$inst][$par];
				}			
			}else{
				$ret = self::$_instruction[$inst];
			}
		}
		return $ret;
	}
    
	//返回 opt 涉及写操作 的 通用(标志)寄存器 数组 $ret[FLAG]   = array('','')
	//                                             $ret[NORMAL] = array('','') #不区分bits
	//                                             $ret[STACK]  = 
	public static function getRegWriteArrayByOpt($inst,$param_num){
		$ret = false;
		if (isset(self::$_instruction[$inst])){
			$r = array();
		    $c = self::$_instruction[$inst];
		    if (isset(self::$_instruction[$inst]['multi_op'])){
				if (isset($c[$param_num])){
					$r[] = $c[$param_num];
				}else{
					unset($c['multi_op']);
					$r = $c;
				}				
			}else{
			    $r[0] = $c;
			}
			foreach ($r as $a){
				foreach ($a as $b => $z){
					if (STACK === $b){
						$ret[STACK] = $z;
					}else{
						if ($z > 1) { //write opt
							if (self::isEflag($b)){
								$ret[FLAG_WRITE_ABLE][$b] = $z;
							}elseif (false !== ($j = self::getGeneralRegIndex($b))){
								$ret[NORMAL_WRITE_ABLE][$j] = $z;
							}//else{
							//	echo "<br>drop...$b<br>";
							//}					
						}
					}
				}
			}
		}
		return $ret;		
	}

	//获取内存地址中的通用寄存器
	public static function getRegInMem($mem){
		$ret = array();
		if (preg_match_all('/'.self::$_pattern_regs.'/',$mem,$tmp)){
			$ret = $tmp[0];
		}
		return $ret;	    
	}


	//获取指令别名(如果有的话)
	public static function getInstAlias($inst){
	    return (isset(self::$_alias_inst[$inst]))?self::$_alias_inst[$inst]:$inst;
	}
    //内存操作 指令
	public static function getMemOpt($inst){
		return (isset(self::$_mem_opt[$inst]))?(self::$_mem_opt[$inst]):false;
	}
	
	public static function isEipInst($inst){
		return isset(self::$_eip_instruction[$inst]);
	}

    public static function isJmp($inst,$type=false){
		if (isset(self::$_con_abs_jmp[$inst])){
			$a = self::$_con_abs_jmp[$inst];
			if (false === $type){
				return $a;
			}else if ($type === $a){
				return true;
			}
		}
		return false;
	}

	public static function randUnLmtJcc(){
		return self::$_jcc_without_limit[array_rand(self::$_jcc_without_limit)];
	}

    public static function isMatchCC($cc,$inst){
		return ((isset (self::$_cc_insts[$inst])) and (self::$_cc_insts[$inst] === $cc));
	}

	public static function getMatchCC($inst){
	    return (isset(self::$_cc_insts[$inst]))?(self::$_cc_insts[$inst]):false;
	}

    public static function isJmpStatic($inst){
	    return (isset(self::$_range_limit_static_jmp[$inst]));
	}
    public static function getJmpRangeLmt($inst){
	    return self::$_range_limit_static_jmp[$inst];
	}

	public static function isCantDealInst($inst){
		return (isset(self::$_cant_deal_inst[$inst]));
	}
    
	public static function getMemEffectLen($first,$second,$third){
		if (isset(self::$_mem_effect_len_array[$first][$second][$third])){
			return self::$_mem_effect_len_array[$first][$second][$third];
		}else{
		    return self::$_mem_effect_len_array['max'];
		}
	}

	public static function isEflag($var){
	    return (isset(self::$_eflags[$var]));
	}
    
	//type 1: status flag  2:control flag  3:system flag(不全)
	public static function getEflags($type = false){
		if (!$type){
		    return self::$_eflags_reserve;
		}elseif (-1 === $type){
			return self::$_eflags;
		}else{
			$ret = false;
			foreach (self::$_eflags as $n => $t){
				if ($t == $type){
					$ret[] = $n;
				}
			}
			return $ret;
		}
	}
	// 获取随机寄存器,可指定不含部分
	public static function getRandomReg($bits,$filter=array()){
		$r = self::$_register_index[$bits];
		if (!empty($filter)){
			foreach ($filter as $a){
				unset($r[$a]);
			}
		}
		if (!empty($r)){
			return array_rand($r);
		}else{
			return false;
		}
	}  
    // 指定 寄存器 获取 对应的 通用寄存器 索引名
	public static function getGeneralRegIndex($reg){
		return (isset(self::$_register_asort[$reg]))?(self::$_register_asort[$reg]):false;
	}

    // 指定 寄存器 获取 对应的 位数
	public static function getGeneralRegBits($reg){
		return (isset(self::$_register_total[$reg]))?(self::$_register_total[$reg]):false;
	}

	// 指定 寄存器索引 + 位数  获取 对应的 寄存器
	public static function getRegByIdxBits($bits,$reg){
	    return (isset(self::$_register_index[$bits][$reg]))?(self::$_register_index[$bits][$reg]):false;
	}

	// 指定 寄存器索引   获取 对应 的 寄存器 数组
	public static function getRegsByIdx($idx){
		$ret = false;
	    foreach (self::$_register_index as $a){
			if (isset($a[$idx])){
				$ret[$a[$idx]] = $a[$idx];
			}
		}
		return $ret;
	}
	
	//指定 寄存器位数  获取 对应 的 寄存器 数组
	public static function getRegsByBits($bits){
		return (isset(self::$_register_index[$bits]))?(self::$_register_index[$bits]):false;
	}


}

?>