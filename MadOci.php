<?php
namespace mad\tools;

class MadOci extends MadDb {
	private $conn = null;

	private $statement;
	public  $result;

	private $id = 0;

	private $query = "";
	private $params = [];

	public function __construct($conn) {
		$this->conn = $conn;
	}

	public function isConnect() {
		return !! $this->conn;
	}

	public function q($query='', $params = null) {
		$this->query = trim($query);
		$this->params = $params;

		if( empty( $this->query ) ) {
			return $this;
		}

		$this->statement = oci_parse($this->conn, $this->query);

		if(is_array($params) || is_a( $params, 'IteratorAggregate' )) {
			foreach($this->params as $key => $value) {
				if((!is_numeric($value)) && empty($value)) {
					$value = NULL;
				}
				if( $key == 'rnum' ) {
					oci_bind_by_name($this->statement, ":$key", $this->params[$key], -1, SQLT_INT);
				} else {
					oci_bind_by_name($this->statement, ":$key", $this->params[$key] );
				}
			}
		}

		$this->result = oci_execute($this->statement);		

		if(! $this->result ) {
			$e = oci_error();
			$message = $e['message']. "\nquery: " . $this->prettyQuery() . ",\nparams: " . print_r($this->params, true);
			throw new \Exception( $message );
		}
		return $this;
	}

	public function executeRaw($query) {
		return oci_execute(oci_parse($this->conn, $query));
	}

	public function execute($query, $params = null) {
		$this->query = $query;
		$this->params = $params;

		$this->statement = oci_parse($this->conn, $query);

		$this->bindByName( $params );
		$rv = oci_execute($this->statement);
		if (! $rv) {
			$e = oci_error($this->statement);  // For oci_execute errors pass the statement handle
			print htmlentities($e['message']);
			print "\n<pre>\n";
			print htmlentities($e['sqltext']);
			printf("\n%".($e['offset']+1)."s", "^");
			print  "\n</pre>\n";
			die;
		}
		return $rv;
	}

	private function bindByName($params) {
		foreach($params as $key => &$value) {
			if((!is_numeric($value)) && empty($value)) {
				$value = NULL;
			}
			if( $key == 'rnum' ) {
				oci_bind_by_name($this->statement, ":$key", $value, -1, SQLT_INT);
			} else {
				oci_bind_by_name($this->statement, ":$key", $value );
			}
		}
	}

	public function insertList($query, $list) {
		$this->query = $query;
		$this->statement = oci_parse($this->conn, $query);

		$rv = 0;
		foreach( $list as $row ) {
			$this->params = $row;
			$this->bindByName( $row );
			$result = oci_execute($this->statement);
		}
		return $rv;
	}

	public function list($query='', $params = null) {
		if(! empty($query) ) $this->q($query, $params);
		$rv = [];
		while( $row = oci_fetch_object($this->statement) ) {
			$rv[] = $row;
		}
		return $rv;
	}

	public function rows() {
		return oci_num_rows($this->statement);	
	}

	public function query($query='', $params = null) {
		return $this->list($query, $params);
	}

	public function lastInsertId() {
		return $this->id;
	}	

	public function row($query='', $params = null){				
		$this->q($query, $params);
		return oci_fetch_object($this->statement);
	}

	public function single($query, $params = null){
		$this->q($query, $params);
		$array = oci_fetch_row($this->statement); 

		return empty( $array ) ? false : $array[0];
	}

	private function prettyParams( $params ) {
		$rv = [];
		foreach( $params as $key => $value ) {
			$rv[":$key"] = "'$value'";
		}
		return $rv;
	}
	private function prettyQuery() {
		$params = $this->prettyParams($this->params);
		return str_replace( array_keys($params), array_values($params), $this->query );
	}
}
