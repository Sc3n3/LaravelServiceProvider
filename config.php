<?php

return [

    'providers' => [
        Barryvdh\Debugbar\ServiceProvider::class,
        Spatie\Permission\PermissionServiceProvider::class,
        Collective\Html\HtmlServiceProvider::class,
        Vinkla\Hashids\HashidsServiceProvider::class,
    ],

    'aliases' => [
        'Collection' => Illuminate\Support\Collection::class,
        'Debugbar'   => Barryvdh\Debugbar\Facade::class,
        'Hashids'    => Vinkla\Hashids\Facades\Hashids::class,
        'Form'       => Collective\Html\FormFacade::class,
        'Html'       => Collective\Html\HtmlFacade::class,
    ],

    'commands' => [
        Foo\Bar\Services\Commands\DatabaseSeeder::class,
    ],

    'events' => [
        Foo\Bar\Services\Events\VideoWatched::class => [
            Foo\Bar\Services\Events\Listeners\UpdateVideoWatchCount::class,
        ],
    ],

    'subscribers' => [
        
    ],
    
    'middlewares' => [
        Foo\Bar\Middlewares\Language::class,
    ],

    'middlewareGroups' => [
        'web' => [
            App\Http\Middleware\EncryptCookies::class,
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Illuminate\Session\Middleware\StartSession::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
            App\Http\Middleware\VerifyCsrfToken::class,
            Foo\Bar\Middlewares\UserDetail::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ],

    'routeMiddlewares' => [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        'auth'         => Foo\Bar\Middlewares\Authenticate::class,
        'guest'        => Foo\Bar\Middlewares\Guest::class,
        'userIs'       => Foo\Bar\Middlewares\HasRole::class,
        'userCan'      => Foo\Bar\Middlewares\HasPermission::class,
        'userIsOrCan'  => Foo\Bar\Middlewares\HasRoleOrPermission::class,
        'userIsAndCan' => Foo\Bar\Middlewares\HasRoleAndPermission::class,
    ],

];
