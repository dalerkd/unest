<?php
// read the readme.sterographic.txt
class SteroGraphic{
	
	private static $_mono_space;
	private static $_mono_space_idx;
	private static $_stero_space_idx;
	private static $_units_map;
	private static $_mono2stero_map;
	private static $_stero2mono_map;
	private static $_units_map_id;

	const MONOID    = 0;
	const DIMENSION = 1;
	const SENTINEL  = 0;
	const PREV      = 1;
	const NEXT      = 2;

	private static $_units_map_insert;
	private static $_monos_4_insert;
	private static $_monos_4_insert_idx;
	private static $_monos_be_inserted;

    // insert balance pair
    public static function insert_balance_pair($balance_pair,$effects,$diff_dims){
    	$inserted = array();
    	// add dims for all effects
		foreach ($effects as $unit){
			self::$_units_map[$unit][self::DIMENSION] += $diff_dims;
		}    	
    	// start insert new unit
    	foreach ($balance_pair[0] as $pos => $isInclude){
    		if ($isInclude){
    			$direction = self::PREV;
    		}else{
    			$direction = self::NEXT;
    		}
    		$nid_1 = self::clone_unit($pos);
    		$nid_2 = self::clone_unit($pos);
			if ($isInclude){
    			self::$_units_map[$nid_1][self::DIMENSION] -= $diff_dims;
    		}else{
    			self::$_units_map[$nid_2][self::DIMENSION] += $diff_dims;
    		}    		
    		$inserted[$pos] = array($nid_1,$nid_2);
    		
    		self::insert_new_unit($nid_1,$pos,$direction);
    		self::insert_new_unit($nid_2,$nid_1,self::NEXT);
    	}

    	foreach ($balance_pair[1] as $pos => $isInclude){
    		if ($isInclude){
    			$direction = self::NEXT;
    		}else{
    			$direction = self::PREV;
    		}
    		$nid_1 = self::clone_unit($pos);
    		$nid_2 = self::clone_unit($pos);
			if ($isInclude){
    			self::$_units_map[$nid_1][self::DIMENSION] -= $diff_dims;
    		}else{
    			self::$_units_map[$nid_2][self::DIMENSION] += $diff_dims;
    		}
    		$inserted[$pos] = array($nid_2,$nid_1);

    		self::insert_new_unit($nid_1,$pos,$direction);
    		self::insert_new_unit($nid_2,$nid_1,self::PREV);
    	}
    	return $inserted;
    }
    // insert new unit into mono
    private static function insert_new_unit($nid,$pos,$direction){
    	$monos = self::get_unit_mono_ids($pos);
    	if (self::PREV === $direction){
    		$Re_direction = self::NEXT;
		}else{
    		$Re_direction = self::PREV;
    	}
    	foreach ($monos as $mono){
    		$tmp = self::$_mono_space[$mono][$pos][$direction];
    		self::$_mono_space[$mono][$pos][$direction] = $nid;
    		self::$_mono_space[$mono][$nid][$direction] = $tmp;
    		
    		self::$_mono_space[$mono][$tmp][$Re_direction] = $nid;
    		self::$_mono_space[$mono][$nid][$Re_direction] = $pos;
    	}
    }    
    // clone unit by _units_map
    private static function clone_unit($unit){
    	$nid = self::$_units_map_id;
    	self::$_units_map_id ++;
    	self::$_units_map[$nid] = self::$_units_map[$unit];
    	return $nid;
    }
    // collect all effects
    public static function collect_insert_effects($balance_pair){
    	$effect_units = array();
    	$effect_units_cache = array();
    	$ends = array();
    	foreach ($balance_pair[1] as $uid => $isInclude){
    		$monos = self::get_unit_mono_ids($uid);
    		if (!$monos){return false;}
    		foreach ($monos as $mono){
    			$c = $uid;
    			if (!$isInclude){
    				$c = self::get_prev_unit($mono,$uid);
    			}
    			$ends[$mono][$c] = true;
    		}
    	}
    	foreach ($balance_pair[0] as $uid => $isInclude){
    		$monos = self::get_unit_mono_ids($uid);
    		if (!$monos){return false;}
    		foreach ($monos as $mono){
    			$c = $uid;
    			if (!$isInclude){
    				$c = self::get_next_unit($mono,$c);
    			}
    			while (true){
    				if (!isset($effect_units_cache[$c])){
	    				$effect_units_cache[$c] = self::get_unit_mono_ids($c);
	    			}
	    			$k = array_search($mono,$effect_units_cache[$c]);
	    			unset($effect_units_cache[$c][$k]);
	    			if (empty($effect_units_cache[$c])){
	    				unset ($effect_units_cache[$c]);
	    				$effect_units[$c] = true;
	    			}
	    			if (isset($ends[$mono][$c])){
	    				unset($ends[$mono][$c]);
	    				if (empty($ends[$mono])){
	    					unset ($ends[$mono]);
	    				}
	    				break;	    				
	    			}
	    			$c = self::get_next_unit($mono,$c);
	    			if (!$c){return false;}
    			}
    		}
    	}
    	if ((!empty($ends)) or (!empty($effect_units_cache))){
    		return false;
    	}
    	
    	return array_keys($effect_units);
    }
    // ready to insert mono to stero space
	public static function insert_ready($new_mono,$insert_pos,$insert_direct){
		if (isset(self::$_units_map[$insert_pos])){
			self::$_monos_4_insert_idx ++;
			self::$_monos_4_insert[$_monos_4_insert_idx] = $new_mono;
			foreach (self::$_units_map[$insert_pos][self::MONOID] as $a){
				self::$_monos_be_inserted[$a][$insert_pos][$insert_direct][] = self::$_monos_4_insert_idx;
			}
			return true;
		}else{
			return false;
		}
	}
	// get specified mono's neighbour
	public static function get_parallel_monos($mono){
		if (isset(self::$_mono2stero_map[$mono])){
			if (isset(self::$_stero2mono_map[self::$_mono2stero_map[$mono]])){
				return array_keys(self::$_stero2mono_map[self::$_mono2stero_map[$mono]]);
			}
		}
		return false;
	}
	// reset all global variables for insert
	private static function init_insert(){
		self::$_units_map_insert   = array();
		self::$_monos_4_insert     = array();
		self::$_monos_4_insert_idx = 0;
	    self::$_monos_be_inserted  = array();
	}
	// is wormhole?
	private static function is_wormhole($uid){
		return (1<count(self::$_units_map[$uid][self::MONOID]))?true:false;
	}
	// 获取 数据单位的 mono space id(s)
	public static function get_unit_mono_ids($uid){
		return (isset(self::$_units_map[$uid][self::MONOID]))?(self::$_units_map[$uid][self::MONOID]):false;
	}
	// get next unit (id) 
	public static function get_next_unit($mono,$unit){
		return self::get_mono_unit($mono,$unit,self::NEXT);
	}
	// get prev unit (id)
	public static function get_prev_unit($mono,$unit){
		return self::get_mono_unit($mono,$unit,self::PREV);
	}
	// get unit's dim
	public static function get_dim($uid){
		if (isset(self::$_units_map[$uid][self::DIMENSION])){
			return self::$_units_map[$uid][self::DIMENSION];
		}else{
			return false;
		}
	}
	// change the stero space dimension
	private static function stero_dimension_adjust($stero_idx,$dim){
		$units = array();
		foreach (self::$_stero2mono_map[$stero_idx] as $mono_id => $a){
			$c = 0;
			while (true){
				$c = self::get_mono_unit($mono_id,$c);				
				if ($c <= 0){break;}
				$units[$c] = true;
			}
		}		
		if (!empty($units)){
			self::units_dimension_adjust((array_keys($units)),$dim);
		}
	}
	// change the units' dimension
	private static function units_dimension_adjust($units,$dim){
		foreach ($units as $unit){
			self::$_units_map[$unit][self::DIMENSION] += $dim;
		}
	}
	// wormhole scanner
	public static function wormhole_scanner($mono,$dim){
		$wormholes = array();
		foreach ($mono as $a => $unit){
			if (isset(self::$_units_map[$unit])){ // wormhole!
				$dim_offset = $dim[$unit] - self::$_units_map[$unit][self::DIMENSION];
				$c_mono  = reset(self::$_units_map[$unit][self::MONOID]); // a unit belongs one stero only
				$c_stero = self::$_mono2stero_map[$c_mono];

				$wormholes[$unit][0] = $c_stero;
				$wormholes[$unit][1] = $dim_offset;
			}
		}
		return $wormholes;
	}
	// ripperpoint scanner
	private static function ripper_scanner($wormholes){
		$ret = array();
		$keep_dim = array();
		foreach ($wormholes as $a => $b){
			if (!isset($keep_dim[$b[0]])){
				$keep_dim[$b[0]] = $b[1];
			}else{
				if ($b[1] != $keep_dim[$b[0]]){ // ripperpoint!
					$keep_dim[$b[0]] = $b[1];
					$ret[$a] = true;
				}
			}
		}
		return $ret;
	}
	// seeker of the mono space
	private static function get_mono_unit($mono_id,$c_unit=0,$direct=self::NEXT){
		return (isset(self::$_mono_space[$mono_id][$c_unit][$direct]))?(self::$_mono_space[$mono_id][$c_unit][$direct]):0;
	}
	// save odd mono space as new stero space
	private static function mono_save_as_stero($mono,$dim){
		self::$_mono_space_idx++;
		$prev = self::SENTINEL;
		foreach ($mono as $a => $unit){
			self::$_mono_space[self::$_mono_space_idx][$prev][self::NEXT] = $unit;
			self::$_mono_space[self::$_mono_space_idx][$unit][self::PREV] = $prev;
			$prev = $unit;
			self::$_units_map[$unit][self::MONOID][] = self::$_mono_space_idx;
			self::$_units_map[$unit][self::DIMENSION] = $dim[$unit];
		}
		self::$_mono_space[self::$_mono_space_idx][$prev][self::NEXT] = self::SENTINEL;
		self::$_mono_space[self::$_mono_space_idx][self::SENTINEL][self::PREV] = $prev;

		self::$_stero_space_idx++;
		self::$_mono2stero_map[self::$_mono_space_idx] = self::$_stero_space_idx;
		self::$_stero2mono_map[self::$_stero_space_idx][self::$_mono_space_idx] = true;
		return self::$_stero_space_idx;
	}
	// combine two stero_space (* balance dim already)
	private static function combine_stero_space($src,$dst){
		// echo '<br>'."space combine: $src => $dst <br>";
		// var_dump (self::$_stero2mono_map[$src]);
		$tmp = self::$_stero2mono_map[$src];
		foreach ($tmp as $mono => $true){
			self::$_mono2stero_map[$mono] = $dst;
			self::$_stero2mono_map[$dst][$mono] = true;
		}
		unset (self::$_stero2mono_map[$src]);
	}
	// insert new mono into universe (filted any ripper points already!!!)
	private static function new_mono_into_universe($c_mono,$dim){
		$effects_spaces = array();
		$wormholes = self::wormhole_scanner($c_mono,$dim);
		if (!empty($wormholes)){
			$alignment = array();
			foreach ($wormholes as $effects){
				if (0 != $effects[1]){
					$alignment[$effects[0]] = $effects[1];
				}
				$effects_spaces[$effects[0]] = true;
			}
			if (!empty($alignment)){
				foreach ($alignment as $stero_id => $dim_effects){
					echo '<br>alignment: '."$stero_id".' => '."$dim_effects";
					self::stero_dimension_adjust($stero_id,$dim_effects);
				}
			}			
		}
		$new_stero_id = self::mono_save_as_stero($c_mono,$dim);
		if (!empty($effects_spaces)){
			foreach ($effects_spaces as $src => $true){
				self::combine_stero_space($src,$new_stero_id);
			}	
		}
	}
	// del unit from mono_space
	private static function delete_mono_unit($unit_id,$mono_id){
		$prev = self::$_mono_space[$mono_id][$unit_id][self::PREV];
		$next = self::$_mono_space[$mono_id][$unit_id][self::NEXT];
		self::$_mono_space[$mono_id][$prev][self::NEXT] = $next;
		self::$_mono_space[$mono_id][$next][self::PREV] = $prev;
		unset(self::$_mono_space[$mono_id][$unit_id]);
	}
	// del unit from units_map
	private static function delete_units_map_unit($unit_id,$mono_id){
		if (isset(self::$_units_map[$unit_id][self::MONOID])){
			$pos = array_search($mono_id,self::$_units_map[$unit_id][self::MONOID]);
			if (false !== $pos){
				if (1 == count(self::$_units_map[$unit_id][self::MONOID])){
					unset(self::$_units_map[$unit_id]);
				}else{
					unset (self::$_units_map[$unit_id][self::MONOID][$pos]);
					self::$_units_map[$unit_id][self::MONOID] = array_values(self::$_units_map[$unit_id][self::MONOID]);
				}
			}
		}
	}
	// drop (unit of mono) from mono space & units_map 
	private static function drop_units($units,$mono_id){
		foreach ($units as $unit_id){
			self::delete_mono_unit($unit_id,$mono_id);
			self::delete_units_map_unit($unit_id,$mono_id);
		}		
	}
	// dim value to dim effects	
	private static function dim_to_dim_effects($units,$dim){
		$ret = array();
		$c = false;
		foreach ($dim as $i => $a){
			if (false === $c){
				$c = $a;
			}else{
				if ($c !== $a){
					$ret[$units[$i]] = $a - $c;
					$c = $a;
				}
			}
		}
		return $ret;
	}
	// mono space ripper
	private static function mono_ripper($mono_id,$ripper_point){
		$ret = array(); // units by ripped!
		$matched = false;
		$new = array();
		$new_dim = array();
		if (isset(self::$_mono_space[$mono_id])){
			$unit = 0;
			while (true){
				$unit = self::get_mono_unit($mono_id,$unit);
				if ($unit <= 0){break;}
				
				if ($unit == $ripper_point){
					$matched = true;
				}
				if (!$matched){
					$new[] = $unit;
					$new_dim[] = self::$_units_map[$unit][self::DIMENSION];
				}
			}			
		}
		if (true === $matched){
			if (!empty($new)){
				$ret['units'] = $new;		
				$ret['dim']   = self::dim_to_dim_effects($new,$new_dim);
				self::drop_units($new,$mono_id);
			}
		}
		return $ret;
	}	
	// stero space ripper
	private static function stero_ripper($ripper_point){		
		$ripped_mono = array();
		if (isset(self::$_units_map[$ripper_point])){
			foreach (self::$_units_map[$ripper_point][self::MONOID] as $mono_id){
				$ripped_mono[] = self::mono_ripper($mono_id,$ripper_point);				
			}			
		}
		if (!empty($ripped_mono)){
			// import the ripped units as new mono 
			foreach ($ripped_mono as $new){
				if (!empty($new)){
					self::import($new['units'],$new['dim']);
				}
			}
		}
	}
	// delete mono space from universe
	private static function delete_mono_space($mono_id){
		// var_dump ($mono_id);
		$c_unit = 0;
		while (0!=($c_unit = self::get_mono_unit($mono_id,$c_unit))){
			self::delete_units_map_unit($c_unit,$mono_id);
		}
		unset (self::$_mono_space[$mono_id]);
		$stero_id = self::$_mono2stero_map[$mono_id];
		unset (self::$_mono2stero_map[$mono_id]);
		unset (self::$_stero2mono_map[$stero_id][$mono_id]);
		if (empty(self::$_stero2mono_map[$stero_id])){
			unset (self::$_stero2mono_map[$stero_id]);
		}
	}
	// delete stero space from universe
	private static function delete_stero_space($stero_id){
		$monos = self::$_stero2mono_map[$stero_id];
		foreach ($monos as $mono_id => $true){
			self::delete_mono_space($mono_id);
		}
	}
	// del reduplicated mono space by scan all stero
	private static function erase_reduplicated_mono(){
		$should_be_erased = array();
		foreach (self::$_stero2mono_map as $c_monos){
			$c_included_array = array();
			foreach ($c_monos as $mono_id => $true){
				$c = self::get_mono_unit($mono_id,0);
				if (1 < count(self::$_units_map[$c][self::MONOID])){
					$c_redup = self::$_units_map[$c][self::MONOID];
					$a = array_search($mono_id, $c_redup);
					unset($c_redup[$a]);
					while ($c = self::get_mono_unit($mono_id,$c)){
						if (1 < count(self::$_units_map[$c])){
							$c_redup = array_intersect($c_redup,self::$_units_map[$c][self::MONOID]);
							if (!empty($c_redup)){
								continue;
							}							
						}
						break;
					}
					if (!empty($c_redup)){
						foreach ($c_redup as $a){
							$c_included_array[$mono_id][$a] = true;
							if (isset($c_included_array[$a][$mono_id])){
								if (true === $c_included_array[$a][$mono_id]){
									if ($a > $mono_id){
										$should_be_erased[] = $a;
									}else{
										$should_be_erased[] = $mono_id;
									}

								}
							}
						}
					}
				}				
			}			
		}
		if (!empty($should_be_erased)){
			foreach ($should_be_erased as $id){
				self::delete_mono_space($id);
			}
		}
	}	
	// delete steros which units less than $min_number
	public static function delete_min_stero($min_number){
		$should_be_erased = array();
		foreach (self::$_stero2mono_map as $stero_id => $c_monos){
			$uniqu_numbers = 0;
			$uniqu_units = array();
			$break = false;
			foreach ($c_monos as $mono_id => $true){
				$c = 0;
				while (0!=($c = self::get_mono_unit($mono_id,$c))){
					if (!isset($uniqu_units[$c])){
						$uniqu_numbers ++;
						$uniqu_units[$c] = true;
					}
					if ($uniqu_numbers >= $min_number){
						$break = true;
						break;
					}
				}
				if ($break){
					break;
				}
			}
			if (!$break){
				$should_be_erased[] = $stero_id;
			}			
		}
		if (!empty($should_be_erased)){			
			foreach ($should_be_erased as $id){
				self::delete_stero_space($id);
			}
		}
	}
	// unserialize input
	public static function unserialize($str){
		$tmp = unserialize($str);
		self::$_mono_space      = $tmp[1];
		self::$_mono_space_idx  = $tmp[2];
		self::$_stero_space_idx = $tmp[3];
	    self::$_units_map       = $tmp[4];
	    self::$_mono2stero_map  = $tmp[5];
		self::$_stero2mono_map  = $tmp[6];

		self::$_units_map_id = max(array_keys(self::$_units_map)) + 1;
	}
	// serialize output
	public static function serialize(){
		self::erase_reduplicated_mono();

		$tmp[1] = self::$_mono_space;
		$tmp[2] = self::$_mono_space_idx;
		$tmp[3] = self::$_stero_space_idx;
	    $tmp[4] = self::$_units_map;
	    $tmp[5] = self::$_mono2stero_map;
		$tmp[6] = self::$_stero2mono_map;
		return serialize($tmp);
	}
	// reset all global variables
	public static function init(){
		self::$_mono_space = array();
		// self::$_stero_space = array();
		self::$_mono_space_idx = 0;
		self::$_stero_space_idx = 0;
		self::$_units_map = array();
		self::$_mono2stero_map = array();
		self::$_stero2mono_map = array();
	}
	// import as a new mono space
	public static function import($units,$dimension_effects){
		// format
		$i = 0;
		$c_dimension = 0;
		$dimension = array();
		$mono = array();
		foreach ($units as $b){
			if (isset($dimension_effects[$b])){
				$c_dimension += $dimension_effects[$b];
			}
			$mono[$i] = $b;
			$dimension[$b] = $c_dimension;			
			$i ++;
		}	
		// get wormholes -> ripperpoints -> ripper them!	
		$wormholes = self::wormhole_scanner($mono,$dimension);
		if (count($wormholes) > 1){ // perhapes has ripper point 			
			$ripperpoint = self::ripper_scanner($wormholes);
			if (!empty($ripperpoint)){ // has ripper points !
				foreach ($ripperpoint as $ripper_point => $true){
					self::stero_ripper($ripper_point);   // ripper the steros
				}
			}
		}

		// 根据ripper point 分段 insert into universe
		$c_units     = array();
		$c_dim   	 = array();
		$i = 0;
		foreach ($mono as $c_unit){
			if (isset($ripperpoint[$c_unit])){ // ripper point !				
				$i++;
			}
			$c_units[$i][] = $c_unit;
			$c_dim[$i][$c_unit] = $dimension[$c_unit];
		}
		foreach ($c_units as $i => $a){
			self::new_mono_into_universe($a,$c_dim[$i]);
		}
	}	
	// get all dims level from steros
	private static function get_dims_from_monos($stero_id){
		$ret = array();
		if (isset(self::$_stero2mono_map[$stero_id])){
			foreach (self::$_stero2mono_map[$stero_id] as $mono_id => $a){
				$c = 0;
				while (true){
					$c = self::get_mono_unit($mono_id,$c);				
					if ($c <= 0){break;}
					$ret[self::$_units_map[$c][self::DIMENSION]] = true;
				}
			}
		}
		if (!empty($ret)){
			$ret = array_keys($ret);
			rsort($ret);
		}
		return $ret;
	}
	// 确定 数据单位表示的position(x,y)
	private static function determine_unit_position($stero_id,&$max_x){
		$ret = array();
		$max_x = 0 ; // max x axis
		$wormholes = array();
		if (isset(self::$_stero2mono_map[$stero_id])){
			// 确定所有 wormhole 的max pos
			$wormhole_max_pos = array();
			while (True){
				$any_changed = false;
				foreach (self::$_stero2mono_map[$stero_id] as $mono_id => $true){
					$pos = 0;

					$c = 0;
					while (true){
						$c = self::get_mono_unit($mono_id,$c);				
						if ($c <= 0){break;}
						if (self::is_wormhole($c)){
							if (!isset($wormhole_max_pos[$c])){
								$wormhole_max_pos[$c] = $pos;
							}else{
								if (($wormhole_max_pos[$c]) < $pos){
									$wormhole_max_pos[$c] = $pos;
									$any_changed = true;
								}else{
									$pos = $wormhole_max_pos[$c];
								}
							}
						}
						$pos ++;
					}
				}
				if (!$any_changed){
					break;
				}
			}
			// 分配x y 轴给units (by wormhole as pole)
			foreach (self::$_stero2mono_map[$stero_id] as $mono_id => $true){
				$x_pos = 0;

				$c = 0;
				while (true){
					$c = self::get_mono_unit($mono_id,$c);				
					if ($c <= 0){break;}				
				
					$y_pos = self::get_dim($c);
					if (isset($wormhole_max_pos[$c])){
						$x_pos = $wormhole_max_pos[$c];						
					}
					$ret[$x_pos][$y_pos][$c][] = $mono_id;
					if ($x_pos > $max_x){
						$max_x = $x_pos;
					}
					$x_pos ++;
				}
			}
		}		
		return $ret;
	}
	// show current universe
	public static function show(){
		foreach (self::$_stero2mono_map as $stero_id => $monos){
			$process_steps = 0;
			$dimension_array = self::get_dims_from_monos($stero_id);
			// x axis: process steps  y axis: dim level
			$units = self::determine_unit_position($stero_id,$process_steps);
			echo '<br><b>Stero ID:'.$stero_id.'</b> ([mono id:mono id:...]-unit id)';
			echo '<table border=1>';
			echo '<tr><td>Dimension</td>';
			for ($i=0;$i<=$process_steps;$i++){
				echo '<td>'.$i.'</td>';
			}
			echo '</tr>';
			foreach ($dimension_array as $dim){
				echo '<tr>';
				echo '<td>'.$dim.'</td>';
				for ($i=0;$i<=$process_steps;$i++){
					echo '<td>';
					if (isset($units[$i][$dim])){
						foreach ($units[$i][$dim] as $uid => $u){
							$string = '[';
							foreach ($u as $mono){
								$string .= $mono.':';
							}
							$string = substr($string,0,strlen($string)-1);
							echo $string."]-$uid".',';
						}
					}
					echo '</td>';
				}
				echo '</tr>';
			}

			echo '</table>';
		}
	}
}

?>