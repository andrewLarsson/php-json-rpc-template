<?php namespace JSONRPCTEMPLATE\API\Exceptions;

use \Exception;

class AuthenticationException extends Exception {
	public function __construct($message = null, $code = 0, Exception $previous = null) {
		parent::__construct($message, 401, $previous);
	}
}

?>
