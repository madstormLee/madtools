<?php
namespace mad\tools;

class MadFileIndex {
	protected $data = [];

	protected $pattern = '*';
	protected $flags = 0;

	function __construct($pattern='*', $flags=0) {
		$this->pattern = $pattern;
		$this->flags = $flags;
	}

	function info($file) {
		$permissions = fileperms($file);
		$perms = substr(sprintf('%o', $permissions), -4);
		return [
			'name' => basename($file),
			'dir' => dirname($file),
			'file' => $file,
			'path' => $file,
			'perms' => $perms,
			'writable' => is_writable($file),
			'readable' => is_readable($file),
			'executable' => is_executable($file),
			'isDir' => is_dir( $file ),
			'createDate' => date('Y-m-d H:i:s', filectime($file)),
			'updateDate' => date('Y-m-d H:i:s', filemtime($file)),
		];
	}

	function list() {
		if(empty($this->data)) {
			$this->data = array_map(fn($row) => $this->info($row), glob($this->pattern, $this->flags));
		}
		return $this->data;
	}
	function total() {
		if(empty($this->data)) {
			$this->list();
		}
		return count($this->data);
	}
}
