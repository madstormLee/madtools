<?php
namespace mad\tools;

class MadI18n {
	// service languages
	public static $languages = ['en-US', 'ko-KR'];

	public static function init() {
		$locale = str_replace( '-', '_', $_COOKIE['language'] ) . '.utf8';
		putenv("LANGUAGE=".$locale);
		$currentLocale = setlocale(LC_ALL, $locale);
		$domain = 'messages';
		bindtextdomain($domain, $_SERVER['DOCUMENT_ROOT'] . "/locale/nocache");
		bindtextdomain($domain, $_SERVER['DOCUMENT_ROOT'] . "/locale/");
		textdomain($domain);
	}
}
