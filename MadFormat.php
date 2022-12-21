<?php
namespace mad\tools;

class MadFormat {
	private static $instance;

	private function __construct() {}

	public static function getInstance() {
		if( is_null(self::$instance) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function number2hangul($number){ 
		$num = array('', '1', '2', '3', '4', '5', '6', '7', '8', '9'); 
		$unit4 = array('', '만', '억', '조', '경'); 
		$unit1 = array('', '십', '백', '천'); 

		$res = array(); 

		$number = str_replace(',','',$number); 
		$split4 = str_split(strrev((string)$number),4); 

		for($i=0;$i<count($split4);$i++){
			$temp = array(); 
			$split1 = str_split((string)$split4[$i], 1); 
			for($j=0;$j<count($split1);$j++){ 
				$u = (int)$split1[$j]; 
				if($u > 0) {
					$temp[] = $num[$u].$unit1[$j]; 
				}
			}
			if(count($temp) > 0) {
				$res[] = implode('', array_reverse($temp)).$unit4[$i]; 
			}
		} 
		return implode('', array_reverse($res)); 
	}

	function billion($number) {
		$val = is_numeric($number) ? $number : 0;

		if ($val <= 0) {
			return '0원';
		}
		$billion = round($val / 10000, 2);
		return ( $billion >= 1 ) ? $billion."억" : round($val / 1000, 2) ."천";
	}
}
