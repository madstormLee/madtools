<?php
namespace mad\tools;

class MadValidation {
	static function email($email) {
		return preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/',$email);
	}

	static function validateEmail($email) {
		if( ! self::email($email) ) {
			return false;
		}
		$domain = end(explode("@", $email));
		return getmxrr($domain, $mxhosts) || @fsockopen($domain, 25, $errno, $errstr, 5);
	}

	// 주민등록번호진위여부 확인 함수
	static function regident($rn) {
		$rn = preg_replace( '/\D/', '', strval( $rn ) );
		if ( strlen($rn) != 13 ||
			(! preg_match('/^\d{6}[1-4]\d{6}$/', $rn)) ||
			(! checkdate(substr($rn, 2, 2), substr($rn, 4, 2), (('2' >= $rn[6]) ? '19' : '20') . substr($rn, 0, 2)))
		) {
			return false;
		}

		$sum = 0;
		foreach( str_split('234567892345') as $i => $w ) {
			$sum += $rn[$i] * $w;
		}

		return (11 - ($sum % 11)) % 10 == $rn[12];
	}

	// 사업자등록번호 체크 함수
	static function isBusinessNumber($bn) {
		$bn = preg_replace( '/\D/', '', strval( $bn ) );
		if (strlen($bn) != 10) {
			return false;
		}

		$sum = 0;
		foreach( str_split('137137135') as $i => $w ) {
			$sum += $bn[$i] * $w;
		}
		$sum = $sum + $bn[8] * 5 / 10;

		return (10 - ($sum % 10) )% 10 == $bn[9];
	}
}
