<?php namespace JSONRPCTEMPLATE\API\Events;

use League\Event\AbstractListener;
use League\Event\EventInterface;

use AndrewLarsson\Helpers\PDO\DatabaseInterface;
use AndrewLarsson\Helpers\PDO\SQLHelper;

use JSONRPCTEMPLATE\API\Session\AccountSession;
use JSONRPCTEMPLATE\API\Models\EventModel;

class WildcardEventLoggerHandler extends AbstractListener {
	private $AccountSession;
	private $Database;
	
	public function __construct(AccountSession $AccountSession, DatabaseInterface $Database) {
		$this->AccountSession = $AccountSession;
		$this->Database = $Database;
	}
	
	public function handle(EventInterface $Event, $Data = null) {
		SQLHelper::prepareInsert(
			$this->Database,
			new EventModel([
				'Name' => $Event->getName(),
				'Context' => json_encode($this->AccountSession),
				'Data' => json_encode($Data)
			])
		)->execute();
	}
}
?>
