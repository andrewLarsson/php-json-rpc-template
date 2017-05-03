<?php namespace JSONRPCTEMPLATE\API;

use \ReflectionClass;
use \ReflectionMethod;

use League\Container\Container;
use League\Event\Emitter;

use JsonRPC\Server;

use AndrewLarsson\Helpers\PDO\Databases;
use AndrewLarsson\Helpers\PDO\SQLStatements;

use JSONRPCTEMPLATE\API\Events\WildcardEventLoggerHandler;
use JSONRPCTEMPLATE\API\Session\AccountAuthenticationMiddleware;
use JSONRPCTEMPLATE\API\Session\AccountSession;

class Main {
	public static function __execute() {
		date_default_timezone_set('UTC');
		$pwd = dirname(__FILE__);
		$Container = new Container();
		
		$AccountSession = new AccountSession();
		$Container->share('JSONRPCTEMPLATE\API\Session\AccountSession', $AccountSession);
		
		$Databases = new Databases($pwd . '/conf/database/conf.d');
		$SQLStatements = new SQLStatements($pwd . '/database/statements');
		$Container->share('AndrewLarsson\Helpers\PDO\DatabaseInterface', $Databases->JSONRPCTEMPLATE);
		$Container->share('AndrewLarsson\Helpers\PDO\SQLStatements', $SQLStatements);
		
		$Emitter = new Emitter();
		$Emitter->addListener('*', new WildcardEventLoggerHandler($AccountSession, $Databases->JSONRPCTEMPLATE));
		$Container->share('League\Event\EmitterInterface', $Emitter);
		
		$Container->addServiceProvider('JSONRPCTEMPLATE\API\IoC\ServiceProviders\AuthorizationServiceProvider');
		$Container->addServiceProvider('JSONRPCTEMPLATE\API\IoC\ServiceProviders\AccountServiceProvider');
		
		$AuthorizationService = $Container->get('JSONRPCTEMPLATE\API\Services\AuthorizationService');
		$AccountService = $Container->get('JSONRPCTEMPLATE\API\Services\AccountService');
		
		$Services = [
			$AuthorizationService,
			$AccountService
		];
		
		$Server = new Server();
		$Server->getMiddlewareHandler()->withMiddleware(
			new AccountAuthenticationMiddleware(
				$AccountSession,
				$AuthorizationService,
				$AccountService,
				[
					'Account.Create',
					'Authorization.Authenticate',
					'Authorization.Refresh'
				]
			)
		);
		foreach ($Services as $Service) {
			$ReflectedObject = new ReflectionClass($Service);
			$ServiceName = $ReflectedObject->getconstant('SERVICE');
			$ServicePublicMethods = $ReflectedObject->getMethods(ReflectionMethod::IS_PUBLIC);
			foreach ($ServicePublicMethods as $ServicePublicMethod) {
				$ServiceMethodName = $ServicePublicMethod->name;
				$Server->getProcedureHandler()
				->withClassAndMethod($ServiceName . '.' . $ServiceMethodName, $Service, $ServiceMethodName)
				;
			}
		}
		
		echo $Server->execute();
	}
}
?>
