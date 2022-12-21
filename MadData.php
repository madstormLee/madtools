<?php
namespace mad\tools;

class MadData implements IteratorAggregate, ArrayAccess, Countable {
	private $data = [];

	function __construct( $data = [] ) {
		$this->setData( $data );
	}
	function fetch( $id ) {
		return $this->get( $id );
	}
	function index($key='id') {
		$rv = [];
		foreach( $this->data as &$row ) {
			$rv[$row->$key] = $row;
		}
		return $rv;
	}
	function get( $key ) {
		if ( ! isset( $this->data[$key] ) ) {
			return false;
		}
		return $this->data[$key];
	}
	function set( $key, $value ) {
		if ( is_array( $value ) ) {
			$this->data[$key] = new MadData($value);
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}
	function remove( $key ) {
		$this->offsetUnset( $key );
		return $this;
	}
	function add( $value ) {
		return $this->push( $value );
	}
	public function append($value) {
		$this->data[] = $value;
		return $this;
	}
	function data($data = null) {
		return ( null === $data ) ? $this->getData() : $this->setData( $data );
	}
	function getData() {
		return $this->data;
	}
	function setData( $data = null ) {
		$this->data = [];
		return $this->addData( $data );
	}
	function addData( $data = [] ) {
		foreach( $data as $key => $value ) {
			$this->set($key,$value);
		}
		return $this;
	}
	function clear() {
		$this->data = [];
		return $this;
	}
	function in( $value ) {
		return in_array( $value, $this->data );
	}
	function exists( $key ) {
		return array_key_exists( $key, $this->data );
	}
	/*********************** array_somethings *************************/
	function push( $value ) {
		array_push( $this->data, $value );
		return $this;
	}
	function pop() {
		return array_pop( $this->data );
	}
	function shift() {
		return array_shift( $this->data );
	}
	function unshift( $value ) {
		array_unshift( $this->data, $value );
		return $this;
	}
	function unique() {
		$this->data = array_unique( $this->data );
		return $this;
	}
	function sum() {
		return array_sum( $this->data );
	}
	function search( $value ) {
		return array_search( $value, $this->data );
	}
	/*********************** sorts *************************/
	function merge( $data ) {
		if ( is_array( $data ) ) {
			$this->data = array_merge( $this->data, $data );
		} elseif ( is_object( $data ) && $data instanceof MadData ) {
			$this->data = array_merge( $this->data, $data->getData() );
		}
		return $this;
	}
	function intersect( $data ) {
		$this->data = array_intersect( $this->data, $data );
		return $this;
	}
	function sort() {
		sort( $this->data );
		return $this;
	}
	function ksort() {
		ksort( $this->data  );
		return $this;
	}
	function natsort() {
		natsort( $this->data );
		return $this;
	}
	function kNatsort() {
		$rv = [];
		$keys = array_keys( $this->data );
		natsort( $keys );
		foreach( $keys as $key ) {
			$rv[$key] = $this->data[$key];
		}
		$this->data = $rv;
		return $this;
	}
	function filter( $callback = '' ) {
		if ( $callback ) {
			$this->data = array_filter( $this->data, $callback );
		} else {
			$this->data = array_filter( $this->data );
		}
		return $this;
	}
	function getKeys() {
		return new MadData( $this->keys() );
	}
	function getValues() {
		return new MadData( $this->values() );
	}
	function keys() {
		return array_keys($this->data);
	}
	function values() {
		return array_values($this->data);
	}
	function getArrayValues() {
		return array_values($this->data);
	}
	/******************** utils ********************/
	function whereStatement() {
		if( empty( $this->data ) ) {
			return '';
		}
		$quoted = [];
		foreach( $this->data as $key => $value ) {
			$quoted[] = "$key='$value'";
		}
		return "where " . implode(' and ', $quoted);
	}

	function except( $exceptions ) {
		if ( empty( $this->data ) ) {
			return '';
		}
		if ( ! is_array( $exceptions ) ) {
			$exceptions = [ $exceptions ];
		}
		$queries = $this->data;
		foreach( $exceptions as $exception ) {
			unset( $queries[$exception] );
		}
		return http_build_query( $queries );
	}
	function replace( $replace, $sep = '&' ) {
		$queries = $this->data;

		$data = array_filter( explode( $sep, $replace ) );
		foreach( $data as $row ) {
			list( $key, $value )  = explode( '=', $row );
			$queries[$key] = $value;
		}
		return http_build_query( $queries );
	}
	function dic( $field1='', $field2 = '' ) {
		if ( empty( $field2 ) ) {
			return $this->cols( $field1 );
		}
		$rv = [];
		foreach( $this->data as $key => $row ) {
			$row = new MadData( (array) $row );
			$rv[$row[$field1]] = $row[$field2];
		}
		return new MadData( $rv );
	}
	function max() {
		return max( $this->data );
	}
	function maxKey() {
		return max( array_keys($this->data) );
	}
	function getFirsts() {
		foreach( $this->data as $key => $row ) {
			$row = new MadData( (array) $row );
			$rv[$key] = current($row);
		}
		return new MadData($rv);
	}
	function cols( $field='' ) {
		if ( empty($field) ) {
			return $this->getFirsts();
		}
		$rv = [];
		foreach( $this->data as $key => $row ) {
			$row = new MadData( (array) $row );
			$rv[$key] = $row[$field];
		}
		return new MadData($rv);
	}
	function implode( $glue = '' ) {
		return implode( $glue, $this->data );
	}
	function getReverseDictionary( $target ) {
		$rv = [];
		foreach( $this->data as $key => $row ) {
			$rv[$row->$target] = $key;
		}
		return new MadData( $rv );
	}
	function json() {
		return json_encode( $this->getArray() );
	}
	function getJson() {
		return json_encode( $this->data );
	}
	function setJson( $json ) {
		$this->data = json_decode($json);
		return $this;
	}
	function walk( $function ) {
		array_walk( $this->data, $function, $this );
		return $this;
	}
	// @Override of Countable 
	function count() {
		return count( $this->data );
	}
	// @Override - IteratorAggregate 
	function getIterator(): \Traversable {
		return new ArrayIterator($this->data);
	}
	/************* ArrayAcess implements ****************/
	public function first() {
		reset( $this->data  );
		return $this->current();
	}
	public function key() {
		return key( $this->data );
	}
	public function current() {
		return current( $this->data );
	}
	public function next() {
		return next( $this->data );
	}
	public function offsetUnset($key) {
		if ( isset( $this->data[$key] ) ) {
			unset( $this->data[$key] );
		}
	}
	public function offsetExists($key) {
		return isset( $this->$key );
	}
	public function offsetSet($key, $value) {
		$this->set( $key, $value );
	}
	public function offsetGet($key) {
		if ( ! isset( $this->data[$key] ) ) {
			return '';
		}
		return $this->data[$key];
	}
	public function end() {
		return end( $this->data );
	}
	/******************* magic methods *****************/
	function __get( $key ) {
		return $this->get( $key );
	}
	function __set( $key, $value ) {
		$this->set( $key, $value );
	}
	function __unset($key) {
		$this->offsetUnset( $key );
	}
	function has( $key ) {
		return isset( $this->data[$key] );
	}
	function __isset( $key ) {
		return $this->has( $key );
	}
	function __toString() {
		return json_encode($this->data);
	}
	public function isEmpty() {
		return empty( $this->data );
	}
}
