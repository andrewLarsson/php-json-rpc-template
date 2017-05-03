<?php namespace JSONRPCTEMPLATE\API\Models;

use AndrewLarsson\Helpers\PDO\ModelAbstract;

class EventModel extends ModelAbstract {
	const TABLE = "Event";
	const PRIMARY_KEY = "EventID";

	public $EventID;
	public $Name;
	public $Context;
	public $Data;
	public $OccuredAtDate;
}
?>
