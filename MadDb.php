<?php
namespace mad\tools;

class MadDb {
	private static $db = [];

	protected $conn = null;

	protected $fetchmode = PDO::FETCH_OBJ;

	protected $statement = null;
	protected $result = false;

	protected $query = "";
	protected $params = [];

	// factory method
	public static function create( $name = 'default' ) {
		if( ! isset(self::$db[$name]) ) {
			self::$db[$name] = self::createDb($name);
		}
		return self::$db[$name];
	}

	public static function createDb($name) {
		$config = MadConfig::getInstance();
		if(! isset($config->db)) {
			throw new \Exception('DB info not found!');
		}
		$info = $config->db->$name;

		return new self($info);
	}

	public static function sqlite($info) {
		$dsn = "sqlite:" . $info->dbname;
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_EMULATE_PREPARES => false,
		];
		return new PDO($dsn, '', '', $options);
	}

	public static function mysql($info) {
		$dsn = "mysql:host=$info->host;dbname=$info->dbname;charset=$info->charset";

		$options = [
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		];
		
		return new PDO($dsn, $info->username, $info->password, $options);
	}
		
	public function __construct($info) {
		if(empty($info->driver)) {
			$info->driver = 'mysql';
		}
		if(! in_array($info->driver, ['mysql', 'sqlite'])) {
			throw new \Exception("Driver not supported: $info->driver", 500);
		}
		$driver = $info->driver;
		$this->conn = self::$driver($info);
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
		return $this->q($query, $params)->rows();
	}

	public function list($query='', $params = null) {
		$this->q($query, $params);
		return $this->statement->fetchAll($this->fetchmode);
	}

	public function row($query, $params = null){				
		$this->q($query, $params);
		return $this->statement->fetch($this->fetchmode);			
	}
	
	public function single($query,$params = null){
		$this->q($query,$params);
		return $this->statement->fetchColumn();
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

	private function prettyQuery() {
		$keys = [];
		$values = [];
		foreach( $params as $key => $value ) {
			$keys[] = ":$key";
			$values[] = "'$value'";
		}
		return str_replace( $keys, $values, $this->query );
	}
}
