<?php
namespace mad\tools;

class MadDir extends MadFile {
	protected $pattern = '*';
	protected $flag = 0;

	function __construct( $dir = '.', $pattern='*' ) {
		$this->setDir( $dir );
		$this->setPattern( $pattern );
	}

	function setDir( $dir ) {
		$this->file = $dir;
		return $this;
	}

	function getDir() {
		return $this->file;
	}

	function getData() {
		$this->getIndex();
		return $this->data;
	}

	function list() {
		if ( ! empty( $this->data ) ) {
			return $this->data;
		}
		if ( $this->file == '' ) {
			$target = "$this->pattern";
		} else {
			$target = "$this->file/$this->pattern";
		}
		foreach( glob( $target, $this->flag ) as $file ) {
			$this->data[] = new MadFile( $file );
		}
		return $this->data;
	}

	function getParentDir() {
		return dirName( $this->file );
	}

	function setPattern( $pattern ) {
		if ( false !== strpos( $pattern, '{' ) ) {
			$this->addFlag( GLOB_BRACE );
		}
		if( empty( $pattern ) ) {
			$pattern = '*';
		}
		$this->pattern = $pattern;
		return $this;
	}

	function getPattern( $pattern ) {
		return $this->pattern;
	}

	function filter( $function='' ) {
		$function = 'is_file';
		$this->data = array_filter( $this->getData(), $function );

		return $this;
	}

	function addFlag( $flag ) {
		$this->flag = $this->flag | $flag;
		return $this;
	}

	function setFlag( $flag ) {
		$this->flag = $flag;
		return $this;
	}

	function flag( $flag='' ) {
		if ( empty( $flag ) ) {
			return $this->getFlag();
		}
		return $this->addFlag( $flag );
	}

	function order( $order='dirFirst' ) {
		$data = $this->getData();
		if ( $order == 'dirFirst' ) {
			$dirs = array_filter($data, 'is_dir');
			$files = array_filter($data, 'is_file');
			$this->data = array_merge( $dirs, $files );
		}
		return $this;
	}

	function mkdir() {
		if ( ! $this->isDir() ) {
			mkdir( $this->file, 0755, true );
		}
		return $this;
	}

	function rmdir() {
	}

	function save() {
		return $this->mkdir();
	}

	function getIterator(): \Traversable {
		return new ArrayIterator( $this->getIndex() );
	}
	/***************** utilities *****************/

	function getTree($dir='', $root = true,$UploadDate=false) {
		static $tree;
		static $base_dir_length;
		if ( empty( $dir ) ) {
			$dir = $this->file;
		}

		if ($root) {
			$tree = [];
			$base_dir_length = strlen($dir) + 1;
		}

		if (is_file($dir)) {
			if($UploadDate!=false) {
				if(filemtime($dir)>strtotime($UploadDate)) {
					$tree[substr($dir, $base_dir_length)] = date('Y-m-d H:i:s',filemtime($dir));   
				}
			} else {
				$tree[substr($dir, $base_dir_length)] = date('Y-m-d H:i:s',filemtime($dir));
			}
		} elseif ((is_dir($dir) && substr($dir, -4) != ".svn") && $di = dir($dir) ) {
			if (!$root) {
				$tree[substr($dir, $base_dir_length)] = false;
			}
			while (($file = $di->read()) !== false) {
				if ($file != "." && $file != "..") {
					$this->getTree("$dir/$file", false,$UploadDate);
				}
			}
			$di->close();
		}
		if ($root)
			return $tree;   
	}

	function copyFiles( MadDir $destDir ) {
		if ( ! $destDir->isDir() ) {
			$destDir->mkDir();
		}
		$files = array_filter( glob( $this->file . '/{*,.[a-z]*}', GLOB_BRACE ), 'is_file' );
		$i = 0;
		foreach( $files as $file ) {
			$destFile = $destDir . '/' . basename($file);
			if ( ! copy( $file, $destFile ) ) {
				throw new Exception( 'copy error occured.' );
			}
			++$i;
		}
		return $i;
	}

	// from : jyotsnachannagiri@gmail.com 
	function copyR(self $dst_dir,$UploadDate=false, $use_cached_dir_trees = false) {
		static $cached_src_dir;
		static $src_tree;
		static $dst_tree;
		$src_dir = $this->file;
		$log = [];

		if(($slash = substr($src_dir, -1)) == "\\" || $slash == "/") {
			$src_dir = substr($src_dir, 0, strlen($src_dir) - 1);
		}
		if(($slash = substr($dst_dir, -1)) == "\\" || $slash == "/") {
			$dst_dir = substr($dst_dir, 0, strlen($dst_dir) - 1);
		}

		if (!$use_cached_dir_trees || !isset($src_tree) || $cached_src_dir != $src_dir) {
			$src_tree = $this->getTree($src_dir,true,$UploadDate);
			$cached_src_dir = $src_dir;
			$src_changed = true;
		}
		if (!$use_cached_dir_trees || !isset($dst_tree) || $src_changed) {
			$dst_tree = $this->getTree($dst_dir,true,$UploadDate);
		}
		if (!is_dir($dst_dir)) {
			mkdir($dst_dir, 0777, true);
		}

		foreach ($src_tree as $file => $src_mtime) {
			if (!isset($dst_tree[$file]) && $src_mtime === false) {
				mkdir("$dst_dir/$file");
			} elseif (!isset($dst_tree[$file]) && $src_mtime || isset($dst_tree[$file]) && $src_mtime > $dst_tree[$file]) {
				if( ! copy("$src_dir/$file", "$dst_dir/$file")) {
					throw new Exception("File '$src_dir/$file' could not be copied!");
				}
				$log[] = "Copied '$src_dir/$file' to '$dst_dir/$file'";
				@touch("$dst_dir/$file", strToTime($src_mtime) );
			}
		}
		return $log;
	}

	function deleteAll( $file='' ) {
		$dir = empty( $file ) ? $this->file : $file;
		if ( ! is_dir( $dir ) ) {
			throw new Exception( $dir . ' is not a directory.' );
		}
		$files = array_filter( glob( $dir . '/{*,.*}', GLOB_BRACE ), function( $file ) {
			return ( is_dir( $file ) && substr( $file, -1 ) == '.' ) ? false:true;
		});
		foreach ( $files as $file ) {
			if ( is_link( $file ) ) {
				unlink( $file );
				continue;
			}
			if( is_dir($file) ) {
				$this->deleteAll( $file );
			} else {
				unlink( $file );
			}
		}
		return rmDir( $dir );
	}

	public static function globR($pattern, $flags = 0) {
		$files = glob($pattern, $flags);

		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
			if ( is_link( $dir ) ) {
				continue;
			}
			$files = array_merge($files, globR($dir.'/'.basename($pattern), $flags));
		}

		return $files;
	}

	function __toString() {
		return $this->file;
	}
}
