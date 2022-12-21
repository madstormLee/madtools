<?php
namespace mad\tools;

class MadCsvIndex {
	protected $file = '';
	protected $list = [];
	protected $headers = [];

	function __construct($file = '') {
		$this->file = $file;
	}
	function list() {
		if( empty( $this->list ) ) {
			$list = array_map('str_getcsv', file($this->file));
			$headers = array_shift($list);

			$this->headers = $headers;
			$this->list = array_map(function($row) use( $headers )  {
				return (object) array_combine($headers, $row);
			}, $list);
		}
		return $this->list;
	}
	function total() {
		return count( $this->list() );
	}
	function isEmpty() {
		return $this->total() == 0;
	}
}
