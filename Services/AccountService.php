<?php namespace JSONRPCTEMPLATE\API\Services;

use AndrewLarsson\Helpers\STDObject;
use AndrewLarsson\Helpers\PDO\DatabaseInterface;
use AndrewLarsson\Helpers\PDO\SQLStatements;
use AndrewLarsson\Helpers\PDO\SQLHelper;
use AndrewLarsson\Helpers\PDO\SQLExecutionHelper;
use AndrewLarsson\Helpers\PDO\PagingMetaData;

use League\Event\EmitterInterface;

use JSONRPCTEMPLATE\API\Session\AccountSession;
use JSONRPCTEMPLATE\API\Models\AccountModel;
use JSONRPCTEMPLATE\API\Exceptions\RecordNotFoundException;
use JSONRPCTEMPLATE\API\Exceptions\InputValidationException;
use JSONRPCTEMPLATE\API\Exceptions\NotAllowedException;

class AccountService {
	const SERVICE = 'Account';
	private $AccountSession;
	private $Emitter;
	private $Database;
	
	public function __construct(AccountSession $AccountSession, EmitterInterface $Emitter, DatabaseInterface $Database) {
		$this->AccountSession = $AccountSession;
		$this->Emitter = $Emitter;
		$this->Database = $Database;
	}
	
	public function Load($AccountID) {
		$this->ValidatePermissionToOperateOnAccount($AccountID);
		$Account = SQLExecutionHelper::select(
			$this->Database,
			new AccountModel([
				'AccountID' => $AccountID
			]), [
				'AccountID',
				'Username',
				'CreatedAtDate',
				'UpdatedAtDate'
			]
		);
		if (!$Account) {
			throw new RecordNotFoundException("Account not found.");
		}
		return $Account;
	}
	
	public function Create($Username, $Password) {
		$Username = trim($Username);
		$this->ValidateUsername($Username);
		$this->ValidatePassword($Password);
		$PasswordHash = $this->HashPassword($Password);
		SQLExecutionHelper::insert(
			$this->Database,
			new AccountModel([
				'Username' => $Username,
				'PasswordHash' => $PasswordHash
			])
		);
		$Account = SQLExecutionHelper::select(
			$this->Database,
			new AccountModel([
				'AccountID' => $this->Database->lastInsertId()
			]), [
				'AccountID',
				'Username',
				'CreatedAtDate',
				'UpdatedAtDate'
			]
		);
		$this->Emitter->emit(
			'JSONRPCTEMPLATE.Account.CreatedEvent',
			new STDObject([
				'AccountID' => $Account->AccountID
			])
		);
		return $Account;
	}
	
	public function ChangeUsername($AccountID, $Username) {
		$this->ValidatePermissionToOperateOnAccount($AccountID);
		$this->ValidateUsername($Username);
		SQLExecutionHelper::update(
			$this->Database,
			new AccountModel([
				'AccountID' => $AccountID,
				'Username' => $Username
			])
		);
		$this->Emitter->emit(
			'JSONRPCTEMPLATE.Account.UsernameChangedEvent',
			new STDObject([
				'AccountID' => $AccountID,
				'Username' => $Username
			])
		);
	}
	
	public function ChangePassword($AccountID, $Password) {
		$this->ValidatePermissionToOperateOnAccount($AccountID);
		$this->ValidatePassword($Password);
		$PasswordHash = $this->HashPassword($Password);
		SQLExecutionHelper::update(
			$this->Database,
			new AccountModel([
				'AccountID' => $AccountID,
				'PasswordHash' => $PasswordHash
			])
		);
		$this->Emitter->emit(
			'JSONRPCTEMPLATE.Account.PasswordChangedEvent',
			new STDObject([
				'AccountID' => $AccountID
			])
		);
	}
	
	private function HashPassword($Password) {
		return password_hash($Password, PASSWORD_BCRYPT);
	}
	
	private function ValidateUsername($Username) {
		$Account = SQLExecutionHelper::searchUnique(
			$this->Database,
			new AccountModel([
				'Username' => $Username
			]), [
				'AccountID'
			]
		);
		if ($Account) {
			throw new InputValidationException('Account with that Username already exists.');
		}
	}
	
	private function ValidatePassword($Password) {
		if (!preg_match('/[a-zA-Z0-9#?!@$%^&*~-]{8,54}/', $Password)) {
			throw new InputValidationException('Invalid password.');
		}
	}

	private function ValidatePermissionToOperateOnAccount($AccountID) {
		if ($this->Account->AccountID != $AccountID) {
			throw new NotAllowedException('You are not allowed to access an Account oher than your own.');
		}
	}
}
?>
