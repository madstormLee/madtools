<?php
namespace mad\tools;

class MadFile implements IteratorAggregate, Countable {
	protected $file = '';
	protected $data = [];

	protected $info = null;

	protected $contents = '';

	function __construct( $file = '' ) {
		$this->setFile( $file );
	}

	static function format($bytes, $precision = 1) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow]; 
	}

	static $types;

	static function mimeTypes() {
		if(empty(self::$types)) {
			self::$types = MadJson::fromFile(__dir__ . '/fileTypes.json');
		}
		return self::$types;
	}

	static function mime($file) {
		if(is_file($file)) {
			return mime_content_type($file);
		}
		$mimeTypes = self::mimeTypes();
		$ext = end(explode('.', $file));
		return isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'text/plain';
	}

	/******************* getter/setter *******************/
	function setFile( $file ) {
		$this->file = $file;
		return $this;
	}
	function getFile() {
		return $this->file;
	}
	function getBasename( $tail = '' ) {
		return basename( $this->file, $tail );
	}
	function getDirname() {
		return dirName( $this->file );
	}
	function dirName() {
		return dirName( $this->file );
	}
	function size() {
		return filesize($this->file);
	}
	function count() {
		return count( $this->data );
	}
	function date( $format = 'Y-m-d' ) {
		return date( $format, $this->mtime() );
	}
	function mtime() {
		return fileMtime( $this->file );
	}
	function ctime() {
		return fileCtime( $this->file );
	}
	function atime() {
		return fileAtime( $this->file );
	}
	function getData() {
		return $this->data;
	}
	function setData( $data = [] ) {
		$this->data = $data;
		return $this;
	}
	function addData( $data = [] ) {
		$this->data = array_merge( $this->data, $data );
		return $this;
	}
	function getName() {
		return baseName( $this->file );
	}
	function getExtension() {
		return end( explode( '.', baseName( $this->file ) ) );
	}
	function setInfo() {
		$this->info = new SplFileInfo( $this->file );
		return $this;
	}
	function getInfo() {
		if ( ! $this->info instanceof SplFileInfo ) {
			$this->setInfo();
		}
		return $this->info;
	}
	/******************* checks *******************/
	function isWritable() {
		return is_writable( $this->file );
	}
	function isEmpty() {
		return ( empty( $this->data ) );
	}
	function exists() {
		return file_exists( $this->file );
	}
	function isImage() {
		return exif_imagetype( $this->file );
	}
	function isText() {
		if ( preg_match( '(txt|css|js|json|php)$',$this->file ) ) {
			return true;
		}
		$info = new finfo( FILEINFO_MIME );
		return substr( $info->file( $this->file ), 0, 4 ) == 'text';
	}
	function isBinary() {
		return ! $this->isText();
	}
	function isFile() {
		if ( 0 === strpos( $this->file, 'http' ) ) {
			$headers = get_headers($this->file);
			return ! ! preg_match( '/200 OK$/', current( $headers ) );
		}
		return is_file( $this->file );
	}
	function isDir() {
		return is_dir( $this->file );
	}
	function hasDir() {
		if ( ! $this->isDir() ) {
			return false;
		}
		foreach( $this as $row ) {
			if( $row->isDir() ) return true;
		}
		return false;
	}
	/******************* crud *******************/
	function fetch( $file ) {
		return $this->setFile( $file );
	}
	function getContents() {
		if ( ! empty( $this->contents ) ) {
			return $this->contents;
		}
		if ( ! $this->isFile() ) {
			return '';
		}
		return $this->contents = file_get_contents( $this->file );
	}
	function get_fcontent( $url,  $javascript_loop = 0, $timeout = 5 ) {
		//$url = str_replace( "&amp;", "&", urldecode(trim($url)) );
		$cookie = tempnam ("/tmp", "CURLCOOKIE");
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		$content = curl_exec( $ch );
		$response = curl_getinfo( $ch );
		curl_close ( $ch );
		if ( in_array($response['http_code'], [301, 302]) ) {
			ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
			if ( $headers = get_headers($response['url']) ) {
				foreach( $headers as $value ) {
					if ( substr( strtolower($value), 0, 9 ) == "location:" )
						return get_url( trim( substr( $value, 9, strlen($value) ) ) );
				}
			}
		}
		if (( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5) {
			return get_url( $value[1], $javascript_loop+1 );
		}
		return [$content, $response];
	}
	function setContents( $contents ) {
		$this->contents = $contents;
		return $this;
	}
	function template( $data = [] ) {
		$assign = [];
		foreach( $data as $key => $value ) {
			$assign['{'.$key.'}'] = $value;
		}
		$rv = str_replace( array_keys($assign), array_values($assign), $this->getContents() );
		return $rv;
	}
	function phpTemplate() {
		$phpReplaces = array( '<?' => '{{', '?>' => '}}' );
		$keys = array_keys( $phpReplaces );
		$contents = str_replace( $keys, $phpReplaces, $this->getContents() );
		$contents = htmlEntities( $contents );
		return $contents;
	}
	function phpTemplateDecode( $contents ) {
		$phpReplaces = array( '<?' => '{{', '?>' => '}}' );
		$keys = array_keys( $phpReplaces);
		$contents = html_entity_decode( $contents );
		$contents = str_replace( $phpReplaces, $keys, $contents );
		return $this->setContents( $contents );
	}
	function getSource() {
		if ( ! $this->isFile() ) {
			return '';
		}
		return show_source( $this->file, true );
	}
	function getViewer() {
		$ext = $this->getExtension();
		if ( $ext == 'html' ) {
			return $this->escapePhp();
		} elseif ( $ext == 'php' ) {
			return $this->getSource();
		}
		return nl2br( $this->getContents() );
	}
	// todo: move from here
	function escapePhp() {
		$rv = $this->getContents();
		$changes = array(
			'<?' => '&lt;?',
			'?>' => '?&gt;',
		);
		return str_replace( array_keys( $changes ), $changes, $rv );
	}
	function delete( $file = '' ) {
		if ( $file ) {
			$this->setFile( $file );
		}
		if( $this->isDir() ) {
			return rmDir( $this->file );
		} else if( $this->isFile() ) {
			return unlink( $this->file );
		}
		return false;
	}
	function save() {
		return file_put_contents( $this->file, $this->getContents() );
	}
	function saveAs( $file ) {
		$this->file = $file;
		return $this->save();
	}
	function saveContents( $contents ) {
		$this->contents = $contents;
		return $this->save();
	}
	function append( $data ) {
		return file_put_contents( $this->file, $contents, FILE_APPEND );
	}
	function nl2br() {
		return nl2br( $this->getContents() );
	}
	function log( $message ) {
		$message = date("Y-m-d H:i:s") . " - $message\n";
		return $this->append( $message );
	}
	function rename( $name ) {
		if ( ! $this->isWritable() ) {
			return false;
		}
		$target = $this->getFile();
		$dest = dirName( $target ) . '/' . $name;
		if ( ! rename( $target, $dest ) ) {
			throw new Exception( 'Rename Failure!' );
		}
		$this->file = $dest;
		return $this;
	}
	function stat() {
		return (object) stat( $this->file );
	}
	private $ext = null;

	function contentType() {
		$this->ext = (object) [
			'images' => ['jpg', 'jpeg', 'gif', 'png', 'bmp']
		];

		$info = pathinfo($this->file);
		$ext = strtolower($info['extension']);
		if( in_array($ext, $this->ext->images) ) {
			$img_info = getimagesize($server_filename);
			return $img_info['mime'];
		}
		return 'text/plain';
	}

	function download() {
		header("Content-Type: " . $this->contentType());
		header("Content-Disposition: attachment; filename=" . $this->getBasename());
		header('Expires: 0');
		header("Pragma: public");
		header('Cache-Control: must-revalidate');
		header("Content-Description: File Transfer");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . $this->size());
		return readfile( $this->file );
	}

	function getIndex() {
		return $this->getIterator();
	}
	function getLines() {
		return file( $this->file );
	}
	function getIterator(): \Traversable {
		if ( $this->isDir() ) {
			return new MadDir( $this->file );
		}
		if ( ! $this->isFile() ) {
			return new ArrayIterator( [] );
		}
		return new ArrayIterator( $this->getLines() );
	}
	function __set( $key, $value ) {
		$this->data[$key] = $value;
	}
	function __get( $key ) {
		if ( ! isset( $this->data[$key] ) ) {
			return '';
		}
		return  $this->data[$key];
	}
	function __isset( $key ) {
		return isset( $this->data[$key] );
	}
	function __unset( $key ) {
		unset( $this->data[$key] );
	}
	function __toString() {
		return $this->file;
	}
	function __call( $method, $args ) {
		$info = $this->getInfo();
		if ( $info instanceof SplFileInfo ) {
			return call_user_func_array( [$info, $method], $args);
		}
		throw new Exception("There is no $method method in " . get_class($this) . "." );
	}
}
