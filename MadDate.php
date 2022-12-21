<?php
namespace mad\tools;

class MadDate {
	static function mysqlTimezone() {
		return (new DateTime('now', self::timezone()))->format('P');
	}

	static function timezone() {
		static $tz;
		if( ! $tz) {
			$name = isset($_COOKIE['timezone']) ? $_COOKIE['timezone'] : 'Asia/Seoul';
			$tz = new DateTimeZone($name);
		}
		return $tz;
	}

	static function localDate($date, $format = 'Y-m-d H:i:s') {
		return (new DateTime($date))->setTimeZone(self::timezone())->format($format);
	}

	static function weekKr($chk) {
		return '월화수목금토'[$chk%7] . '요일';
	}
}
