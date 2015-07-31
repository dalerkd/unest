<?php
class RelocInfo{
	private static $_reloc;
	private static $_pattern;

	public static function init($src){
		self::$_reloc = $src;
		self::$_pattern = '/('.UNIQUEHEAD.'RELINFO_[\d]{1,}_[\d]{1,}_[\d]{1,})/';
	}
	public static function transString2Array($src){
		if (preg_match(self::$_pattern,$src,$tmp)){
			$tmp = explode ('_',$tmp[0]);
			if ((isset($tmp[3])) and (isset($tmp[4]))){
				return array('sec'=>$tmp[2],'i'=>$tmp[3],C=>$tmp[4]);
			}			
		}
		return false;
	}
	public static function cloneUnit($id,$key){
		$new = $key + 1;
		if (isset(self::$_reloc[$id][$key])){		
			while (isset(self::$_reloc[$id][$new])){
				$new ++;
			}
			self::$_reloc[$id][$new] = self::$_reloc[$id][$key];
		}else{
			return false;
		}
		return $new;
	}
	public static function resetUnit($id,$key,$value){
		if (isset(self::$_reloc[$id][$key])){
			foreach ($value as $k => $v){
				self::$_reloc[$id][$key][$k] = $v;
			}
		}
	}
	public static function getValue($id,$key){
		return isset(self::$_reloc[$id][$key]['value'])?self::$_reloc[$id][$key]['value']:NULL;
	}
	public static function getType($id,$key){
		return isset(self::$_reloc[$id][$key]['Type'])?self::$_reloc[$id][$key]['Type']:NULL;
	}
	public static function getSymbolTableIndex($id,$key){
		return isset(self::$_reloc[$id][$key]['SymbolTableIndex'])?self::$_reloc[$id][$key]['SymbolTableIndex']:NULL;
	}
	public static function isMem($id,$key){
		return isset(self::$_reloc[$id][$key]['isMem'])?self::$_reloc[$id][$key]['isMem']:false;
	}
	public static function isLabel($id,$key){
		return isset(self::$_reloc[$id][$key]['isLabel'])?self::$_reloc[$id][$key]['isLabel']:false;
	}




}
?>