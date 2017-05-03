<?php namespace JSONRPCTEMPLATE\API\Models;

use AndrewLarsson\Helpers\PDO\ModelAbstract;

class AccountModel extends ModelAbstract {
	const TABLE = "Account";
	const PRIMARY_KEY = "AccountID";

	public $AccountID;
	public $Username;
	public $PasswordHash;
	public $CreatedAtDate;
	public $UpdatedAtDate;
}
?>
