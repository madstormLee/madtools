<?php
namespace mad\tools;

class MadController {
	protected $layout = "layout.html";
	protected $data = [];

	function __construct() {
	}

	function init() {
		if((! isset($this->config->model)) && is_file('model.mad') ) {
			$this->installAction();
		}
	}

	function testAction() {
		if(! $this->user->is('developer') ) {
			throw new Exception('Unauthorized.', 401);
		}
		if( class_exists('Test') ) {
			return new Test();
		}
	}

	function debug() {
		ini_set("display_errors", 1);
	}

	function layout($name) {
		$layoutDir = $this->config->layoutDir;
		if( empty( $layoutDir ) ) {
			$layoutDir = MAD . 'layout/';
		}
		if( $layoutDir[0] == '~') {
			$layoutDir = str_replace('~', ROOT, $layoutDir);
		}
		$dir = $layoutDir . $name;
		$file = $dir . '/layout.html';
		if(is_dir($dir) && is_file( $file )) {
			$this->layout = $file;
		}
	}

	protected function service($table = '') {
		if(! $table) {
			$table = $this->table();
		}

		$service = $table . 'Service';
		if(class_exists($service)) {
			return new $service;
		}
		return new MadService($table);
	}

	protected function index() {
		return $this->service()->index();
	}

	function table() {
		$packages = array_map('ucFirst', explode('/', $this->router->package));
		return implode($packages);
	}

	function getLayout() {
		return $this->layout;
	}

	function setLayout($layout) {
		$this->layout = $layout;
	}

	function getData() {
		return $this->data;
	}

	function setData($data = []) {
		return $this->data = $data;
	}

	function __get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : '';
	}
	function __set($key, $value) {
		$this->data[$key] = $value;
	}
	function __isset($key) {
		return isset($this->data[$key]);
	}
	function __call($method, $params) {
		if( preg_match("/Action$/", $method)) {
			return null;
		}
		throw new Exception("Method not found: $method()");
	}

	protected function createAcl($actions) {
		$rv = [];
		foreach( $actions as $action ) {
			if( preg_match("/Action$/", $action)) {
				$name = str_replace('Action', '', $action);
				$rv[$name] = ["developer"];
			}
		}
		return $rv;
	}
	
	function installAction() {
		$model = new MadModel($this->table());
		$configModel = (object) $model->config('model.mad');

		$configContents = [
			"model" => $configModel,
			"acl" => $this->createAcl(get_class_methods($this))
		];

		MadConfig::saveConfig($configContents);
		$scheme = new MadScheme($configModel);
		$scheme->saveScheme();	

		$query = file_get_contents('create.sql');
		$db = MadDb::create();
		return $db->execute($query);
	}
}
