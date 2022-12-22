<?php
namespace mad\tools;

class MadSqlite {
	protected $conn = null;

	protected $fetchmode = PDO::FETCH_OBJ;

	protected $statement = null;
	protected $result = false;

	protected $query = "";
	protected $params = [];
		
	public function __construct($info) {
	}

	public function q($query='', $params = null) {
		$this->query = trim($query);
		$this->params = $params;

		if( empty( $this->query ) ) {
			throw new \Exception('No Query', 500);
		}

		try {
			$this->statement = $this->conn->prepare($query);

			if(is_object($params) || is_array($params) || is_a($params, 'IteratorAggregate')) {
				foreach($params as $key => &$value) {
					if( strpos( $this->query, ":$key") === false ) {
						continue;
					}
					$this->statement->bindParam(":$key", $value);
				}
			}
			$this->result = $this->statement->execute();		
		} catch(\PDOException $e) {
			$message = $e->getMessage() . "\nquery: " . $this->prettyQuery() . "\nparams: " . print_r($params, true);
			throw new \Exception( $message );
		}
		return $this;
	}
	public function prepare($query) {
		$this->statement = $this->conn->prepare($query);
		return $this->statement;
	}
	public function x($query='', $params=null) {
		return $this->execute($query, $params);
	}
	public function execute($query='', $params=null) {
		if(! empty($query) ) $this->q($query, $params);
		return $this->rows();
	}

	public function list($query='', $params = null) {
		if(! empty($query) ) $this->q($query, $params);
		return $this->statement->fetchAll($this->fetchmode);
	}

	public function query($query='', $params = null) {
		return $this->list($query, $params);
	}

	public function rows() {
		return $this->statement->rowCount();	
	}

	public function lastInsertId() {
		return $this->conn->lastInsertId();
	}

	public function column($query='', $params = null) {
		if(! empty($query) ) $this->q($query, $params);

		$Columns = $this->statement->fetchAll(PDO::FETCH_NUM);		
			
		$column = null;

		foreach($Columns as $cells) {
			$column[] = $cells[0];
		}

		return $column;
	}	
	
	public function row($query, $params = null){				
		$this->q($query, $params);
		return $this->statement->fetch($this->fetchmode);			
	}
	
	public function single($query,$params = null){
		$this->q($query,$params);
		return $this->statement->fetchColumn();
	}

	private function prettyParams( $params ) {
		$rv = array();
		foreach( $params as $key => $value ) {
			$rv[":$key"] = "'$value'";
		}
		return $rv;
	}

	private function prettyQuery() {
		// $query = preg_replace('/\s+/', ' ', trim($query));
		$params = $this->prettyParams($this->params);
		return str_replace( array_keys($params), array_values($params), $this->query );
	}

	public function isConnect() {
		return !! $this->conn;
	}

	function isTable($table){
		return count($this->query( "show tables like '$table'" )) > 0;
	}
	function explain( $table ) {
		$query = "explain $table";
		$this->query( $query );
		return $this->getData();
	}
	function escape( $value ) {
		if ( is_array( $value ) ) {
			return $this->escapeAll( $value );
		}
		if ( preg_match( '/now\(\)$/', $value ) ) {
			return $value;
		}
		$value = mysql_real_escape_string( $value );
		if ( false === strpos($value , '.') ) {
			return "'$value'";
		}
		$temp = explode( '.', $value );
		$temp[0] = "'$temp[0]'";
		return implode( '.', $temp );
	}
	// @Override
	function escapeField( $value ) {
		return  "`$value`";
	}

}
