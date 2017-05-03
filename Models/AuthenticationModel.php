<?php namespace JSONRPCTEMPLATE\API\Models;

use AndrewLarsson\Helpers\PDO\ModelAbstract;

class AuthenticationModel extends ModelAbstract {
	const TABLE = "Authentication";
	const PRIMARY_KEY = "AuthenticationID";

	public $AuthenticationID;
	public $AccountID;
	public $AccessToken;
	public $RefreshToken;
	public $ExpiresAtDate;
	public $IssuedAtDate;
}
?>
