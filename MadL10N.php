<?php
namespace mad\tools;

class MadL10N {
	private static $instance = null;
	protected $data = null;

	private $locales = null;
	private static $localeFile = 'locale.json';

	private function __construct() {
		if ( ! isset( $_SESSION[__CLASS__] ) ) {
			$_SESSION[__CLASS__] = new MadData;
		}
		$this->data = &$_SESSION[__CLASS__];

		if ( $this->data->isEmpty() ) {
			$this->setCode();
		}
	}
	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	public function getLocales() {
		if ( empty( $this->locales ) ) {
			$this->locales = new MadJson( self::$localeFile );
		}
		return $this->locales;
	}
	private function setDataFromId( $id ) {
		$locales = $this->getLocales();
		if ( $locales->$id ) {
			return $this->setDataFromCode( $locales->$id->code );
		}
		return $this;
	}
	private function setCookie() {
		return setcookie("lo", $this->code,  strToTime("+1 year"), "/", $_SERVER["HTTP_HOST"]);
	}
	private function setDataFromCode( $code ) {
		$locales = clone $this->getLocales();

		if ( $locales->isEmpty() ) {
			return $this->setDataFromLocale( $code );
		}

		$locales->index( 'code' );
		if ( ! $target = $locales->$code ) {
			return $this;
		}
		$this->setData( $target );
		$this->setCookie();

		return $this;
	}
	public function setCode( $locale = '' ) {
		if ( ! empty( $locale ) ) {
			$this->setDataFromCode( $locale );
		}
		// first of all : use cookie.
		$cookie = new MadCookie;
		if ( empty( $this->code ) && $cookie->locale ) {
			$this->setDataFromCode( $cookie->locale );
		}
		// second: use geoip;
		// this is heavy. but almostly matching good.
		if ( ! $this->code ) {
			$id = strToLower( MadGeoIp::getInstance()->countryCode );
			$this->setDataFromId( $id );
		}
		// thirdly: use accept_language if still dont know user locale
		$server = MadParams::create('_SERVER');
		if ( ! $this->code ) {
			$locale = Locale::acceptFromHttp( $server->HTTP_ACCEPT_LANGUAGE );
		}

		if ( ! $this->code ) {
			$this->setDataFromCode( 'ja_JP' );
		}

		return $this;
	}
	function setCodeFromId( $id ) {
		$locale = $this->getLocales()->$id;
		if ( ! $code = $locale->code ) {
			$code = '';
		}
		return $this->setCode( $code );
	}
	private function setDataFromLocale( $locale ) {
		if ( ! class_exists( 'Locale' ) ) {
			return false;
		}
		$data = new MadData( array(
					'id' => Locale::getPrimaryLanguage( $locale ),
					'code' => $locale,
					'label' => Locale::getDisplayRegion( $locale ),
					'language' => Locale::getPrimaryLanguage( $locale ),
					'region' => Locale::getRegion( $locale ),
					) );
		$this->setData( $data );
		return $this;
	}
	public function getCode() {
		return $this->code;
	}
	public function getLabel( $id ) {
		$locales = $this->getLocales();
		return $locales->$id->label;
	}
	public function isCode() {
		return !! $this->code;
	}
	public function clear() {
		$this->data->clear();
		return $this;
	}
	protected function setData( MadData $data ) {
		$this->data->setData( $data );
	}
	function __set( $key, $value ) {
		throw new Exception( 'Not accessable!' );
	}
	function __get( $key ) {
		return $this->data->$key;
	}
	function __toString() {
		return $this->id;
	}
	function setTimezone() {
		date_default_timezone_set( $this->timezone );
		MadDb::create()->exec( "set time zone '{$this->timezone}'" );
	}
	function setLocale() {
		putenv( "LANG={$this->language}" ); 
		setLocale( LC_ALL, $this->language );
	}
	function setGettext( $domain = 'messages' ) {
		bindTextDomain( $domain, "locale" ); 
		textDomain($domain);
	}
}
