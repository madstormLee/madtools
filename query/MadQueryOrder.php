<?php
namespace mad\tools\query;

class MadQueryOrder {
	private $statements = [];

	function add( $string ) {
		$this->statements[] = $string;
	}
	function __toString() {
		if( empty( $this->statements ) ) {
			return '';
		}
		return 'order by ' . implode(', ', $this->statements);
	}
}

