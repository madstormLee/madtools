<?php
namespace mad\tools;

class MadView {
	protected $file = 'index.html';
	protected $data = [];

	function __construct($file) {
		$this->file = $file;
		$filePath = dirname(realpath($this->file));


		if (str_starts_with($filePath, '/home/mad/www') ) {
			$replacePath = '/home/mad/www';
		} else {
			$replacePath = $_SERVER['DOCUMENT_ROOT'];
		}

		$this->dot = str_replace(realpath($replacePath), '', $filePath);
		$this->tilt = str_replace($_SERVER['DOCUMENT_ROOT'], '', ROOT);
		$this->router = MadRouter::getInstance();
		$this->config = MadConfig::getInstance();
	}

	function getContents() {
		if ( ! $this->isFile() ) {
			return getcwd() . '/' . $this->file . ' 파일이 존재하지 않습니다.';
		}
		extract( $this->data );
		ob_start();
		include $this->file;
		$rv = ob_get_clean();
		// $rv = $this->dot2cwd($rv);
		return $rv;
	}

	function dot2cwd($html) {
		return preg_replace("!(src|href)=(['\"])\./!i", "\$1=\$2$this->dot/", $html);
	}

	function tilt2pd($html) {
		return preg_replace("!(src|href)=(['\"])~/!i", "\$1=\$2$this->tilt/", $html);
	}

	function setFile($file) {
		$this->file = $file;
		return $this;
	}

	function getFile() {
		return $this->file;
	}

	function isFile() {
		return is_file($this->file);
	}

	function setData($data) {
		$this->data = $data;
		return $this;
	}

	function getData() {
		return $this->data;
	}

	function __get($key) {
		if( isset( $this->data[$key] ) ) {
			return $this->data[$key];
		}
		return '';
	}

	function __set($key, $value) {
		$this->data[$key] = $value;
	}

	function __isset($key) {
		return isset($this->data[$key]);
	}

	function __toString() {
		try {
			return $this->getContents();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}
}
