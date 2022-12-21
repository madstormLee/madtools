<?php
namespace mad\tools;

class MadLayout extends MadView {
	function __construct($userId) {
		if ( is_file('layout.html') ) {
			$this->file = 'layout.html';	
			return ;
		}
		$info = $this->fetch($userId);
		$this->file = MAD . "layout/$info->name/layout.html";
	}

	function fetch($userId) {
		$query = "select l.* from Site s
			left join Layout l on s.layoutId = l.id
			where userId=:userId";
		return MadDb::create()->row($query, ['userId' => $userId]);
	}
}
