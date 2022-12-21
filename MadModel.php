<?php
namespace mad\tools;

use mad\tools\query\MadQuery;

class MadModel {
	function __construct($table) {
		$this->table = $table;
	}
	function isInstall() {

		$query = new MadQuery( $this->getName() );
		return $query->isTable();
	}
	function config($file) {
		if(preg_match("/\.mad$/", $file)) {
			$data = file_get_contents($file);
		} else {
			$data = $file;
		}

		// data validate and add id, cearted, updated automatically.
		if(! preg_match("/^id/", $data) ) {
			$data = "id: int auto\n" . $data;
		}

		if(! preg_match("/(create|regist)[a-zA-Z0-9]*:.* auto/", $data) ) {
			$data .= "\ncreated: datetime auto";
		}

		if(! preg_match("/auto auto/", $data) ) {
			$data .= "\nupdated: datetime auto auto";
		}

		$model = [
			"name" => $this->table,
			"fields" => array_map(fn($row) => $this->parse($row), array_values(array_filter(explode("\n", $data), 'trim'))),
			"key" => [ "primary" => "id" ],
			"charset" => "utf8mb4",
			"comment" => $table,
		];

		return $model;
	}

	private function parse($row) {
		list($name, $data) = array_filter(explode(':', $row), 'trim');
		$rv = (object) [
			'name' => trim($name),
			'type' => 'str',
			'min' => 0,
			'max' => 500,
			'null' => false,
			'extra' => '',
			'default' => '',
			'comment' => trim($name),
		];

		$data = array_values(array_filter(explode(' ', $data), 'trim'));

		if( is_numeric($data[0]) ) {
			$rv->type = 'str';
			$rv->max = $data[0];
		} else {
			$rv->type = $data[0];
		}

		if(in_array('null', $data)) {
			$rv->null = true;
		} else {
			if( $rv->type == 'str' ) {
				$rv->default = '';
			} else if( $rv->type == 'int' ) {
				$rv->default = 0;
			}
		}
		if(preg_match("/\d+/", $row, $match)) {
			$rv->max = $match[0];
		} else {
			if( $rv->type == 'int') {
				$rv->max = 10;
			}
		}

		if(strpos($row, 'auto auto') > 0) {
			$rv->extra = 'on update current_timestamp';
		}
<<<<<<< HEAD
=======

>>>>>>> 911542ed501e7300cc1d945dec4c3b775871f44d
		if(strpos($row, 'auto') > 0) {
			if(strpos($rv->type, 'date') === 0){
				$rv->default = 'current_timestamp';
			}
<<<<<<< HEAD
			if(strpos($rv->type, 'int')){
=======
			if($rv->type === 'int') {
>>>>>>> 911542ed501e7300cc1d945dec4c3b775871f44d
				$rv->extra = 'auto_increment';
			}
		}
		return $rv;
	}
}
