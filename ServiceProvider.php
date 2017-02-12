<?php

namespace Foo\Bar;

use Gate;
use Route;
use Event;
use Cache;

use Foo\Bar\Models\User;
use Foo\Bar\Models\Channel;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider as ServiceProviderBase;
use Illuminate\Foundation\AliasLoader;

class ServiceProvider extends ServiceProviderBase {

    private $config = null;

    public static function getPath($path = null)
    {
        return realpath(__DIR__.'/..'. ( $path == null ? '' : '/'. $path ));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Kernel $kernel, Router $router, Request $request)
    {
        //
        $this->registerGates();
        $this->setRoutes($request);

        //$this->loadRoutesFrom(self::getPath('routes.php'));
        $this->loadViewsFrom(self::getPath('Views'), 'tivu');
        $this->loadMigrationsFrom(self::getPath('Services/Migrations'));
        $this->loadTranslationsFrom(self::getPath('Services/Languages'), 'tivu');

        $loader = AliasLoader::getInstance();
        foreach($this->getConfig('aliases') as $alias => $class) {
            $loader->alias($alias, $class);
        }

        foreach(glob(self::getPath('Services/Configs') .'/*.php') ?: [] as $file) {
            $this->mergeConfigFrom($file, 'tivu.'. pathinfo($file, PATHINFO_FILENAME));
        }

        foreach ($this->getConfig('middlewares') as $middleware) {
            $kernel->pushMiddleware($middleware);
        }

        foreach ($this->getConfig('middlewareGroups') as $key => $middleware) {
            $router->middlewareGroup($key, $middleware);
        }

        foreach ($this->getConfig('routeMiddlewares') as $key => $middleware) {
            $router->middleware($key, $middleware);
        }

        foreach ($this->getConfig('events') as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        foreach ($this->getConfig('subscribers') as $subscriber) {
            Event::subscribe($subscriber);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->commands($this->getConfig('commands'));

        foreach ($this->getConfig('providers') as $provider) {
            $this->app->register($provider);
        }

        Str::macro('slugs', function($title, $separator = '-') {
            $letters = [ 'ö' => 'o', 'ı' => 'i', 'ü' => 'u' ];

            return Str::slug(str_ireplace(array_keys($letters), array_values($letters), $title), $separator);
        });

        require self::getPath('helpers.php');
    }

    private function setRoutes(Request $request)
    {
        Route::group([
            'namespace' => 'Foo\Bar\Controllers',
            'prefix' => '/'. $request->segment(1)
        ], function() {
            require self::getPath('routes.php');
        });
    }

    private function registerGates()
    {
        Gate::define('channel', function(User $user, Channel $channel) {
            return Cache::remember('Channel::getChannelUsers-'. $channel->id, 60, function() use($channel) {
                return $channel->users()->get();
            })->where('id', $user->id)->count() > 0;
        });
    }

    private function getConfig($name = null)
    {
        $this->config = $this->config ?: require self::getPath('config.php');

        return !$name ? $this->config 
                      : isset($this->config[$name]) ? $this->config[$name] 
                                                    : [];
    }

}
