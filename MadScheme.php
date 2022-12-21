<?php
namespace mad\tools;

class MadScheme {
	function __construct($model) {
		$this->model = $model;
	}

	function setDriver($driver) {
		$this->driver = $driver === 'sqlite' ? 'sqlite' : 'mysql';
	}

	function mysqlCreate() {
		$rv = [];
		$rv[] = "create table {$this->model->name} (";
		$rv[] = $this->fieldsAndKeys();
		$rv[] = ") default charset={$this->model->charset} comment='{$this->model->comment}'";
		return implode("\n", preg_replace("/[ ]+/", ' ', $rv));
	}

	function sqliteCreate() {
		$rv = [];
		$rv[] = "create table {$this->model->name} (";
		$rv[] = $this->fieldsAndKeysSqlite() . ');';
		return implode("\n", preg_replace("/[ ]+/", ' ', $rv));
	}

	function __toString() {
		if( $this->driver === 'sqlite') {
			return $this->sqliteCreate();
		} else {
			return $this->mysqlCreate();
		}
	}

	function fieldsAndKeys() {
		$rv = [];
		foreach($this->model->fields as $row) {
			$rv[] = $this->field($row);
		}

		foreach($this->model->key as $type => $field) {
			$rv[] = "$type key($field)";
		}

		return implode(",\n", $rv);
	}
	
	function fieldsAndKeysSqlite() {
		$rv = [];
		foreach($this->model->fields as $row) {
			$rv[] = $this->fieldSqlite($row);
		}
		return implode(",\n", $rv);
	}

	function field($row) {
		$rv = [];
		$rv[] = "`$row->name`";
		$rv[] = $this->convertType($row->type, $row->max);
		$rv[] = $row->null ? 'null' : 'not null';
		$rv[] =	$row->default ? "default $row->default" : '';
		$rv[] = $row->extra;
		$rv[] =	"comment '$row->comment'";
		$rv = implode(" ", $rv);
		return $rv;
	}

	function fieldSqlite($row) {
		$rv = [];
		$rv[] = "`$row->name`";
		$rv[] = $this->convertType($row->type, $row->max);
		if ($row->name == 'id')  $rv[] = 'PRIMARY KEY AUTOINCREMENT';
		if ($row->type == 'datetime') $rv[] = "default $row->default";
		$rv = implode(" ", $rv);
		return $rv;
	}

	private function convertType( $type, $length ) {
		switch($type) {
			case 'int':
				return 'integer'; break;
			case 'str':
				return $length >= 1000 ? 'text' : "varchar($length)"; break;
			case 'datetime':
				return $this->driver !== 'sqlite' ? $type : 'timestamp';
				break;
			default:
				return $type; break;
		}
	}

	function saveScheme( $path = '', $fileName = 'create.sql') {
		$config = MadConfig::getInstance();
		$this->setDriver($config->db->default->driver);
		return file_put_contents($path.$fileName, $this);
	}

}
