<?php
namespace mad\tools;

class MadTest implements IteratorAggregate {
	private $tested = false;
	private $method = '';
	public $tests = null;
	private $data = [
		'success' => [],
		'fail' => []
	];

	function __construct() {
		$this->tests = preg_grep("/^test[A-Z]/", get_class_methods($this));
	}

	protected function is($brief, $result) {
		$target = $result ? 'success' : 'fail';
		$cursor = &$this->data[$target];
		if(! isset($cursor[$this->method])) {
			$cursor[$this->method] = [];
		}
		$cursor[$this->method][] = $brief;
	}

	function test() {
		foreach( $this->tests as $method ) {
			$this->method = $method;
			$this->$method();
		}
		$this->tested = true;
	}

	function getIterator(): \Traversable {
		return new ArrayIterator( $this->data );
	}

	function count() {
		return count( $this->tests );
	}

	function getTests() {
		return $this->tests;
	}

	function __get($key) {
		if(isset( $this->data[$key] )) {
			return $this->data[$key];
		}
		return [];
	}

	function __toString() {
		if( ! $this->tested ) {
			$this->test();
		}
		return json_encode($this->data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
	}
}
