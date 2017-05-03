<?php namespace JSONRPCTEMPLATE\API\IoC\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;

class AuthorizationServiceProvider extends AbstractServiceProvider {
	protected $provides = [
		'JSONRPCTEMPLATE\API\Services\AuthorizationService'
	];

	public function register() {
		$this->getContainer()
		->share('JSONRPCTEMPLATE\API\Services\AuthorizationService')
		->withArgument('League\Event\EmitterInterface')
		->withArgument('AndrewLarsson\Helpers\PDO\DatabaseInterface')
		;
	}
}
?>
