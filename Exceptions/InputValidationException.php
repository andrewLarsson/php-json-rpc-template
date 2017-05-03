<?php namespace JSONRPCTEMPLATE\API\Exceptions;

use \Exception;

class InputValidationException extends Exception {
	public function __construct($message = null, $code = 0, Exception $previous = null) {
		parent::__construct($message, 400, $previous);
	}
}

?>
