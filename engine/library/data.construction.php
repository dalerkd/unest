<?php

if(!defined('UNEST.ORG')) {
        exit('Access Denied');
}

//主 链表 及 相关 结构 操作 类

define ('MIN_REL_JMP_RANGE_RESERVE',5); //rel_jmp range 极限保留字节数 (小于此数,所有单位character.Rate清除)

class ConstructionDlinkedListOpt{
    //链表记录
    private static $_soul_writein_Dlinked_List;        //双向链表 (数组结构)
    private static $_s_w_Dlinked_List_index;           //双向链表插入位置指针
	private static $_soul_writein_Dlinked_List_start;  //双向链表首单位指针
	private static $_c_rel_jmp_range;
	private static $_c_rel_jmp_pointer;
	private static $_c_usable_oplen;

    //回滚记录
	private static $_rollback_soul_writein_Dlinked_List;
    private static $_rollback_s_w_Dlinked_List_index;
	private static $_rollback_soul_writein_Dlinked_List_start;
	private static $_rollback_c_rel_jmp_range;
	private static $_rollback_c_rel_jmp_pointer;
	private static $_rollback_c_usable_oplen;

	//记录rel.jmp.range已到极限或无限 ()
    private static $_forbid_rel_jmp;


    //搜索所有rel_jmp列表，将所有到达range限制的单位character.Rate清除
	public static function forbidMinRanges(){
	    foreach (self::$_c_rel_jmp_range as $i => $v){
			if (isset(self::$_forbid_rel_jmp[$i])){
			    continue;
			}elseif (false === $v['max']){  // no limit range => not need forbid check
			    self::$_forbid_rel_jmp[$i] = true;
			}elseif ($v['max'] <= $v['range'] + MIN_REL_JMP_RANGE_RESERVE){
				//echo "<br>$$$$$$$$$$$$$$$$$$$$$$$$$$$$$";
				//echo $v['max'].' <= '.$v['range']." + ".MIN_REL_JMP_RANGE_RESERVE;
			    self::$_forbid_rel_jmp[$i] = true;
				foreach ($v['unit'] as $u => $vv){
				    Character::removeRate($u);
				}
			}
		}
		//var_dump (self::$_forbid_rel_jmp);
	}

	//初始化 变量s
	public static function init($c_soul_writein_Dlinked_List_Total,$c_rel_jmp_range,$c_rel_jmp_pointer){
	    self::$_soul_writein_Dlinked_List = $c_soul_writein_Dlinked_List_Total['list'];	
		self::$_s_w_Dlinked_List_index    = $c_soul_writein_Dlinked_List_Total['index'];
		self::$_soul_writein_Dlinked_List_start = DEFAULT_DLIST_FIRST_NUM;
		self::$_c_rel_jmp_range = $c_rel_jmp_range;
		self::$_c_rel_jmp_pointer = $c_rel_jmp_pointer;
		self::$_c_usable_oplen = false;              //可用代码长度限制 剩余值 (设置为false 代表不限制代码长度)

		self::$_forbid_rel_jmp = array();
	}

	//准备回滚
	public static function ready(){
	    self::$_rollback_soul_writein_Dlinked_List       = self::$_soul_writein_Dlinked_List;	
		self::$_rollback_s_w_Dlinked_List_index          = self::$_s_w_Dlinked_List_index;
		self::$_rollback_soul_writein_Dlinked_List_start = self::$_soul_writein_Dlinked_List_start;
		self::$_rollback_c_rel_jmp_range                 = self::$_c_rel_jmp_range;
		self::$_rollback_c_rel_jmp_pointer               = self::$_c_rel_jmp_pointer;
		self::$_rollback_c_usable_oplen                  = self::$_c_usable_oplen;
	}
	//开始回滚
	public static function rollback(){
	    self::$_soul_writein_Dlinked_List       = self::$_rollback_soul_writein_Dlinked_List;	
		self::$_s_w_Dlinked_List_index          = self::$_rollback_s_w_Dlinked_List_index;
		self::$_soul_writein_Dlinked_List_start = self::$_rollback_soul_writein_Dlinked_List_start;
		self::$_c_rel_jmp_range                 = self::$_rollback_c_rel_jmp_range;
		self::$_c_rel_jmp_pointer               = self::$_rollback_c_rel_jmp_pointer;
		self::$_c_usable_oplen                  = self::$_rollback_c_usable_oplen;
	}

	/////////////////////////////////////////////////////////////////////////////////////////////
	//ready 阶段，特殊初始化
	public static function ReadyInit(){
		self::$_c_rel_jmp_range   = array();
	    self::$_c_rel_jmp_pointer = array();
	}
    //range => pointer 转换
	public static function RelJmpRange2Pointer($unit){
		foreach (self::$_c_rel_jmp_range[$unit]['unit'] as $a => $b){
		    self::$_c_rel_jmp_pointer[$a][$unit] = $b;
		}	
	}

	////////////////////////////////////////////////////////////////////////////////////////////
	//roll 准备阶段值操作
	
	//读取roll back 准备中的 jmp_range 值
	public static function ReadRollingRelJmpRange(){
	    return self::$_rollback_c_rel_jmp_range;
	}
	//读取roll back 准备中的 list 值
	public static function ReadRollingDlinkedList(){
	    return self::$_rollback_soul_writein_Dlinked_List;
	}
	// 获取2单位间所有单位,按次序排列, 注: 输入位置未按序
	public static function getAmongObjs($a,$b){
		$flag = 0;
		$i = 1;
		$objs = array();
		$c = self::$_soul_writein_Dlinked_List_start;
		do{
			if ($a == $c){
				$flag ++;
			}
			if ($b == $c){
				$flag ++;
			}
			if (0 < $flag){
				$objs[$i] = $c;
				$i ++;
			}
			if ($flag > 1){
				return $objs;
			}
			$c = self::$_soul_writein_Dlinked_List[$c][N];
		}while (false !== $c);
		return false;
	}
	// 获取当前链表中所有单位编号
	public static function getAllUnits(){
		$ret = array();
		$c = self::$_soul_writein_Dlinked_List_start;
		do{
			$ret[] = $c;
			$c = self::$_soul_writein_Dlinked_List[$c][N];
		}while (false !== $c);

		return $ret;
	}
	// 有效单位编号
	public static function isValidID($id){
		if (isset(self::$_soul_writein_Dlinked_List[$id])){
		    if (!isset(self::$_soul_writein_Dlinked_List[$id]['302'])){
			    return true;
			}
		}
	    return false;
	}

    // 读取指定unit 的 $_c_rel_jmp_pointer 值
	public static function ReadRelJmpPointer($unit=false){
		if (false === $unit){
			return self::$_c_rel_jmp_pointer;    
		}
	    return self::$_c_rel_jmp_pointer[$unit];
	}
	//
	public static function SetRelJmpPointer($unit,$key,$value){
	    self::$_c_rel_jmp_pointer[$unit][$key] = $value;
		return;
	}
	public static function UnsetRelJmpPointer($unit,$key=false){
		if (false === $key){
			unset (self::$_c_rel_jmp_pointer[$unit]);
		}else{
			unset (self::$_c_rel_jmp_pointer[$unit][$key]);
		}
		return;
	}
	public static function issetRelJmpPointer($unit){
	    return isset(self::$_c_rel_jmp_pointer[$unit]);
	}
	// 读取指定unit 的 $_c_rel_jmp_range; 值
	public static function readRelJmpRange($unit=false,$key=false){
		if (false === $unit){
			return self::$_c_rel_jmp_range;    
		}
		if (false === $key){
		    return self::$_c_rel_jmp_range[$unit];    
		}
	    return self::$_c_rel_jmp_range[$unit][$key];
	}	
	//
	public static function setRelJmpRange($value,$unit,$key=false,$skey=false){
		if (false !== $skey){
		    self::$_c_rel_jmp_range[$unit][$key][$skey] = $value;
		}elseif (false !== $key){
			self::$_c_rel_jmp_range[$unit][$key]        = $value;
		}else{
			self::$_c_rel_jmp_range[$unit]              = $value;
		}
	}
	public static function unsetRelJmpRange($unit,$key=false,$skey=false){
		if (false === $key){
			unset (self::$_c_rel_jmp_range[$unit]);
		}elseif (false === $skey){
			unset (self::$_c_rel_jmp_range[$unit][$key]);
		}else{
			unset (self::$_c_rel_jmp_range[$unit][$key][$skey]);
		}
		return;
	}	
	public static function increaseRelJmpRange($unit,$inc_value,$df=true){
		if ($df)
			self::$_c_rel_jmp_range[$unit]['range'] += $inc_value;
		else
			self::$_c_rel_jmp_range[$unit]['range'] -= $inc_value;
	}
    public static function issetRelJmpRange($unit,$key = 'range'){
	    return isset(self::$_c_rel_jmp_range[$unit][$key]);
	}
	public static function outRelJmpRange($unit){
        if (false !== self::$_c_rel_jmp_range[$unit]['max']){
			if (self::$_c_rel_jmp_range[$unit]['max'] < self::$_c_rel_jmp_range[$unit]['range']){
				return true;
			}
		}
		return false;
	}


    ////////////////////////////////////////////////////////////////////////////////////////////
    //可用代码长度 初始化 返回：1 不足   / 2 无可用 / 0 正常
	public static function OplenInit($oplen){
		if ($oplen > 0){
		    self::$_c_usable_oplen = $oplen;
		}elseif ($oplen < 0){
		    self::$_c_usable_oplen = 0;
			return 1;
		}else{
		    self::$_c_usable_oplen = 0;		
			return 2;
		}
		return 0;
	}

    //计算可用代码长度 限制 剩余值 // 返回 true: 成功 false:失败，剩余不足
	                               // 传递 $inc_len = 0 ;可用来检测 可用代码长度 是否已不足
								   // $type == false : 数值为增加
	public static function OplenIncrease($inc_len,$type=true){	
		if (false === self::$_c_usable_oplen){
		    return true;
		}
		if ($type){
			self::$_c_usable_oplen -= $inc_len;
		}else{
			self::$_c_usable_oplen += $inc_len;
		}
		if (self::$_c_usable_oplen >= 0){
			return true;
		}		
		return false;		
	}
	//链条表 首单位 读取
	public static function readListFirstUnit(){
	    return self::$_soul_writein_Dlinked_List_start;
	}

	////////////////////////////////////////////////////////////////////////////////////////////
	//双向链表 $soul_writein_Dlinked_List 及 链表指针 $s_w_Dlinked_List_index 操作函数s
   
	// 强制重置链表链接
	public static function relinkUnit($prev,$next){
		self::linkUnit($prev,$next);	    
	}		
	// 链表单位设值
	public static function setDlinkedList($obj,$key,$value){
		self::$_soul_writein_Dlinked_List[$obj][$key] = $value;
	}	
	// 获取 链表单位
	public static function getUnit($unit){
		return isset(self::$_soul_writein_Dlinked_List[$unit])
					?self::$_soul_writein_Dlinked_List[$unit]
					:false;
	}
	// 链表单位是否影响ipsp
	public static function isIPSPUnit($unit){
		if (self::labelUnit($unit)){
			return true;
		}
		if (isset(self::$_soul_writein_Dlinked_List[$unit]['ipsp'])){
			return self::$_soul_writein_Dlinked_List[$unit]['ipsp']?true:false;
		}
		return false;
	}
	// 获取 下个链表单位
	public static function nextUnit($unit){
		if (!$unit){
			return self::$_soul_writein_Dlinked_List_start;
		}
		return isset(self::$_soul_writein_Dlinked_List[$unit][N])
					?self::$_soul_writein_Dlinked_List[$unit][N]
					:false;
	}
	// 获取 上个链表单位
	public static function prevUnit($unit){
		return isset(self::$_soul_writein_Dlinked_List[$unit][P])
					?self::$_soul_writein_Dlinked_List[$unit][P]
					:false;
	}
	// 获取单位长度
	public static function lenUnit($unit){
		return isset(self::$_soul_writein_Dlinked_List[$unit]['len'])
					?self::$_soul_writein_Dlinked_List[$unit]['len']
					:0;
	}
	// 获取单位LABEL
	public static function labelUnit($unit){
		return isset(self::$_soul_writein_Dlinked_List[$unit][LABEL])
					?self::$_soul_writein_Dlinked_List[$unit][LABEL]
					:false;
	}
	// 获取单位rel_jmp
	public static function reljmpUnit($unit){
		return isset(self::$_soul_writein_Dlinked_List[$unit]['rel_jmp'])
					?self::$_soul_writein_Dlinked_List[$unit]['rel_jmp']
					:false;
	}

	// 摘除 双向 链表 中的 指定单位 
	public static function remove_from_DlinkedList($c_lp){

		Character::removeRate($c_lp); //清character.Rate

		$prev = self::$_soul_writein_Dlinked_List[$c_lp][P];
		$next = self::$_soul_writein_Dlinked_List[$c_lp][N];

		self::$_soul_writein_Dlinked_List[$c_lp]['302'] = $next;

		self::linkUnit($prev,$next);
	}
	// 创建一个新的链表单位(内容为$array)并追加到指定链表单位($prev)的后面(作为首单位则设为false),返回新的单位序号
	// $array          内容应由调用者保证安全
	public static function appendNewUnit($prev,$array){
		$c_id = self::newUnit($array);		
		if (false === $prev){
			$next = self::$_soul_writein_Dlinked_List_start;
		}elseif (false !== self::$_soul_writein_Dlinked_List[$prev][N]){
			$next = self::$_soul_writein_Dlinked_List[$prev][N];
		}else{
			$next = false;
		}
		self::linkUnit($prev,$c_id);
		self::linkUnit($c_id,$next);
		return $c_id;
	}
	// 建立一个新单位
	private static function newUnit($array){
		$c_id = self::$_s_w_Dlinked_List_index;
		self::$_s_w_Dlinked_List_index ++;
		$array[P] = false;
		$array[N] = false;
		self::$_soul_writein_Dlinked_List[$c_id] = $array;
		return $c_id;
	}
	// 链接2个前后单位 | false 则作为边界单位,不可皆为false
	private static function linkUnit($prev,$next){
		if (false === $prev){
			self::$_soul_writein_Dlinked_List_start = $next;
		}else{
			self::$_soul_writein_Dlinked_List[$prev][N] = $next;
		}
		if (false === $next){
			
		}else{
			self::$_soul_writein_Dlinked_List[$next][P] = $prev;
		}
	}
	// 
	public static function show(){
		$c = self::$_soul_writein_Dlinked_List_start;
		echo '<table border=1>';
		echo '<tr><td>No.</td><td>len</td><td>C</td><td>Organ</td></tr>';
		while ($c){			
			$u = self::getUnit($c);
			echo '<tr>';
			echo '<td>'."$c".'</td>';
			echo '<td>'.$u['len'].'</td>';
			echo '<td>'.$u[C].'</td>';
			echo '<td>';
			OrgansOperator::show($u[C]);
			echo '</td>';
			echo '</tr>';
			$c = self::nextUnit($c);
		}
		echo '</table>';

	}
}

?>