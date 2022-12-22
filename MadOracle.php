<?php
namespace mad\tools;

class MadOracle {
	private $conn = null;

	function __construct($info) {
		$tns = "(DESCRIPTION = 
			(ADDRESS = (PROTOCOL = TCP)(HOST = ".$info->host.")(PORT = ".$info->port.")) 
			(CONNECT_DATA = (SERVICE_NAME = ".$info->service_name.") (SID = ".$info->sid."))
			)";

		$this->conn = oci_connect($info->username, $info->password, $tns, 'AL32UTF8');
		if (! $this->conn) {
			$e = oci_error();
			throw new \Exception(htmlentities($e['message'], ENT_QUOTES));
		}
	}
}
