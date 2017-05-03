<?php namespace JSONRPCTEMPLATE\API\Session;

use \DateTime;
use \DateTimeZone;

use JsonRPC\MiddlewareInterface;

use JSONRPCTEMPLATE\API\Exceptions\AuthenticationException;
use JSONRPCTEMPLATE\API\Models\AuthenticationModel;
use JSONRPCTEMPLATE\API\Models\AccountModel;
use JSONRPCTEMPLATE\API\Services\AuthorizationService;
use JSONRPCTEMPLATE\API\Services\AccountService;

class AccountAuthenticationMiddleware implements MiddlewareInterface {
	private $AccountSession;
	private $AuthorizationService;
	private $AccountService;
	private $UnauthenticatedMethods;
	
	public function __construct(AccountSession $AccountSession, AuthorizationService $AuthorizationService, AccountService $AccountService, Array $UnauthenticatedMethods) {
		$this->AccountSession = $AccountSession;
		$this->AuthorizationService = $AuthorizationService;
		$this->AccountService = $AccountService;
		$this->UnauthenticatedMethods = $UnauthenticatedMethods;
	}
	
	public function execute($Username, $Password, $ProcedureName) {
		if (in_array($ProcedureName, $this->UnauthenticatedMethods)) {
			return null;
		}
		$AuthorizationHeader = null;
		if (function_exists('apache_request_headers')) {
			$ApacheHeaders = apache_request_headers();
			if (!array_key_exists('Authorization', $ApacheHeaders)) {
				throw new AuthenticationException('Missing HTTP Authorization header.');
			}
			$AuthorizationHeader = $ApacheHeaders['Authorization'];
		}
		if ($AuthorizationHeader == null) {
			if (!array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) {
				throw new AuthenticationException('Missing HTTP Authorization header.');
			}
			$AuthorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
		}
		$AuthorizationHeader = explode(' ', $AuthorizationHeader);
		if (count($AuthorizationHeader) != 2) {
			throw new AuthenticationException('Invalid HTTP Authorization header.');
		}
		$AuthorizationType = $AuthorizationHeader[0];
		$AccessToken = $AuthorizationHeader[1];
		if ($AuthorizationType != 'Bearer') {
			throw new AuthenticationException('Unsupported Authorization type.');
		}
		$Authentication = $this->AuthorizationService->LoadAuthenticationByAccessToken($AccessToken);
		$UTCDateTime = new DateTime(null, new DateTimeZone('UTC'));
		$ExpiresAtDate = new DateTime($Authentication->ExpiresAtDate, new DateTimeZone('UTC'));
		if ($UTCDateTime > $ExpiresAtDate) {
			throw new AuthenticationException('Expired Access Token.');
		}
		$Account = $this->AccountService->Load($Authentication->AccountID);
		$this->AccountSession->Account = $Account;
		$this->AccountSession->AccessToken = $Authentication->AccessToken;
	}
}
?>
