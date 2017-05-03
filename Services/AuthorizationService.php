<?php namespace JSONRPCTEMPLATE\API\Services;

use \DateTime;
use \DateTimeZone;
use \DateInterval;

use AndrewLarsson\Helpers\STDObject;
use AndrewLarsson\Helpers\PDO\DatabaseInterface;
use AndrewLarsson\Helpers\PDO\SQLStatements;
use AndrewLarsson\Helpers\PDO\SQLHelper;
use AndrewLarsson\Helpers\PDO\SQLExecutionHelper;

use League\Event\EmitterInterface;

use JSONRPCTEMPLATE\API\Models\AuthenticationModel;
use JSONRPCTEMPLATE\API\Models\AccountModel;
use JSONRPCTEMPLATE\API\Exceptions\AuthenticationException;

class AuthorizationService {
	const SERVICE = 'Authorization';
	private $Emitter;
	private $Database;
	
	public function __construct(EmitterInterface $Emitter, DatabaseInterface $Database) {
		$this->Emitter = $Emitter;
		$this->Database = $Database;
	}
	
	public function Authenticate($Username, $Password) {
		$Account = SQLExecutionHelper::searchUnique(
			$this->Database,
			new AccountModel([
				'Username' => $Username
			])
		);
		if (!$Account) {
			throw new AuthenticationException("Incorrect Username or Password.");
		}
		if (!$this->VerifyPassword($Password, $Account->PasswordHash)) {
			throw new AuthenticationException("Incorrect Username or Password.");
		}
		$Authentication = $this->CreateAuthenticationForAccount($Account);
		return $Authentication;
	}
	
	public function Refresh($RefreshToken) {
		$Authentication = SQLExecutionHelper::searchUnique(
			$this->Database,
			new AuthenticationModel([
				'RefreshToken' => $RefreshToken
			])
		);
		if (!$Authentication) {
			throw new AuthenticationException("Inavlid refresh token.");
		}
		$Account = SQLExecutionHelper::select(
			$this->Database,
			new AccountModel([
				'AccountID' => $Authentication->AccountID
			])
		);
		$Authentication = $this->CreateAuthenticationForAccount($Account);
		return $Authentication;
	}
	
	public function LoadAuthenticationByAccessToken($AccessToken) {
		$Authentication = SQLExecutionHelper::searchUnique(
			$this->Database,
			new AuthenticationModel([
				'AccessToken' => $AccessToken
			])
		);
		if (!$Authentication) {
			throw new AuthenticationException("Inavlid access token.");
		}
		return $Authentication;
	}
	
	private function VerifyPassword($Password, $PasswordHash) {
		return password_verify($Password, $PasswordHash);
	}
	
	private function CreateAuthenticationForAccount(AccountModel $Account) {
		$UTCDateTime = new DateTime(null, new DateTimeZone('UTC'));
		$UTCDateTimeExpiresAt = (new DateTime(null, new DateTimeZone('UTC')))->add(DateInterval::createFromDateString('1 day'));
		$UTCDateTimeMySQLFormatted = $UTCDateTime->format('Y-m-d H:i:s');
		$UTCDateTimeExpiresAtMySQLFormatted = $UTCDateTimeExpiresAt->format('Y-m-d H:i:s');
		$AccessToken = md5($Account->Username . $UTCDateTimeMySQLFormatted);
		$RefreshToken = md5($AccessToken . $UTCDateTimeMySQLFormatted);
		SQLExecutionHelper::insert(
			$this->Database,
			new AuthenticationModel([
				'AccountID' => $Account->AccountID,
				'AccessToken' => $AccessToken,
				'RefreshToken' => $RefreshToken,
				'ExpiresAtDate' => $UTCDateTimeExpiresAtMySQLFormatted
			])
		);
		$Authentication = SQLExecutionHelper::select(
			$this->Database,
			new AuthenticationModel([
				'AuthenticationID' => $this->Database->lastInsertId()
			])
		);
		$this->Emitter->emit(
			'JSONRPCTEMPLATE.Authorization.AccountAuthenticatedEvent',
			new STDObject([
				'AccountID' => $Account->AccountID,
				'AuthenticationID' => $Authentication->AuthenticationID
			])
		);
		return $Authentication;
	}
}
?>
