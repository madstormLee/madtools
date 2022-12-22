<?php
namespace mad\tools; 

define('BR', '<br />');
define('MAD', dirname(__DIR__) . '/');
define('ROOT', $_SERVER['DOCUMENT_ROOT'] . '/');

function pre( $value ) {
	echo '<pre>' . $value . '</pre>';
}
function printR($data) {
  echo '<pre class="printR">' . print_r( $data, true ) . '</pre>';
}
function varDump( $data ) {
  echo '<pre class="varDump">' . var_dump($data) . '</pre>';
}

spl_autoload_register(function ($class) {
	if(file_exists("$class.php")) {
		return require("$class.php");
	}
	$file = ROOT . str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
	return file_exists($file) && require($file);
});

return MadFront::getInstance();

class MadFront {
	private $data = [];
	private $htmlDirectServe = true;
	private $preProcess = [];

	public static function htaccess() {
		$exts = "png|jpg|gif|js|css|json|woff|ttf";
		$uri = $_SERVER['SCRIPT_NAME'];
		if(! preg_match("/\.($exts)$/", $uri) ) {
			return false;
		}

		$file = $_SERVER['DOCUMENT_ROOT'] . $uri;

		if( preg_match("/css$/", $uri) ) {
			$type = 'text/css';
		} else {
			$type = mime_content_type($file);
		}
		header('Content-Type: ' . $type);
		exit(readfile($file));
	}

	private function __construct() {
		error_reporting(E_ERROR);
		ini_set('display_errors', 1);
		set_error_handler([$this, 'errorHandler']);

		$this->router = MadRouter::getInstance();
		$this->config = MadConfig::getInstance();
		date_default_timezone_set('Asia/Seoul');
		session_start();

	}

	public static function p3p() {
		header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');
	}

	public static function getInstance() {
		if(! isset($_SERVER['REDIRECT_URL'])) {
			self::htaccess();
		}
		static $i; $i || $i = new self(); return $i;
	}

	public function debug() {
		error_reporting(E_ALL | E_STRICT);
		ini_set('display_errors', 1);
	}

	public function addPreProcess( MadProcessable $process ) {
		$this->preProcess[] = $process;
	}

	public function serve() {
		try {
			$this->router->checkAcl();

			$this->preProcess();
			print $this->process();
		} catch (\Exception $e) {
			print $this->serveError($e);
		}
		return true;
	}

	private function process() {
		$config = $this->config;
		$router = $this->router;

		if(! class_exists('Controller') ) {
			throw new \Exception("컨트롤러가 존재하지 않습니다.");
		}

		$controller = new \Controller();
		$controller->get = (object) $_GET;
		$controller->post = (object) $_POST;
		$controller->user = MadUser::getInstance();
		$controller->router = $router;
		$controller->config = $config;

		if( isset($config->layout) ) {
			$controller->setLayout( $router->path->root . $config->layout );
		}

		if(method_exists( $controller, 'init' ) ) {
			$controller->init();
		}

		$actionMethod = $router->action . 'Action';

		$result = $controller->$actionMethod();

		if(is_null($result)) {
			return $this->serveHtml($router->action . '.html', $controller);
		}
		return $this->postProcess($result, $controller);
	}

	private function preProcess() {
		if( isset($_SESSION['debug']) ) {
			$this>debug();
		}

		$config = MadConfig::getInstance();
		if( $config->preprocess ) {
			foreach( $config->preprocess as $row ) {
				$front->addPreProcess( $row );
			}
		}

		foreach( $this->preProcess as $process ) {
			$process->run();
		}
	}

	private function postProcess($result, $controller) {
		if( is_object($result) ) {
			if( method_exists( $result, '__toString' ) ) {
				return $result->__toString();
			} else {
				return $this->serveJson($result);
			}
		}

		if(is_array($result)) {
			return $this->serveJson($result);
		}

		if(is_numeric($result) || is_bool($result) || ! is_string($result)) {
			return $this->servePlane($result);
		}

		if( preg_match("/^[a-zA-Z]+:/", $result) ) {
			return $this->serveCommand($result);
		}

		if( preg_match( "/\.html$/", $result) ) {
			return $this->serveHtml($result, $controller);
		}

		return $this->servePlane($result);
	}

	private function servePlane($result) {
		header("content-type:application/json; charset=utf-8");
		return $result;
	}

	private function serveHtml($file, $controller) {
		header("content-type:text/html; charset=utf-8");
		$file = trim($file);

		$view = new MadView($file);
		if(! $view->isFile()) {
			throw new \Exception('Page Not Found', 404);
		}
		$view->setData( $controller->getData() );

		$layout = $controller->getLayout();
		if( gettype( $layout ) == 'string' ) {
			$layout = new MadView($layout, 'layout');
		}
		if( $layout->isFile() ) {
			$layout->main = $view;
			return $layout;
		}
		return $view;
	}

	private function serveJson($result) {
		if( isset($_GET['printr']) ) {
			printR( $result );
			die;
		}
		header("content-type:application/json; charset=utf-8");

		$options = JSON_UNESCAPED_UNICODE;
		if(isset($_GET['pretty'])) $options |= JSON_PRETTY_PRINT;
		return json_encode($result, $options);
	}

	function serveCommand($string) {
		$view = new MadView(__DIR__ . '/pageControl.html');
		list($view->command, $view->contents) = explode(':', $string);
		$params = [];
		foreach( explode(',', $view->contents) as $key => $value ) {
			$name = 'arg' . $key;
			$view->$name = trim($value);
			$params[] = $view->$name;
		}
		$view->params = $params;
		return $view;
	}

	function serveError(\Exception $e) {
		error_log($e->getTraceAsString());

		$code = $e->getCode();
		header($_SERVER['SERVER_PROTOCOL'] . " $code " . $e->getMessage(), true, $code);

		$config = MadConfig::getInstance();
		$file = $config->errorFile($code);

		if( ! is_file($file) ) {
			return $this->serveCommand("alert: " . $e->getMessage() . ", /");
		}

		$layout = $config->errorLayout();
		$view = new MadView($file);
		$view->e = $e;
		$layout->e = $e;
		$layout->main = $view;
		return $layout;
	}

	function __set($key, $value) {
		$this->data[$key] = $value;
	}
	function __get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public static function autoload($name) {
		static $dirs = [ '.', __DIR__ ];

		foreach( $dirs as $dir ) {
			$path = $dir . '/' . $name.'.php';
			if (! file_exists($path)) {
				continue;
			}
			return require_once($path);
		}
		return false;
	}

	static function errorHandler($errno, $errstr, $errfile, $errline) {
		$errorNames = [
			1 =>	'Fatal',
			2	=> 'WARNING.',
			4	=> 'PARSE',
			8	=> 'NOTICE',
			16	=> 'CORERROR',
			32	=> 'CORE_WARNING',
			64	=> 'COMPILE_ERROR',
			128	=> 'COMPILE_WARNING',
			256	=> 'User-generated error',
			512	=> 'User-generated warning',
			1024	=> 'User-generated notice',
			2048	=> 'STRICT',
			4096	=> 'Catchable fatal error',
			8192	=> 'DEPRECATED',
			16384	=> 'User-generated warning',
		];
		$rv = baseName($errfile) . "#$errline: {$errorNames[$errno]} $errstr\n";
		error_log($rv);
		if( $errno == E_DEPRECATED) {
			return true;
		}
		
		if (ini_get('display_errors') > 0 && (error_reporting() & $errno)) {
			echo $rv;
		}

		return false;
	}
}
