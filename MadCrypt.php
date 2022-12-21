<?php
namespace mad\tools;

class MadCrypt {
	private $key = '';
	private $method = "AES-128-CBC";

	function __construct($key) {
		$this->key = sha1($key);
	}

	function encrypt($data) {
		$plaintext = $data;
		$ivlen = openssl_cipher_iv_length($cipher = $this->method);
		$iv = openssl_random_pseudo_bytes($ivlen);
		$ciphertext_raw = openssl_encrypt($plaintext, $cipher, $this->key, $options = OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $ciphertext_raw, $this->key, $as_binary = true);
		$ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
		return $ciphertext;
	}

	function decrypt($data) {
		$c = base64_decode($data);
		$ivlen = openssl_cipher_iv_length($cipher = $this->method);
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len = 32);
		$ciphertext_raw = substr($c, $ivlen + $sha2len);
		$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $this->key, $options = OPENSSL_RAW_DATA, $iv);
		$calcmac = hash_hmac('sha256', $ciphertext_raw, $this->key, $as_binary = true);
		if(! hash_equals($hmac, $calcmac)) {
			return '';
		}
		return $original_plaintext;
	}
}
