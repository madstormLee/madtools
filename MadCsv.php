<?php
namespace mad\tools;

class MadCsv extends MadFile {
	protected $file = ''; 
	protected $delimiter = ','; 
	protected $columns = []; 

	function __construct( $file = '', $columns=[] ) {
		$this->load( $file, $columns );
	}
	function setColumns( $columns = [] ) {
		$this->columns = $columns;
		return $this;
	}
	function load( $file = '', $columns = [] ) {
		if ( ! empty( $file ) ) {
			$this->setFile( $file );
		}
		if ( ! $this->isFile () ) {
			return $this;
		}
		if ( ! empty( $columns ) ) {
			$this->setColumns( $columns );
		}
		if (($handle = fopen( $this->file , "r")) === FALSE) {
			return $this;
		}

		if ( ! empty($this->columns) ) {
			while (($row = fgetcsv($handle, 1000, $this->delimiter)) !== FALSE) {
				$this->data[] = array_combine( $this->columns, $row );
			}
		} else {
			while (($row = fgetcsv($handle, 1000, $this->delimiter)) !== FALSE) {
				$this->data[] = $row;
			}
		}
		fclose($handle);
		return $this;
	}
	function load2($file, $headerNames=[]) {
		$header = [];
		$list = [];
		foreach( file($file) as $key => $row  ) {
			$row = str_getcsv($row);
			if( $key == 0 ) {
				if( ! empty($headerNames) ) {
					$header = $headerNames;
				} else {
					$header = $row;
				}
				$header = array_filter( $header );
				$headerLength = count($header);
				continue;
			}
			$row = array_slice( $row, 0, $headerLength );
			$row = array_combine( $header, $row );
			$list[] = (object) $row;
		}
		return $list;
	}
}
