<?php namespace Hocza\Sendy;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class SendyServiceProvider extends ServiceProvider {

	/**
	* Indicates if loading of the provider is deferred.
	*
	* @var bool
	*/
	protected $defer = false;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('hocza/sendy', 'sendy', __DIR__.'/../');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('sendy', function() {
			return new Hocza\Sendy\Sendy;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('sendy');
	}

}
