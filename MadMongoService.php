<?php
namespace mad\tools;

class MadMongoService {
	protected $collection;

	function __construct($collection='') {
		if( empty( $collection ) ) {
			$collection = str_replace('Service', '', get_class($this));
		}
		$this->collection = $this->db()->$collection;
	}

	function setCollection($collectionName, $dbName = '') {
		$db = $this->db($dbName);
		$this->collection = $db->$collectionName;
		return $this;
	}

	function getCollection() {
		return $this->collection;
	}

	function db($name = '') {
		$client = new MongoDB\Client("mongodb://localhost:27017");
		if( ! $name ) {
			$name = MadRouter::getInstance()->user;
		}
		return $client->$name;
	}

	public function saveList($list) {
		$cnt = 0;
		foreach( $list as $row ) {
			$this->save( (object) $row );
			++ $cnt;
		}	
		return $cnt;
	}

	public function save($post) {
		if(! $post->_id ) {
			return $this->insert($post);
		}
		return $this->update($post);
	}

	function update($post) {
		$id = $post->_id;
		unset( $post->_id );
		$result = $this->collection->updateOne(
			['_id' => new \MongoDB\BSON\ObjectID($id)]
			, ['$set' => $post]
		);
		return $result->getModifiedCount();
	}

	function insert($post) {
		unset( $post->_id );
		$result = $this->collection->insertOne( $post );
		return $result->getInsertedId();
	}

	function find($condition) {
		return $this->collection->find( $condition );
	}

	function getMongoId($id) {
		return new \MongoDB\BSON\ObjectId($id);
	}

	function list($where) {
		if ( isset($where->id) ) {
			$where->_id = $this->getMongoId($where->id);
			unset($where->id);
		}
		$list = $this->collection->find($where)->toArray();

		if (gettype($list[0]->_id) === 'object' ) {
			foreach( $list as &$row ) {
				if( get_class($row->_id) == 'MongoDB\BSON\ObjectId' ) {
					$row->writeDate = date('Y-m-d H:i:s', $row->_id->getTimestamp());
				}
				$row->_id = "$row->_id";
			}
		}
		return $list;
	}

	function fetch($id) {
		$where = (object) ['_id' => $id];
		return $this->fetchWhere($where);
	}

	function fetchWhere($where) {
		if( is_array($where) ) {
			$where = (object) $where;
		}
		if( isset( $where->_id ) ) {
			$where->_id = $this->getMongoId($where->_id);
		}
		$row = $this->collection->findOne($where);
		if( null != $row ) {
			$row->writeDate = date('Y-m-d H:i:s', $row->_id->getTimestamp());
			$row->_id = "$row->_id";
		}
		return $row;
	}

	function delete($id) {
		$where = (object) ['_id' => $id];
		return $this->deleteWhere($where);
	}

	function deleteWhere($where) {
		if( isset( $where->_id ) ) {
			$where->_id = new \MongoDB\BSON\ObjectId($where->_id);
		}
		$result = $this->collection->deleteOne($where);
		return $result->getDeletedCount();
	}

	function select() {
		$collections = $this->collection->find();
		foreach ($collections as $collection) {
			varDump($collection);
		}
		die;	
	}
	
	function aggregate($strQuery) {
	}
}
