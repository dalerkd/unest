<?php

//处理优先级

class Character{

    private static $_rate;          // rate[type][rate][ListId] = ListId  (type: meat|bone|poly)
    private static $_done_idx;      // [ListId] = true , 已处理单位   
	private static $_rollback_rate;
	private static $_tpl;           // 优先级模板
	private static $_organ_idx;     // organ index

    public static function show(){
		echo "<table border=1>";
		echo '<tr bgcolor="#c0c0c0">';
		echo '<td>Character</td>';
        for ($i = 1;$i<10;$i++){
			echo '<td>'.$i.'</td>';
		}
		echo '</tr>';
        echo  '<tr><td>BONE</td>'; 
		for ($i = 1;$i<10;$i++) {
		    echo '<td>';
			if (is_array(self::$_rate[BONE][$i])){
				foreach (self::$_rate[BONE][$i] as $a){
					if (!isset(self::$_rate[BONE][$i+1][$a])){
						echo $a.'; ';
					}
				}
			}
			echo '</td>';
		}
        echo '</tr>';
        echo  '<tr bgcolor="#c0c0c0"><td>MEAT</td>';        
		for ($i = 1;$i<10;$i++) {
		    echo '<td>';
			if (is_array(self::$_rate[MEAT][$i])){
				foreach (self::$_rate[MEAT][$i] as $a){
					if (!isset(self::$_rate[MEAT][$i+1][$a])){
						echo $a.'; ';
					}
				}
			}
			echo '</td>';
		}
		echo '</tr>';
        echo  '<tr><td>POLY</td>';        
		for ($i = 1;$i<10;$i++) {
		    echo '<td>';
			if (is_array(self::$_rate[POLY][$i])){
				foreach (self::$_rate[POLY][$i] as $a){
					if (!isset(self::$_rate[POLY][$i+1][$a])){
						echo $a.'; ';
					}
				}
			}
			echo '</td>';
		}
        echo '</tr>';
		echo '</table>';	    
	}

	public static function ready(){
	    self::$_rollback_rate = self::$_rate;
	}

	public static function rollback(){
	    self::$_rate = self::$_rollback_rate;
	}

	public static function init(){
	    require dirname(__FILE__)."/../templates/character.tpl.php";
		self::$_tpl = $character_tpl;
		self::$_organ_idx = array(BONE,MEAT,POLY);
	}

	//合并，继承
	//当 $merge_rate 大于 原值 且 原值有效(>0) 时，以 $merge_rate为值
	public static function mergeRate($id,$origin_rate,$merge_rate){
	    foreach ($origin_rate as $type => $old){
			if (($old) and ($merge_rate[$type] > $old)){
				self::setRate($type,$id,$merge_rate[$type]);
			}
		}
	}

	//单位初始化
	//$DListID:  链表编号
	//$att    ： 属性来源(soul | meat | poly | bone)
	//返回    :  Rate
	public static function initUnit($DListID,$att=SOUL){
        
		$ret = false;

		//仅作为单位初始化使用，已存在单位直接返回
		if (!isset(self::$_done_idx[$DListID])){

			self::$_done_idx[$DListID] = true;
			
			// init value
			$ret[BONE] = self::$_tpl[CTPL_INI][$att][BONE];
			$ret[MEAT] = self::$_tpl[CTPL_INI][$att][MEAT];
			$ret[POLY] = self::$_tpl[CTPL_INI][$att][POLY];

			$obj = ConstructionDlinkedListOpt::getCode_from_DlinkedList($DListID);
			if (false === OrganPoly::get_usable_models($obj)){
			    $ret[POLY] = 0;
			}
			if (isset(self::$_tpl[CTPL_OPT][$obj[OPERATION]])){
				foreach (self::$_tpl[CTPL_OPT][$obj[OPERATION]] as $k => $v){
					if ($ret[$k] > 0){
						$ret[$k] += $v;
					}
				}
			}		
			
            self::setRate(POLY,$DListID,$ret[POLY]);
            self::setRate(BONE,$DListID,$ret[BONE]);
            self::setRate(MEAT,$DListID,$ret[MEAT]);
		}
		return $ret;
	}

	//获取操作目标(根据优先级)
	public static function random($type){
	    $i = rand(1,9);
		if (empty(self::$_rate[$type][$i])){ // 优先级无单位，退到 1 级，见readme.character.txt [2014.10.19]
            if (empty(self::$_rate[$type][1])){
			    return false;
			}else{
			    $i = 1;
			}
		}
		return array_rand(self::$_rate[$type][$i]);
	}
	
    //优先级变化 范围为 1-9 / 初始化时需设置0,使用::setRate()函数
	public static function modifyRate($organ,$id,$rate,$old = false){
		if ($rate != 0){
			if (false === $old){
				$old = self::getRate($organ,$id);
			}
			if ($old > 0){
				$new = $old + $rate;
				if ($new < 1){
					$new = 1;
				}elseif ($new > 9){
					$new = 9;
				}
				self::setRate($organ,$id,$new);
			}
		}
	}
	
	//清除所有Rate
	public static function removeRate($id){
	    self::setRate(POLY,$id,0);
	    self::setRate(BONE,$id,0);
	    self::setRate(MEAT,$id,0);
	}

	//获取目标单位优先级(全单位)
	public static function getAllRate($id){
	    $ret = array();
		$ret[POLY] = self::getRate(POLY,$id);
		$ret[BONE] = self::getRate(BONE,$id);
		$ret[MEAT] = self::getRate(MEAT,$id);
		return $ret;
	}

	//clone 优先级，并附加
	public static function cloneRate($id,$rate_array,$extra_rate){
	    foreach ($rate_array as $type => $rate){
		    if ($rate > 0){
			    $rate += $extra_rate;
				self::setRate($type,$id,$rate);
			}
		}
	}

	//获取当前优先级
	private static function getRate($organ,$id){
		$ret = 0;
		for ($i= 1;$i < 10;$i++){
		    if (!isset(self::$_rate[$organ][$i][$id])){
			    break;
			}else{
			    $ret = $i;
			}
		}
		return $ret;
	}
	// 设置优先级
	private static function setRate($organ,$id,$rate){
	    for ($i=1;$i<=9;$i++){
		    if ($rate >= $i){
			    self::$_rate[$organ][$i][$id] = $id;
			}elseif (isset(self::$_rate[$organ][$i][$id])){
			    unset(self::$_rate[$organ][$i][$id]);
			}else{
			    break;
			}
		}
	}	
}

?>