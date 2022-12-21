<?php
namespace mad\tools\query;

class MadQueryWhere {
	private $statements = [];
	private $data = [];

	function __construct() {
	}

	function data() {
		return $this->data;
	}
	function statement() {
		if( empty( $this->statements ) ) {
			return '';
		}
		return 'where ' . implode(' and ', $this->statements);
	}
	function add( $statement ) {
		if( is_string( $statement ) ) {
			$this->statements[] = $statement;
			return $this;
		}
		if(! (is_object($statement) || is_array($statement)) ) {
			return $this;
		}
		foreach( $statement as $key => $value ) {
			if( is_numeric($key) ) {
				$this->statements[] = $value;
			} else {
				$this->statements[] = "`$key`=:$key";
				$this->data[$key] = $value;
			}
		}
		return $this;
	}
	function __toString() {
		return $this->statement();
	}
}
