<?php
namespace mad\tools\query;

class MadQueryGroup {
	private $statements = [];

	function add( $string ) {
		$this->statements[] = $string;
	}
	function statement() {
	}
	function __toString() {
		if( empty( $this->statements ) ) {
			return '';
		}
		return 'group by ' . implode(', ', $this->statements);
	}
}
