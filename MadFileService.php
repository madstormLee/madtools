<?php
namespace mad\tools;

class MadFileService {
	private $dir = '.';

	function __construct($dir) {
		$this->dir = $dir;
	}

	function save($data) {
	}

	function index() {
		new MadFileIndex($this->dir);
	}
}
