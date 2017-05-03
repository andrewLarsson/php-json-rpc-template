<?php namespace JSONRPCTEMPLATE\API\IoC\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;

class AccountServiceProvider extends AbstractServiceProvider {
	protected $provides = [
		'JSONRPCTEMPLATE\API\Services\AccountService'
	];

	public function register() {
		$this->getContainer()
		->share('JSONRPCTEMPLATE\API\Services\AccountService')
		->withArgument('JSONRPCTEMPLATE\API\Session\AccountSession')
		->withArgument('League\Event\EmitterInterface')
		->withArgument('AndrewLarsson\Helpers\PDO\DatabaseInterface')
		;
	}
}
?>
