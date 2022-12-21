<?
namespace mad\tools;

class MadSet {
	private $data = array();
	private $filter = array();
	private $defaults = array();
	public $validator;

	function __construct($data='') {
		if (! empty($data) ) $this->set($data);
	}
	function validate($data) {
		$this->validator = new MadValidator($this->data);
		return $this->validator->validate($data);
	}
	function __set($key, $value) {
		$this->data[$key] = $value;
	}
	function __get($key) {
		return ( isset($this->data[$key]) ) ? $this->data[$key] : false;
	}
	function __isset($key) {
		return isset($this->data[$key]);
	}
	function set( $data = array() ) {
		$this->data=$data;
	}
	function get() {
		return $this->data;
	}
	function add( $key, $value){
		$this->data[$key]=$value;
	}
	function change($key, $value) {
		$this->data[$key]=$value;
	}
	function remove ($key){
		unset ( $this->data[$key] ) ;
	}
	function __unset( $key ) {
		unset ( $this->data[$key] ) ;
	}
	function decode() {
		$rv = array();
		foreach ( $this->data as $key => $value ) {
			// $rv[$key] = rawurldecode($value);
			$rv[$key] = iconv("UTF-8","euc-kr",rawurldecode($value));
		}
		$this->data = $rv;
	}
	function decode2utf8() {
		$rv = array();
		foreach ( $this->data as $key => $value ) {
			$rv[$key] = iconv("UTF-8","euc-kr",rawurldecode($value));
		}
		$this->data = $rv;
	}
	function filtering() {
		if ( empty( $this->filter ) ) {
			return false;
		}
		foreach( $this->data as $key => $value ) {
			if ( ! in_array( $key, $this->filter ) ) {
				unset( $this->data[$key] );
			}
		}
	}
	function defaulting() {
		foreach( $this->defaults as $key => $value ) {
			if ( empty( $this->data[$key] ) ) {
				$this->data[$key] = $value;
			}
		}
	}
	function setFilter( $filter = array() ) {
		$this->filter = $filter;
		return $this;
	}
	function setDefaults( $defaults = array() ) {
		$this->defaults = $defaults;
		return $this;
	}
	function __toString() {
		$functions = array(
			'now',
			'password',
		);
		$this->defaulting();
		$this->filtering();
		$set = array();
		foreach($this->data as $key => $value){
			if ( is_array($value) ) {
				$value = implode('-', $value);
			}
			if ( strpos( $value, '(' ) &&
				in_array( substr( $value, 0, strpos( $value, '(') ), $functions)
			) {
				$set[] = "`$key`=$value";
			} else {
				$set[] = "`$key`='$value'";
			}
		}
		$rv= 'set '.implode(', ', $set);
		return $rv;
	}
}
