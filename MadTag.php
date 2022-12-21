<?php
namespace mad\tools;

class MadTag {
	private $tagName;
	private $attributes;
	private $children = array();
	private $innerHTML;
	private $singleTags = array(
		'input',
		'br',
	);

	function __construct( $tagName = 'div' ) {
		$this->tagName = strToLower( $tagName );
	}
	function __get( $key ) {
		return $this->getAttribute($key);
	}
	function __set( $key, $value ) {
		$this->addAttribute($key, $value);
	}
	function setTagName( $tagName ) {
		$this->tagName = $tagName;
		return $this;
	}
	function getTagName() {
		return $this->tagName;
	}
	function html( $html='' ) {
		if ( empty( $html ) ) {
			return $this->html;
		}
		$this->html = $html;
	}
	function attr( $key, $value='' ) {
		if ( empty( $value ) ) {
			return $this->getAttribute( $key );
		}
		return $this->addAttribute( $key, $value );
	}
	function addAttribute( $key, $value ) {
		$this->attributes[$key] = $value;
		return $this;
	}
	function setAttribute( $key, $value ) {
		$this->attributes[$key] = $value;
		return $this;
	}
	function getAttribute( $key ) {
		if ( isset( $this->attributes[$key] ) ) {
			return $this->attributes[$key];
		}
		return false;
	}
	function removeAttribute( $attribute ) { 
		if ( isset( $this->attributes[$attribute] ) ) {
			unset( $this->attributes[$attribute] );
		}
		return $this;
	}
	public function addChild( self $child ) {
		$this->children[] = $child;
		return $this;
	}
	protected final function getAttributesText() {
		$rv = array();
		if ( empty( $this->attributes ) ) {
			return '';
		}
		foreach( $this->attributes as $attribute => $value ) {
			$rv[] = $attribute . "='$value'";
		}
		return implode(' ' , $rv);
	}
	protected function getBody() {
		return $this->innerHTML;
		$temp = implode( $this->children );
	}
	protected function hasBody() {
		if ( empty( $this->children ) && empty( $this->innerHTML ) ) {
			return false;
		}
		return true;
	}
	function setData( $data ) {
		if ( ! empty( $data ) ) {
			$this->data = $data;
		}
		return $this;
	}
	function dl( $data = '' , $depth = 0 ) {
		if ( empty( $data ) ) {
			$data = $this->data;
		}
		++$depth;
		$rv = "<dl class='depth$depth'>\n";
		foreach( $data as $key => $value ){
			$rv .= "<dt>$key</dt>\n";
			if ( ! is_array( $value ) ) {
				$rv .= "<dd>$value</dd>\n";
			} else {
				$rv .= "<dd>" . $this->dl( $value, $depth ) ."</dd>\n";
			}
		}
		$rv .= "</dl>\n";
		return $rv;
	}
	function ul() {
		if ( empty( $this->data ) ) {
			return '';
		}
		$rv = '<ul>';
		foreach( $this->data as $name => $href ) {
			$rv .= "<li><a href='$href'>$name</a></li>";
		}
		$rv .= '</ul>';
		return $rv;
	}
	public function get() {
		if ( empty( $this->tagName ) ) {
			return '';
		}
		$attributesText = $this->getAttributesText();
		if ( in_array( $this->tagName, $this->singleTags ) ) {
			return "<$this->tagName $attributesText />";
		}
		return "<$this->tagName $attributesText>" . $this->getBody() . "</$this->tagName>";
		return $rv;
	}
	public final function __toString() {
		return $this->get();
	}
}
