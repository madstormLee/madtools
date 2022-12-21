<?php
namespace mad\tools;

// 사실 file load부터 생각해야 하지만, 일단 MadConfig type으로 간다.
class MadInstaller {
	private $config;
	function __construct() {
	}
	function setConfig( MadConfig $config ) {
		$this->config = $config;
	}
	function setDirs( $dirs ) {
		$this->dirs = $dirs;
	}
	function installAll() {
		$this->installTable();
		$this->installController();
		$this->installModel();
		$this->installViews();
	}
	function installTable() {
		$converter = new MadConfig2Table( $this->config );
		$converter->save();
	}
	function installController() {
		$converter = new MadConfig2Controller( $this->config );
		$converter->save();
	}
	function installModel() {
		$converter = new MadConfig2Model( $this->config );
		$converter->save();
	}
	function installViews() {
		$converter = new MadConfig2Views( $this->config );
		$converter->save();
	}
}
