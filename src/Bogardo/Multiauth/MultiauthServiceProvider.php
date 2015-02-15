<?php namespace Bogardo\Multiauth;

use Bogardo\Multiauth\User\UserProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Class MultiauthServiceProvider
 *
 * @package Bogardo\Multiauth
 */
class MultiauthServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * @var Service
     */
    protected $service;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('bogardo/multiauth');

        /** @var Service $service */
        $this->service = $this->app['multiauth.service'];

        $this->registerAuthDriver();

        $this->registerValidationRule();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bindShared('multiauth.service', function () {
            return new Service($this->app['config'], $this->app['db']);
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['multiauth.service'];
	}

    /**
     * Register the multiauth Auth driver
     */
    protected function registerAuthDriver()
    {
        $this->app['auth']->extend('multiauth', function () {
            return new UserProvider($this->app['hash'], $this->app['multiauth.service']);
        });
    }

    /**
     * Register custom validation rule
     */
    protected function registerValidationRule()
    {
        $this->app['validator']->extend('multiAuthUnique', 'Bogardo\Multiauth\Validator@validate');
    }

}
