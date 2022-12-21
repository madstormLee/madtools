<?php
namespace mad\tools;

use mad\tools\query\MadQuery;

class MadIndex extends MadData {
	protected $model = null;

	protected $query = null;

	protected $page = 1;
	protected $pages = 1;

	protected $searchTotal = false;
	protected $total = false;
	protected $pageNavi = null;

	function __construct( MadModel $model=null ) {
		$this->setModel( $model );
		$this->query = new MadQuery( $this->model->getName() );
	}
	function getModel() {
		return $this->model;
	}
	function setModel( MadModel $model=null ) {
		if ( is_null( $model ) ) {
			$model = new MadModel;
		}
		$this->model = $model;
		return $this;
	}
	function init() {
		$get = MadRouter::getInstance()->params;
		if ( $get->page ) {
			$this->query->limit( 10, $get->page );
		}
		$this->setSearchTotal();
		$this->pageNavi = '';
		return $this;
	}
	function getSearchTotal() {
		return $this->setSearchTotal()->searchTotal;
	}
	function setSearchTotal() {
		if ( $this->searchTotal === false ) {
			$this->searchTotal = $this->query->searchTotal();
		}
		return $this;
	}
	function getTotal() {
		return $this->setTotal()->total;
	}
	function setTotal() {
		if ( $this->total === false ) {
			$this->total = $this->query->total();
		}
		return $this;
	}
	// @override
	function isEmpty() {
		return ! $this->getSearchTotal();
	}
	function getQuery() {
		$this->init();
		return $this->query;
	}
	function setQuery( MadQuery $query ) {
		$this->query = $query;
		return $this;
	}
	protected $iterator = null;
	function getIterator(): \Traversable {
		if ( is_null( $this->iterator ) ) {
			$db = MadDb::create();
			$statement = $db->query( $this->query );
			$statement->setFetchMode( PDO::FETCH_CLASS, $this->model->getName() );
			$this->iterator = new ArrayIterator( $statement->fetchAll() );
		}
		return $this->iterator;
	}
	function getRows() {
		return $this->query->limit();
	}
	function setRows( $limit ) {
		$this->query->limit( $limit );
		return $this;
	}
	function setPages( $pages = 10 ) {
		$this->pageNavi->pages = $pages;
	}
	function getPageNavi() {
		if ( empty( $this->pageNavi ) ) {
			$this->pageNavi = new MadPageNavi( $this->query );
		}
		return $this->pageNavi;
	}
	function getMoreNavi( $href = './list', $param = '' ) {
		$view = new MadView('views/moreNavi.html');
		$view->rows = $this->query->limit;
		if ( $view->rows == 0 ) {
			return '';
		}
		$view->href = $href;
		$view->param = $param;
		$view->list = $this;
		if ( ! $page = MadParams::create('get')->page ) {
			$page = 1;
		}
		$view->page = $page;
		$view->nextPage = $view->page + 1;
		$view->total = $this->getSearchTotal();

		if(  $view->page * $view->rows > $view->total ) {
			return '';
		}
		return $view;
	}
	/************************ todo: refactorying. from ListModel ****************/
	protected $searchables=null;

	function isSearchable() {
		return $this->getSearchables()->count();
	}

	function getSearchables() {
		if ( is_null($this->searchables) ) {
			$this->setSearchables();
		}
		return $this->searchables;
	}
	function setSearchables() {
		$setting = clone $this->model->getSetting();
		$this->searchables = $setting->filter( function( $row ){ return isset($row->search); });
		return $this;
	}
	function search( $where ) {
		$this->getQuery()->where( $where );
		return $this;
	}
	function curry( $listName, $column, $searchKey = 'id' ) {
		if ( ! class_exists( $listName ) ) {
			throw new Exception("no $listName class");
		}
		$this->init();
		$list = new $listName;
		$name = $list->getTable();

		$ids = $this->getData()->dic( $column )->filter()->implode(',');
		if ( empty( $ids ) ) {
			return false;
		}
		$list->where( "$searchKey in ( $ids )" )->limit();
		$listdata = $list->getData()->index( $searchKey );

		foreach( $this->data as &$row ) {
			if ( $target = $row->$column ) {;
				$row->$name = $list->$target;
			} else {
				$row->$name = array();
			}
		}

		return $this;
	}
	function searchField( $field, $values ) {
		if ( ! $this->isField( $field ) ) {
			throw new Exception('Search field not exists!');
		}
		$type = $this->model->getConfig()->$field->type;

		if ( $type == 'date' ) {
			$this->searchDate( $field, $values );
		} elseif ( in_array( $type, array( 'radio', 'checkbox', 'select' ) ) ) {
			$this->searchIn( $field, $values );
		} else {
			$this->searchText( $field, $values );
		}
		return $this;
	}
	function searchText( $field, $value ) {
		$this->query->where( "$field like '$value'" );
		return $this;
	}
	function searchIn( $field, $values = '' ) {
		if ( empty( $values ) ) {
			return $this;
		}
		if ( ! $values instanceof MadData ) {
			if ( ! is_array( $values ) ) {
				$values = array( $values );
			}
			$values = new MadData( $values );
		}
		$data = filter_var_array( $values->getData(), FILTER_SANITIZE_STRING );
		$values = implode( "','", $values->getData() );
		$this->query->where( "$field in ($values)" );
		return $this;
	}
	function searchInNumeric( $field, MadData $values) {
		$values = filter_var_array( $values->getData(), FILTER_SANITIZE_NUMBER_INT );
		$values = implode( ',', $values );
		$this->query->where( "$field in ($values)" );
		return $this;
	}
}
