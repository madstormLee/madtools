<?php
namespace mad\tools;

class MadTree implements IteratorAggregate {
	private $data;
	private $tree;
	private $parentid = 'parentId';

	public function __construct( $data, $relNo = 0, $depth = 0 ) {
		$data = new MadData($data);
		$data->index('id');
		$this->data = $data;
		$this->makeTree();
	}
	function makeTree() {
		foreach( $this->data as &$row ) {
			if ( $row->parentId ) {
				$parent = &$this->data->{$row->parentId};
				if ( ! $parent->subs ) {
					$parent->subs = new MadData;
				}
				$parent->subs->{$row->id} = $row;
			}
		}
		foreach( $this->data as &$row ) {
			if ( $row->parentId ) {
				unset( $this->data->{$row->id} );
			}
		}
	}
	public function getSub( $relNo ) {
		$tuple = $this->data;
		return $this->makeTree($tuple, $relNo);
	}
	function get( $key ) {
		if ( isset( $this->data[$key] ) ) {
			return $this->data[$key];
		}
		return false;
	}
	function getRelNo( $no ) {
		if ( isset($this->data[$no]) ) {
			return $this->data[$no]['relNo'];
		} 
		return 0;
	}
	function getSubs( $no ) {
		if ( $this->data->$no->subs > 0 ) {
			return $this->data->$no->subTree; 
		}
		return array();
	}
	function hasSub( $no ) {
		if ( $this->data[$no]['subs'] > 0 ) {
			return true;
		}
		return false;
	}
	function getDepth( $no ) {
		return $this->data[$no]['depth'];
	}
	function getLineColumn( $no, $column ) {
		$rv = array();
		if ( ! $this->data->$no ) {
			return $rv;
		}
		$rv[] = $this->data->$no->$column;
		while( $no = $this->data->$no->relNo ) {
			$rv[] = $this->data->$no->$column;
		}
		$rv = array_reverse($rv);
		return $rv;
	}
	function getLine( $no ) {
		$rv = array();
		$rv[] = $this->data->$no;
		while( $no = $this->data->$no->relNo ) {
			$rv[] = $this->data->$no;
		}
		$rv = array_reverse($rv);
		return $rv;
	}
	function getTree() {
		return $this->tree;
	}
	function getSource() {
		return $this->data;
	}
	function getIterator(): \Traversable {
		return $this->data;
	}
	function __set( $key, $value ) {
		$this->data[$key] = $value;
	}
	function __get( $key ) {
		if ( isset( $this->data[$key] ) ) {
			return $this->data[$key];
		}
	}
}
