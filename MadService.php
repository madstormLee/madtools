<?php
namespace mad\tools;

use mad\tools\query\MadQuery;

class MadService {
	protected $table;
	protected $key = 'id';

	protected $headers = null;

	function __construct($table = '') {
		if(! empty( $table ) ) {
			$this->table = $table;
		}
		if(empty($this->table)) {
			$this->table = str_replace('Service', '', get_class($this));
		}
	}

	function db($name = 'default') {
		return MadDb::create($name);
	}

	function query() {
		return new MadQuery($this->table);
	}

	function index() {
		return $this->query();
	}

	function isTable() {
		return $this->db()->isTable( $this->table );
	}

	function setKey($key) {
		$this->key = $key;
		return $this;
	}
	function getKey() {
		return $this->key;
	}

	function list($where = '') {
		$query = $this->query();
		if( $where ) {
			$query->where($where);
		}
		return $query->list();
	}

	function countList( $groupField, $where = [] ) {
		$query = $this->query();
		$query->select("$groupField, count(*) as cnt")->groupBy( $groupField );

		if( ! empty( $where ) ) {
			$query->where( $where );
		}
		$rv = (object) [];
		foreach( $query->list() as $row ) {
			$rv->{$row->$groupField} = $row->cnt;
		}
		return $rv;
	}
	function row($where) {
		return $this->fetchWhere($where);
	}
	function fetch($key) {
		return $this->row($key);
	}

	private function where($params) {
		return is_string($where) || is_numeric($where) ? [$this->key => $where] : $where;
	}

	function fetchWhere($params) {
		return $this->query()->where($this->where($params))->page(1)->fetch();
	}

	function save($set) {
		if( is_array($set) ) {
			$set = (object)$set;
		}
		if( empty($set->{$this->key}) ) {
			return $this->insert($set);
		}
		return $this->update($set, $set->{$this->key});
	}

	function insert($set) {
		return $this->query()->insert($set)->execute();
	}

	function update($set, $key) {
		$this->updateWhere($set, [$this->key => $key]);
		return $this->query()->update($set)->where()->execute();
	}

	function updateWhere($set, $where) {
		unset($set->{$this->key});
		return $this->query()->update($set)->where($where)->execute();
	}

	function delete($params) {
		return $this->deleteWhere($params);
	}

	function deleteWhere($where) {
		return $this->query()->delete($this->where($where))->execute();
	}

	function count($where = []) {
		$query = new MadQuery($this->table, 'mysql');
		if(! empty($where)) {
			$query->where($where);
		}
		return $query->total();
	}

	function total( $where='' ) {
		$where = $this->formatWhere( $where );
		$query = new MadQuery( $this->table );
		return $query->select('count(*)')->where( $where )->fetch()->first();
	}

	function max($field='id', $where='') {
		$where = $this->formatWhere( $where );
		$query = "select max($field) from `$this->table` $where";
		return $this->db()->query( $query )->getFirst();
	}

	function create($path) {
		if(preg_match("/\.json$/", $path)) {
			return createFromConfig($path);
		}
		if(preg_match("/\.sql$/", $path)) {
			return createFromSqlFile($path);
		}
		return $this->db()->execute($path);
	}

	function createFromConfig($path) {
			$config = new MadJson($path);
			$scheme = new MadScheme($config->model);
			return $this->db()->execute($scheme);
	}

	function createFromSqlFile($path) {
		$scheme = file_get_contents($path);
		return $this->db()->execute($scheme);
	}

	function drop() {
		return $this->db()->x("drop table `$this->table`");
	}
	function truncate() {
		return $this->db()->x("truncate `$this->table`");
	}
	function explain() {
		return $this->db()->list("explain `$this->table`");
	}
	function headers() {
		if( null === $this->headers ) {
			$query = "select
				column_name as name,
				column_default as `default`,
				data_type as `type`,
				column_key as `key`,
				character_maximum_length as `length`,
				numeric_precision as `precision`,
				`extra`,
				column_comment as `comment`
				from `information_schema`.`columns` where `table_name` = '$this->table'";
			$this->headers = $this->db()->list($query);
		}
		return $this->headers;
	}

	function getFields() {
		$config = MadConfig::getInstance();
		if(! isset($config->model)) {
			$config->model = (object) [
				'name' => $this->table,
				'fields' => $this->headers(),
			];
		}

		return array_map(fn($row) => $row->name, $config->model->fields);
	}

	function exists($where = []) {
		return $this->count($where) > 0;
	}

	function filterFields($data, $fields = []) {
		if(empty($fields)) {
			$fields = $this->getFields();
		}
		return array_filter((array) $data, fn($key) => in_array($key, $fields), ARRAY_FILTER_USE_KEY);
	}
}
