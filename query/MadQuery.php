<?php
namespace mad\tools\query;

class MadQuery implements ArrayAccess, IteratorAggregate, Countable {
	protected $db;
	protected $params = [];

	protected $command = 'select';
	protected $fields = '*';
	protected $table;
	protected $where;
	protected $limit;
	protected $group;
	protected $order;
	protected $page;

	protected $set;

	protected $list;
	protected $total;

	function __construct( $table = '' ) {
		$this->from( $table );
		$this->params = new MadData();
		$this->where = new MadQueryWhere();
		$this->order = new MadQueryOrder();
		$this->group = new MadQueryGroup();
		$this->limit = new MadQueryLimit();
		$this->set = new MadQuerySet();
	}
	function getCommand() {
		return $this->command;
	}
	/*************************** setters *************************/
	/******************** commands ********************/
	function select( $fields='*' ) {
		$this->command = 'select';
		$this->fields = $fields;
		return $this;
	}
	function insert( $set = [] ) {
		$this->command = 'insert';
		if( ! empty( $set ) ) {
			$this->set->assign( $set );
		}
		return $this;
	}
	function replace( $set = [] ) {
		$this->command = 'replace';
		if( ! empty( $set ) ) {
			$this->set($set);
		}
		return $this;
	}
	function update( $set ) {
		$this->command = 'update';
		$this->set->assign( $set );
		return $this;
	}
	function delete( $where ) {
		$this->command = 'delete';
		$this->where($where);
		return $this;
	}

	function set($set) {
		$this->set->setData($set);
		return $this;
	}
	function params() {
		return $this->params;
	}
	function result() {
		return $this->exec();
	}
	function from( $table ) {
		$this->table = $table;
		return $this;
	}
	function into( $table ) {
		return $this->from( $table );
	}
	function innerJoin( $table ) {
		$this->table[] = array(
			'inner join' => $table
		);
		return $this;
	}
	function leftJoin( $table ) {
		$this->table[] = array(
			'left join' => $table
		);
		return $this;
	}
	function where( $where = '' ) {
		if( ! $where ) {
			return $this->where;
		}
		$this->where->add( $where );
		return $this;
	}
	function getWhere() {
		if ( empty( $this->where ) ) {
			return '';
		}
		$where = [];
		foreach( $where as $value ) {
			list( $field, $value ) = explode( '=', $value );
			$where[] = $this->escape( $value );
		}
		return 'where ' . implode( ' and ', $this->where );
	}
	function orderBy( $field ) {
		$this->order->add( $field );
		return $this;
	}
	function groupBy( $field ) {
		$this->group->add( $field );
		return $this;
	}
	function page($value) {
		$value < 1 && $value = 1;
		$this->limit->page($value);
		return $this;
	}
	function limit($limit) {
		$this->limit->rows( $limit );
		return $this;
	}
	/*************************** db involed ***************************/
 	function query() {
		return $this->db()->list( $this->getQuery(), $this->set->data() );
 	}

	function execute() {
		return $this->exec();
	}

 	function exec() {
 		$query = $this->getQuery();
		$db = $this->db();
		$db->execute($query, $this->params);
		return ( $this->command == 'insert' ) ? $db->lastInsertId() : $db->rows();
 	}

	function row() {
		return $this->db()->row($this->getQuery(), $this->params);
	}

	function fetch() {
		return $this->row();
	}

	function list() {
		if( ! is_null($this->list) ) {
			return $this->list;
		}
		$query = $this->getQuery();
		$this->list = $this->db()->list( $query, $this->params );

		return $this->list;
	}

	function total() {
		if( is_null( $this->total ) ) {
			$this->total = $this->db()->single( $this->getCountQuery(), $this->params );
		}
		return $this->total;
	}
	/************************* getter/setter **************************/
	function db() {
		if ( is_null( $this->db ) ) {
			$this->setDb();
		}
		return $this->db;
	}
	function setDb( PDO $db=null ) {
		if ( is_null($db) ) {
			$db = MadDb::create();
		}
		$this->db = $db;
		return $this;
	}
	function getModel() {
		if ( is_null( $this->model ) ) {
			$this->setModel();
		}
		return $this->model;
	}
	function setModel(MadData $model=null) {
		if ( is_null( $this->model ) ) {
			$model = new MadData;
		}
		$this->model = $model;
		return $this;
	}
	/************************** queries ************************/
	function getQuery() {
		if( $this->command == 'select' ) {
			$this->params->assign( $this->where->data() );
			$where = $this->where->statement();
			return "select $this->fields from `$this->table` $where $this->group $this->order $this->limit";
		} else if( $this->command == 'insert' ) {
			$query = "insert into `$this->table` " . $this->set->insert();
			$this->params->assign( $this->set->getData() );
			return $query;
		} else if( $this->command == 'replace' ) {
			$query = "replace into `$this->table` $this->set";
			$this->params->assign( $this->set->getData() );
			return $query;
		} else if( $this->command == 'update' ) {
			$this->params->assign( $this->set->getData() );

			$where = $this->where->statement();
			if( empty( trim($where) ) ) {
				throw new Exception('update문에는 where절이 반드시 필요합니다.');
			}
			$this->params->assign( $this->where->data() );
			return "update `$this->table` $this->set $where";
		} else if( $this->command == 'delete' ) {
			$this->params->assign( $this->where->data() );
			$where = $this->where->statement();
			if( empty( trim($where) ) ) {
				throw new Exception('delete문에는 where절이 반드시 필요합니다.');
			}
			return "delete from `$this->table` $where";
		}
		return "$this->command";
	}
	function getCountQuery() {
		$this->params->assign( $this->where->data() );
		$where = $this->where->statement();
		return "select count(*) from `$this->table` $where $this->group";
	}

	function pretty() {
		$query = $this->getQuery();
		foreach( $this->params as $key => $value ) {
			$query = preg_replace("/:\b$key\b/", "'$value'", $query);
			$query = preg_replace("/:$key/", "'$value'", $query);
		}
		return $query;
	}
	/******************* implementaion *****************/
	function getIterator(): \Traversable {
		if ( empty( $this->statement ) ) {
			$this->query();
			$this->statement = $this->db()->getStatement();
			$this->statement->setFetchMode( PDO::FETCH_CLASS, $this->model );
		}
		return new ArrayIterator( $this->statement->fetchAll() );
	}
	function count() {
		return count( $this->data );
	}
	/******************* getter/setter *****************/
	function data() {
		return $this->data;
	}
	function setData( $data ) {
		$this->data = $data;
		return $this;
	}
	/****************** fetches *****************/
	function insertId() {
		return $this->query()->getInsertId();
	}
	function rows() {
		return $this->query()->getRows();
	}
	/*************************** test ***************************/
	function isEmpty() {
		return empty( $this->data );
	}
	function getDriver() {
		return $this->db()->getAttribute(PDO::ATTR_DRIVER_NAME);
	}
	function isTable(){
		$driver = $this->getDriver();
		if ( $driver == 'sqlite' ) {
			$query = "select name from sqlite_master where type='table' AND name='$this->table'";
		} elseif ( $driver == 'mysql' ) {
			$query = "show tables like '$this->table'";
		}
		$result = $this->db()->query( $query )->fetch();
		return ! empty( $result );
	}
	/*************************** utilities ***************************/
	function getFields() {
		$set = $this->data;
		unset( $set[$this->pKey] );

		$keys = array_keys( $set );
		return implode( ',', $this->db()->escapeFields( $keys ) );
	}
	function getPlaceholders() {
		$rv = [];
		foreach( $this->data as $key => $value ) {
			$rv[] = ':' . $key;
		}
		return implode(',', $rv );
	}
	function getSet() {
		$rv = [];
		foreach( $this->data as $key => $value ) {
			if ( $key == $this->pKey ) {
				$this->where( "id=$value" );
			}
			$rv[] = "$key=:$key";
		}
		return implode(', ', $rv );
	}
	function searchTotal() {
		return $this->total( $this->getWhere() );
	}
	function getDefaults() {
		$data = new MadData( $this->explain() );
		return $data->dic( 'Field', 'Default' );
	}

	// @Override of ArrayAccess
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->params[] = $value;
		} else {
			$this->params[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return isset($this->params[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->params[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->params[$offset]) ? $this->params[$offset] : null;
	}

	// @Override getter/setter
	public function __get($key) {
		return $this->params->$key;
	}
	public function __set($key, $value) {
		return $this->params->$key = $value;
	}
	function __toString() {
		return $this->pretty();
	}
}
