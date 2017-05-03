<?php namespace JSONRPCTEMPLATE\API\Exceptions;

use \Exception;

class NotAllowedException extends Exception {
	public function __construct($message = null, $code = 0, Exception $previous = null) {
		parent::__construct($message, 405, $previous);
	}
}

?>
