<?php
namespace mad\tools; 

class MadRouter {
	private $server = null;
	private $config = null;

	private $user = 'stormfactory';
	private $package = '';
	private $action = 'index';
	private $uri = '/';
	private $ajax = false;

	private $info = null;
	private $args = [];
	private $referrer = '/';
	private $path = null;

	private function __construct() {
		$this->config = MadConfig::getInstance();
		$this->server = (object) $_SERVER;
		$this->parseURI($_SERVER['REQUEST_URI']);
	}

	public static function getInstance() {
		static $i;
		if(! $i ) { $i = new self(); }
		return $i;
	}

	public function parseURI($uri) {
		$server = $this->server;

		$this->path = (object) [
			'root' => realPath( $server->DOCUMENT_ROOT ),
			'project' => dirname($server->DOCUMENT_ROOT . $_SERVER->SCRIPT_FILENAME) . '/',
			'tools' => __DIR__ . '/',
			'mad' => dirname(__DIR__) . '/'
		];

		$this->uri = current(explode('?', $uri));
		$this->ajax = isset($server->HTTP_X_REQUESTED_WITH) ? strtolower($server->HTTP_X_REQUESTED_WITH) == 'xmlhttprequest' : false;
		$this->referer = isset($server->HTTP_REFERER) ? $server->HTTP_REFERER : '/';

		$baseNames = array_values(array_filter(explode('/', dirname($server->SCRIPT_NAME))));
		$vhost = explode('.', $server->HTTP_HOST)[0];
		if( $vhost != 'www' ) {
			$this->user = $vhost;
		}

		$uris = array_values(array_filter(explode('/', $this->uri)));
		if(isset($server->REDIRECT_URI)) {
			$uris = array_slice($uris, count($baseNames));
		}

		for( $pos = count($uris); $pos >= 0; --$pos ) {
			$dir = implode('/', array_slice($uris, 0, $pos));

			$file = ($dir == '') ? "Controller.php" : "$dir/Controller.php";

			if( is_file( $file ) ) {
				$this->package = $dir;
				$this->action = isset( $uris[$pos] ) ? $uris[$pos] : 'index';
				$this->args = array_slice( $uris, $pos );
				BREAK;
			}
		}

		if( $this->package != '' && is_dir( $this->package ) ) {
			chdir($this->package);
			$this->config->appendConfig('config.json');
		}
	}

	function getRefererParams() {
		$queries = [];
		parse_str( parse_url( $this->referer, PHP_URL_QUERY ), $queries);
		return (object) $queries;
	}

	function findSetInfo($subs) {
		foreach($subs as $row){
			if( $row->href == $this->uri ) {
				$this->info = $row;
				return true;
			}
			if( isset($row->subs) && $this->findSetInfo($row->subs) ) {
				return true;
			}
		}
	}

	function checkAcl() {
		$config = $this->config;
		if(empty($config->acl)) {
			return true;
		}
		$all = '*';
		$user = MadUser::getInstance();
		if(isset($config->acl->$all) && ! $user->in($config->acl->$all)) {
			throw new Exception('Unauthorized.', 401);
		}
		$action = $this->action;
		if(isset($config->acl->$action) && ! $user->in($config->acl->$action) ) {
			throw new Exception('Unauthorized.', 401);
		}
		return true;
	}

	function isWhiteIp() {
		return in_array($this->server->REMOTE_ADDR, MadConfig::getInstance()->whiteIp);
	}

	function isAjax() {
		return (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}

	function isGet() {
		return $this->server->REQUEST_METHOD === 'GET';
	}

	function isPost() {
		return $this->server->REQUEST_METHOD === 'POST';
	}

	function isInternal() {
		return $_SERVER['REMOTE_ADDR'] == '127.0.0.1';
	}

	function ckInternal() {
		if(! $this->isInternal()) throw new Exception('Not Allowed', 405);
		return true;
	}

	function cors() {
		if ( isset($_SERVER['HTTP_ORIGIN'])
			&& ( preg_match("!https://\S+\.stormfactory\.co\.kr$!", $_SERVER['HTTP_ORIGIN'])
			|| $_SERVER['HTTP_ORIGIN'] === "http://175.126.123.201:3333")
		) {
			header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		}
	}

	function isMobile() {
		static $browsers = ['iPhone', 'iPod', 'IEMobile', 'Mobile', 'lgtelecom', 'PPC', 'skt', 'samsung', 'nokia', 'blackberri', 'android', 'sony', 'phone', 'windows', 'webos', 'palmos', 'Android', 'mobile'];
		$agent = $this->server->HTTP_USER_AGENT;
		foreach( $browsers as $name ) {
			if( strpos($agent, $name)  !== false ) {
				return true;
			}
		}
		return false;
	}

	function download($file, $type='zip') {
		$filename = urlencode(basename($file));
		header("Cache-Control: must-revalidate");
		header('Content-Type: ' . MadFile::mime($file));
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header("Content-Description: File Transfer");
		header("Content-Transfer-Encoding: binary");
	}

	function __get($key) {
		if( isset($this->$key) ) {
			return $this->$key;
		}
		return false;
	}

	function __set($key, $value) {
		$this->$key = $value;
	}

	function __isset($key) {
		return isset($this->$key);
	}

	function info($key) {
		if( isset($this->info->$key) ) {
			return $this->info->$key;
		}
		return '';
	}

	function getInfo() {
		return $this->info;
	}
}
