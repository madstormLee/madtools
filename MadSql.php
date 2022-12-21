<?php
namespace mad\tools;

class MadSql {
	private $db;

	private $query = '';

	private $if;
	private $in;

	function __construct($file, $db = null) {
		$this->db = $db;
		$this->file = $file;

		$this->if = function( $test, $result, $else = '' ) {
			if( $test ) {
				return $result;
			}
			return $else;
		};
		$this->in = function( $array ) {
			$joined = implode("', '", $array);
			return "in ('$joined')";
		};
	}

	public static function load($file, $db) {
		return new self($file, $db);
	}

	public function q($name, $data = null) {
		$this->query = $this->get($name, $data);
		return $this->db->q( $this->query, $data );
	}

	public function list($name='', $data = null) {
		return $this->q($name, $data)->list();
	}

	public function row($name, $data = null) {
		return $this->q($name, $data)->row();
	}

	public function get( $__key__, $data = null ) {
		if( is_null( $data ) ) {
			$data = [];
		}
		if( is_object( $data ) ) {
			$data = (array) $data;
		}

		extract($data);

		$if = $this->if;
		$in = $this->in;

		$queries = (object) include( $this->file );
		$this->query = $queries->$__key__;
		return $this->query;
	}

	public function pretty( $id, $data = array() ) {
		$result = $this->get($id, $data);
		foreach( $data as $key => $value ) {
			$result = str_replace(":$key", "'$value'", $result);
		}
		return $result;
	}
}
