<?php
namespace mad\tools\query;

class MadQuerySet extends MadData {
	function fields() {
		return '`' . implode('`,`', array_keys($this->data)) . '`';
	}

	function insert() {
		if( empty( $this->data ) ) {
			return '';
		}
		$fields = [];
		$values = [];
		$seq = 0;
		foreach( $this->data as $key => $value ) {
			if( preg_match('/^[a-zA-Z_]+\(\)$/',  $value) ) {
				$fields[] = "`$key`";
				$values[] = $value;
				continue;
			}
			$identifier = $key;
			if(! preg_match('/[a-zA-Z0-9_]+/', $identifier) ) {
				++$seq;
				$identifier = '__id' . $seq;
				$this->data[$identifier] = $value;
				unset( $this->data[$key] );
			}
			$fields[] = "`$key`";
			$values[] = ":$identifier";
		}
		$fieldStatement = '(' . implode(',', $fields) . ')';
		$valueStatement = '(' . implode(', ', $values) . ')';
		return $fieldStatement . ' values ' . $valueStatement;
	}

	function update() {
		if( empty( $this->data ) ) {
			return '';
		}
		$statements = [];
		$seq = 0;
		foreach( $this->data as $key => $value ) {
			if( preg_match('/^[a-zA-Z_]+\(\)$/',  $value) ) {
				$statements[] = "$key = $value";
			} else {
				$identifier = $key;
				if(! preg_match('/[a-zA-Z0-9_]+/', $identifier) ) {
					++$seq;
					$identifier = '__id' . $seq;
					$this->data[$identifier] = $value;
					unset( $this->data[$key] );
				}
				$statements[] = "`$key`=:$identifier";
			}
		}
		return 'set ' . implode(', ', $statements);
	}

	function __toString() {
		return $this->update();
	}
}
