<?php
namespace mad\tools; 

class MadJson implements \IteratorAggregate {
	protected $data = null;
	protected $file = '';
	protected $encodeOption = JSON_UNESCAPED_UNICODE;
	protected $decodeOption = JSON_UNESCAPED_UNICODE;

	function __construct( $file = '' ) {
		$this->data = (object)[];
		$this->load( $file );
	}

	public static function fromFile($file) {
		return new self($file);
	}

	function isFile() {
		return is_file( $this->getFile() );
	}

	function getFile() {
		return $this->file;
	}

	function getFileInfo() {
		if ( ! is_file( $this->file ) ) {
			throw new Exception('File Not Found');
		}
		return new SplFileInfo( $this->file );
	}

	function setFile( $file ) {
		$this->file = $file;
		return $this;
	}

	function load( $file = '', $data = [] ) {
		$this->setFile( $file );
		if ( ! is_file ( $this->file ) ) {
			return $this;
		}
		$this->setJson( file_get_contents( $this->file ) );
		return $this;
	}

	function encode() {
		return json_encode($this->data, JSON_UNESCAPED_UNICODE);
	}

	function pretty() {
		return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	}

	function decode($file = '') {
		if(empty($file) ) {
			$file = $this->file;
		}
		$json = json_decode( file_get_contents($file), false );
		if( json_last_error() ) {
			throw new Exception( baseName($file) . " 에 " .  json_last_error_msg() . ' 가 있습니다.' );
		}
		if(! empty($json)) {
			$this->assign( $json );
		}
		return $this;
	}

	function setJson( $json ) {
		$this->data = json_decode($json, null, 512, JSON_THROW_ON_ERROR);
		return $this;
	}

	function getErrorMessage( $errorNo ) {
		static $messages = [
			JSON_ERROR_NONE => ' - No errors',
			JSON_ERROR_DEPTH => ' - Maximum stack depth exceeded',
			JSON_ERROR_STATE_MISMATCH => ' - Underflow or the modes mismatch',
			JSON_ERROR_CTRL_CHAR => ' - Unexpected control character found',
			JSON_ERROR_SYNTAX => ' - Syntax error, malformed JSON',
			JSON_ERROR_UTF8 => ' - Malformed UTF-8 characters, possibly incorrectly encoded',
		];
		if (! in_array($errorNo, $messages)) {
			return ' - Unknown error';
		}
		return $messages[$errorNo];
	}

	function data() {
		return $this->data;
	}

	function getContents() {
		return $this->__toString();
	}

	function save() {
		if( empty($this->file) ) {
			throw new Exception('No file.');
		}
		if(! is_writable( $this->file ) ) {
			throw new Exception('Not writable.');
		}
		$dir = dirName( $this->file );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
		return file_put_contents($this->file, $this->pretty());
	}

	function getIterator(): \Traversable {
		return new \ArrayIterator($this->data);
	}

	function __get($key) {
		if( isset( $this->data->$key ) ) {
			return $this->data->$key;
		}
		return '';
	}

	function __set($key, $value) {
		$this->data->$key = $value;
		return $this;
	}

	function __isset($key) {
		return isset($this->data->$key);
	}

	function option($option) {
		$this->option |= $option;
	}

	function __toString() {
		if ( ! empty( $this->data ) ) {
			return $this->pretty();
		}
		return '{}';
	}
}
