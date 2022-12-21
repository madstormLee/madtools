<?php
namespace mad\tools\query;

class MadQueryLimit {
	private $startPage = 0;
	private $pages = 10;
	private $rows = 10;

	private $page = 1;

	function __construct() {
		$this->page = empty($_GET['page']) ? 1 : $_GET['page'];
		$this->rows = empty($_GET['rows']) ? 10 :$_GET['rows'];
	}
	function rows($value) {
		$this->rows = $value;
		return $this;
	}
	function page($value) {
		$this->page = $value;
		return $this;
	}
	function pages($value) {
		$this->pages = $value;
		return $this;
	}
	function top() {
		$total = $this->index->total();
		if( $this->startPage ) {
			return $total - $this->startPage;
		}
		return $total;
	}
	function getTotalPage() {
		$totalPage = floor($this->startPage / ($this->rows * $pages));
		return $totalPage;
	}
	function getTotal() {
		return floor($this->startPage / ($this->rows * $this->pages));
	}
	function __get($key) {
		if( isset($this->$key) ) {
			return $this->$key;
		}
		return '';
	}
	function __set($key, $value) {
		$this->$key = $value;
	}

	function getOffset() {
		return ($this->page - 1) * $this->rows;
	}
	function __toString() {
		if( $this->rows == 0 ) {
			return '';
		}
		$offset = $this->getOffset();
		return "limit $this->rows offset $offset";
	}
}
