<?php
namespace mad\tools;

class MadUser {
	private $data = null;

	private function __construct() {
		$this->data = isset($_SESSION['user']) ? $_SESSION['user'] : (object) ['userId' => 'guest', 'role' => ['guest']];
	}

	public static function getInstance() {
		static $i;
		if(! $i) $i = new self();
		return $i;
	}

	function autologin($key = true) {
		if(! $key ) {
			return setcookie('autologin', null, -1, '/');
		}
		$rv = password_hash(uniqid(), PASSWORD_DEFAULT);
		setcookie('autologin', $rv, strToTime('+1 month'), '/');
		return $rv;
	}

	function login($user) {
		if(empty($user->role) ) {
			$user->role = ['member'];
		}
		$_SESSION['user'] = $user;
	}
	function logout() {
		$this->autologin(false);
		unset($_SESSION['user']);
	}
	function isLogin() {
		if( $this->is('guest') ) {
			return false;
		}
		return !! $this->id;
	}

	function in($roles) {
		return count( array_intersect( (array) $roles, (array) $this->role ) ) > 0;
	}

	function info() {
		return $this->data;
	}

	function auth() {
		if( $this->is('admin') ) {
			return true;
		}
		$router = MadRouter::getInstance();
		$endpoint = substr($router->uri, 1);
		if( $endpoint[-1] == '/' ) {
			$endpoint .= 'index';
		}

		$service = new MadMongoService('security');
		$info = $service->fetchWhere(['endpoint' => $endpoint]);
		if( ! $info ) {
			return false;
		}

		foreach( $info->auth as $auth => $bool ) {
			if( $bool == 'true' && $this->is($auth) ) {
				return true;
			}
		}

		return false;
	}

	function is($name) {
		return in_array($name, $this->role);
	}

	function __get($key) {
		return isset($this->data->$key) ? $this->data->$key : '';
	}

	function __isset($key) {
		return isset($this->data->$key);
	}
}
