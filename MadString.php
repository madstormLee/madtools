<?php
namespace mad\tools;

class MadString implements IteratorAggregate {
	private $string = '';

	function __construct( $string ) {
		$this->string = $string;
	}
	public static function create( $string ) {
		return new self( $string );
	}
	// $name->f('underscore|htmlEntities')->cut( 20 );
	// $name->f()->cut( 20 );
	function f( $formatter = '' ) {
		$rv = clone $this;
		return $rv->format( $formatter );
	}
	static function strCut($str, $len) {
		return (mb_strlen($str) <= $len) ? $str : mb_substr($str, 0, $len, 'utf-8') . '..';
	}
	static function encode2047( $subject ) {
		return new self('=?euc-kr?b?'.base64_encode($subject).'?=');
	}
	// XSS 관련 태그 제거
	static function cleanXssTags($str) {
		return preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $str);
	}

	static function getImages( $content ) {
		if ( preg_match_all( '/src=["\']([^"]*)["\']/i', $content, $images ) ) {
			return $images[1];
		}
		return array();
	}
	static function getImage( $content ) {
		return current(self::getImages($content));
	}

	function getIterator(): \Traversable {
		return new ArrayIterator( str_split( $this->string ) );
	}

	function cut( $start=0, $length='' ) {
		if ( $length === '' ) {
			$length = $start;
			$start = 0;
		}
		$this->string = mb_strcut( $this->string, $start, $length );
		return $this;
	} 

	function sub( $start=0, $length='' ) {
		if ( $length === '' ) {
			$length = $start;
			$start = 0;
		}
		$this->string = mb_substr( $this->string, $start, $length );
		return $this;
	}

	function format( $formatter='' ) {
		if ( empty( $formatter ) ) {
			return $this;
		}
		$methods = explode( '|', $formatter );
		foreach( $methods as $method ) {
			$this->$method();
		}
		return $this;
	}

	function underscore() {
		$this->lcFirst();
		$func = create_function('$c', 'return "_" . mb_strToLower($c[1]);');
		$this->string = preg_replace_callback('/([A-Z])/', $func, $this->string);
		return $this;
	}
	function camel($capitalise_first_char = false) {
		if($capitalise_first_char) {
			$this->ucFirst();
		}
		$func = create_function('$c', 'return mb_strToUpper($c[1]);');
		$this->string = preg_replace_callback('/_([a-z])/', $func, $this->string);
		return $this;
	}
	function upper() {
		$this->string = mb_strToUpper($this->string);
		return $this;
	}
	function ucFirst() {
		$this->string[0] = mb_strToUpper($this->string[0]);
		return $this;
	}
	function lcFirst() {
		$this->string[0] = mb_strToLower($this->string[0]);
		return $this;
	}
	function len() {
		return mb_strlen( $this->string );
	}
	function isDate() {
		if ( preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $$this->string) ) {
			return true;
		}
		return false;
	}
	function blank() {
		$value = trim($this->string);
		return empty($value);
	}
	function __call( $method, $args ) {
		$func = 'str_' . $method;
		if ( function_exists( $func ) ) {
			array_push( $args,$this->string );
			$this->string = call_user_func_array( $func, $args );
			return $this;
		}
		throw new \Exception('Method not found.');
	}
	function __toString() {
		return $this->string;
	}
}
