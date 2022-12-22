<?
namespace mad\tools;

class MadUploader implements IteratorAggregate {
	protected $dir = 'data';

	function __construct($dir = 'data') {
		$this->dir = $this->setDir($dir);
	}

	function setDir( $dir ) {
		if ( ! is_dir($dir) ) {
			if( ! mkdir($dir , 0777, true) ) {
				throw new \Exception("Fail to create dir: $dir");
			}
		}
		$this->dir = $dir;
		return $this;
	}

	function upload() {
		if(! is_writable($this->dir) ) {
			throw new \Exception("저장할 수 없는 디렉토리입니다: " . $this->dir);
		}
		setlocale(LC_ALL, "C.UTF-8");
		$rv = [];
		foreach( $_FILES as $key => $row ) {
			// name|type|tmp_name|error|size|
			$row = (object) $row;
			if($row->error != 0 || empty($row->name) ) {
				continue;
			}

			$row->name =  $this->getAvailableName(basename($row->name));
			$row->dest = $this->dir . $row->name;
			if (! move_uploaded_file($row->tmp_name, $row->dest)) {
				throw new \Exception('파일업로드 오류 입니다. 확인 후 이용하여 주세요.');
			}
			$rv[] = $row;
		}
		return $rv;
	}

	function getIterator() {
		return new ArrayIterator($_FILES);
	}

	private function getAvailableName() {
		$ext = end( explode( '.', $this->name ) );
		$name = baseName( $this->name, ".$ext" );
		$tail = '';
		while( is_file( $file = "$this->dir/$name$tail.$ext" ) ) {
			$tail = '_' . ++$i;
		}
		return $file;
	}

	function getMaxSize() {
		$size = $this->config->maxSize;
		if(! $size ) {
			$size = '8 MB'; // default
		}
		$value = intval( $size );
		$unit = strToLower( preg_replace('/[^a-zA-Z]/', '', $size) );
		if( $unit == 'mb' ) {
			return $value * 1024 * 1024;
		}
		if( $unit == 'kb' ) {
			return $value * 1024;
		}
		return $value;
	}

	function isImage($path) {
		return !! getimagesize($path);
	}
}
