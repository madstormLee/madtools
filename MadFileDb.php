<?php
namespace mad\tools;

class MadFileDb extends MadFile {
	function __construct( $file = '' ) {
		parent::__construct( $file );
		$this->data = file( $this->file );
	}
	function getData() {
		$rv = array();
		foreach( $this->data as $key => $row ) {
			$row = trim($row);
			if ( $key == 0 || empty( $row ) ) continue;
			$model = unserialize($row);
			$model->id = $key;
			$rv[$key] = $model;
		}
		return $rv;
	}
	function insert( $data ) {
		return file_put_contents( $this->file, "\n".serialize( $data ), FILE_APPEND|LOCK_EX );
	}
	function delete( $id='' ) {
		unset( $this->data[$id] );
		return file_put_contents( $this->file, implode("\n", $this->data), LOCK_EX );
	}
	function getIterator(): \Traversable {
		return new ArrayIterator($this->getData());
	}
}
