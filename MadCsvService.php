<?php
namespace mad\tools;

class MadCsvService {
	protected $file = '';
	protected $headers = ['idx','uid','address','type','registDate','updateDate'];
	protected $model = null;

	function __construct($name) {
		$this->file = $name . '.csv';
		$this->initModel("$name.json");
	}
	function initModel($file) {
		if( is_file($file) ) {
			$this->model = new MadJson($file);
		}
		return $this;
	}
	function file($file = '') {
		if( empty( $file ) ) {
			return $this->file;
		}
		$this->file = $file;
		return $this;
	}
	function headers($headers = []) {
		if( empty( $headers ) ) {
			return array_keys($this->model);
		}
		$this->headers = $headers;
		return $this;
	}
	function install() {
		if( ! is_writable($this->file) ) {
			throw new \Exception('저장 공간이 준비되지 않았습니다.');
		}
		file_put_contents($this->file, '');
		return $this->appendCsv($this->headers());
	}
	function appendCsv($row) {
		$fp = fopen($this->file, 'a+');
		$rv = fputcsv($fp, $row);
		fclose($fp);
		return $rv;
	}
	function fetch( $idx ) {
		$list = $this->listAll();
		$list[$idx];
	}
	function listAll() {
		return array_map('str_getcsv', file($this->file));
	}
	function dumpIn($list) {
		$this->install();
		$fp = fopen($this->file, 'a+');
		$rv = 0;
		foreach( $list as $row ) {
			$rv += fputcsv($fp, $row);
		}
		fclose($fp);
		return $rv;
	}
	function index() {
		return new MadCsvIndex($this->file);
	}
	function save($row) {
		if( is_array( $row ) ) {
			$row = (object) $row;
		}
		if( $row->idx > 0) {
			return $this->update( $row, $row->idx );
		}
		return $this->insert( $row );
	}
	function fetchDefault() {
		return array_combine($this->headers(), array_fill(0, count($this->headers()), ''));
	}
	function insert($row) {
		$list = $this->listAll();
		$row->idx = count($list);
		$row->registDate = date('Y-m-d H:i:s');
		$row->updateDate = date('Y-m-d H:i:s');

		$rowData = array_merge($this->fetchDefault(), $row->getData());

		$fp = fopen($this->file, 'a+');
		$rv = fputcsv($fp, $rowData);
		fclose($fp);
		return $rv;
	}
	function update($row, $idx) {
		unset( $row->idx );
		$row->updateDate = date('Y-m-d H:i:s');

		$originalRow = $list[$idx];
		$rowData = array_merge($originalRow, $row->getData());

		$list = $this->listAll();
		$list[$idx] = $rowData;
		return $this->dumpIn($list);
	}
	function delete($idx) {
		$list = file($this->file);
		if( ! isset( $list[$idx] ) ) {
			throw new \Exception('존재하지 않는 행입니다.');
		}
		unset($list[$idx]);
		return $this->dumpIn($list);
	}
	function download($fileName, $list) {
		header('Content-Type: text/csv');
		header("Content-Disposition: attachment; filename=\"$filename\"");

		$fp = fopen('php://output', 'wb');
		foreach ( $list as $row ) {
			fputcsv($fp, $row);
		}
		fclose($fp);
	}
}
