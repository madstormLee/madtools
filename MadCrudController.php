<?php
namespace mad\tools;

class MadCrudController extends MadController {
	function installAction() {
		$file = 'create.sql';
		if( file_exists($file) ) {
			return $service->execute($file);
		}
	}
}
