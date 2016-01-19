<?php namespace KouTsuneka\Navigation;

use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'\template.php' => config_path('navigation.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerNavigationBuilder();
        $this->app->alias('navigator', 'KouTsuneka\Navigation\NavigationBuilder');
    }

    protected function registerNavigationBuilder()
    {
        $this->app->singleton('navigator', function($app)
        {
            return new NavigationBuilder();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('navigator', 'KouTsuneka\Navigation\NavigationBuilder');
    }
}