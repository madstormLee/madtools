<?php
namespace mad\tools;

class MadPagination extends MadView {
	function __construct($file = '') {
		parent::__construct($file);
		$this->total = 0;
	}

	function __toString() {
		if( ! is_file($file) ) {
			$file = __dir__ . '/pagination.html';
		}
		$get = $_GET;
		unset($get['page']);
		$this->queryString = http_build_query($get);

		if ( $this->total == 0 ) {
			return '';
		}
		$this->rows = $this->limit->rows;
		$this->pages = $this->limit->pages;
		$this->current = $this->limit->page;

		$this->last = ceil( $this->total / $this->rows );
		if ( $this->current > $this->last ) {
			$this->current = $this->last;
		}

		$end = ceil( $this->current / $this->pages ) * $this->pages;
		$start = $end - $this->pages + 1;

		if($end > $this->last) {
			$end = $this->last;
		}
		$this->list = range( $start, $end );

		if( $this->current > $this->pages ) {
			$this->prev = $start - 1;
		}
		if( $end < $this->last ) {
			$this->next = $start + $this->pages;
		}

		$this->pagination = $this;

		return $this;
	}
}
