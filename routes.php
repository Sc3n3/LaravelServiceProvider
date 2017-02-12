<?php

use Foo\Bar\Models\User;
use Foo\Bar\Models\Category;
use Foo\Bar\Models\Channel;

Route::group(['middleware' => ['web']], function() {
    
    Route::get('/', ['uses' => 'HomeController@getHome', 'as' => 'Home::getHome']);
    Route::get('/search', ['uses' => 'HomeController@getHome', 'as' => 'Home::getSearch']);

    // Kanal Yönetimi
    Route::group(['prefix' => '/channel', 'namespace' => 'Channel\Manager', 'middleware' => ['auth']], function() {
        Route::get('/', ['uses' => 'ChannelController@getNewChannelForm', 'as' => 'ChannelManager::getNewChannel']);
        Route::post('/', ['uses' => 'ChannelController@postNewChannel', 'as' => 'ChannelManager::postNewChannel']);
    });

    // Kanal Yönetimi
    Route::group(['prefix' => '/manage/{channel}', 'namespace' => 'Channel\Manager', 'middleware' => ['auth', 'can:channel,channel']], function() {
        Route::get('', ['uses' => 'ChannelController@getChannel', 'as' => 'ChannelManager::getChannel']);
        Route::get('/video', ['uses' => 'VideoController@getChannelNewVideo', 'as' => 'ChannelManager::getChannelNewVideo']);
        Route::get('/videos', ['uses' => 'VideoController@getChannelVideos', 'as' => 'ChannelManager::getChannelVideos']);

        Route::get('/program', ['uses' => 'ProgramController@getChannelNewProgram', 'as' => 'ChannelManager::getNewChannelProgram']);
        Route::get('/programs', ['uses' => 'ProgramController@getChannelPrograms', 'as' => 'ChannelManager::getChannelPrograms']);

        Route::get('/playlist', ['uses' => 'PlaylistController@getChannelNewPlaylist', 'as' => 'ChannelManager::getNewChannelPlaylist']);
        Route::get('/playlists', ['uses' => 'PlaylistController@getChannelPlaylists', 'as' => 'ChannelManager::getChannelPlaylists']);
        Route::get('/playlists/{playlist}', ['uses' => 'PlaylistController@getChannelPlaylist', 'as' => 'ChannelManager::getChannelPlaylist']);

        Route::get('/{program}', ['uses' => 'ProgramController@getChannelProgram', 'as' => 'ChannelManager::getChannelProgram']);
        Route::get('/{program}/{video}', ['uses' => 'VideoController@getProgramVideo', 'as' => 'ChannelManager::getChannelProgramVideo']);

        Route::post('/video', ['uses' => 'VideoController@postChannelNewVideo', 'as' => 'ChannelManager::postChannelNewVideo']);
        Route::post('/program', ['uses' => 'ProgramController@postChannelNewProgram', 'as' => 'ChannelManager::postNewChannelProgram']);
        Route::post('/playlist', ['uses' => 'PlaylistController@postChannelNewPlaylist', 'as' => 'ChannelManager::postChannelNewPlaylist']);
        Route::post('/playlist/{playlist}', ['uses' => 'PlaylistController@postChannelUpdatePlaylist', 'as' => 'ChannelManager::postChannelUpdatePlaylist']);
    });

    Route::group(['prefix' => '/channels'], function() {
        Route::get('/', ['uses' => 'Channel\ChannelController@getChannels', 'as' => 'Channel::getChannels']);
        Route::get('/{category}', ['uses' => 'Channel\ChannelController@getCategoryChannels', 'as' => 'Channel::getCategoryChannels']);
    });

    Route::group(['prefix' => '/user'], function() {
        Route::get('/login', ['uses' => 'UserController@getLogin', 'as' => 'User::getLogin', 'middleware' => ['guest']]);
        Route::get('/register', ['uses' => 'UserController@getRegister', 'as' => 'User::getRegister', 'middleware' => ['guest']]);
        Route::get('/me', ['uses' => 'UserController@getProfile', 'as' => 'User::getUserProfile', 'middleware' => ['auth']]);
        Route::get('/logout', ['uses' => 'UserController@getLogout', 'as' => 'User::getLogout', 'middleware' => ['auth']]);
        Route::get('/{user}', ['uses' => 'UserController@getProfile', 'as' => 'User::getProfile', 'middleware' => ['auth']]);

        Route::post('/login', ['uses' => 'UserController@postLogin', 'as' => 'User::postLogin', 'middleware' => ['guest']]);
        Route::post('/register', ['uses' => 'UserController@postRegister', 'as' => 'User::postRegister', 'middleware' => ['guest']]);
    });

    Route::get('/me', ['uses' => 'UserController@getProfile', 'as' => 'User::getUserProfileFromSlug', 'middleware' => ['auth']]);
    Route::get('/{hashids}.m3u8', ['uses' => 'Channel\ChannelController@getChannelStreamFile', 'as' => 'Channel::getChannelStreamFile']);

    // Ziyaretçi
    Route::group(['prefix' => '/{channel}'], function() {
        Route::get('/', ['uses' => 'Channel\ChannelController@getChannel', 'as' => 'Channel::getChannel']);
        Route::get('/live', ['uses' => 'Channel\ChannelController@getChannelLive', 'as' => 'Channel::getChannelLive']);
        Route::get('/programs', ['uses' => 'Channel\ChannelController@getChannelPrograms', 'as' => 'Channel::getChannelPrograms']);
        Route::get('/programs/{category}', ['uses' => 'Channel\ChannelController@getChannelCategoryPrograms', 'as' => 'Channel::getChannelCategoryPrograms']);
        Route::get('/{program}', ['uses' => 'Channel\ProgramController@getChannelProgram', 'as' => 'Channel::getChannelProgram']);
        Route::get('/{program}/{video}', ['uses' => 'Channel\VideoController@getProgramVideo', 'as' => 'Channel::getChannelProgramVideo']);
    });

    foreach(['video', 'channel', 'program', 'username', 'category', 'hashids'] as $pattern) {
        Route::pattern($pattern, '[0-9a-z_\-]+');
    }

    Route::bind('hashids', function($value) { 
        return Hashids::decode($value)[0] ?? false; 
    });

    Route::bind('channel', function($value, $route) {
        $channel = Cache::remember('Channel::getChannel-'. $value, 10, function() use($value) {
            return Channel::where(function($query) use($value) {
                $query->where('slug', $value)->orWhere('id', Hashids::decode($value)[0] ?? $value);
            })->firstOrFail();
        });

        return View::share('channel', $channel);
    });

    Route::bind('program', function($value, $route) {
        $program = $route->parameter('channel')->programs()->where(function($query) use($value) {
            $query->where('slug', $value)->orWhere('id', Hashids::decode($value)[0] ?? $value);
        })->firstOrFail();

        return View::share('program', $program);
        /*
        $program = Cache::remember('Program::getProgram-'. $value, 10, function() use($value) {
            return \Foo\Bar\Models\Program::where(function($query) use($value) {
                $query->where('slug', $value)->orWhere('id', $value);
            })->firstOrFail();
        });

        return View::share('program', $program);*/
    });

    Route::bind('video', function($value, $route) {
        $video = $route->parameter('program')->videos()->where(function($query) use($value) {
            $query->where('slug', $value)->orWhere('id', Hashids::decode($value)[0] ?? $value);
        })->firstOrFail();

        return View::share('video', $video);
        /*
        $video = Cache::remember('Video::getVideo-'. $value, 10, function() use($value) {
            return \Foo\Bar\Models\Video::where(function($query) use($value) {
                $query->where('slug', $value)->orWhere('id', $value);
            })->firstOrFail();
        });

        return View::share('video', $video);*/
    });

    Route::bind('category', function($value, $route) {
        $category = Cache::remember('Category::getCategory-'. $value, 10, function() use($value) {
            $categories = Collection::make(trans('tivu::categories'));
            $categoryCache = $categories->where('slug', $value)->first() ?: $categories->where('id', Hashids::decode($value)[0] ?? $value)->first();
            $value = $categoryCache ? $categoryCache['id'] : $value;

            return Category::where(function($query) use($value) {
                $query->where('slug', $value)->orWhere('id', Hashids::decode($value)[0] ?? $value);
            })->firstOrFail();
        });

        return View::share('category', $category);
    });

    Route::bind('playlist', function($value, $route) {
        $playlist = $route->parameter('channel')->playlists()->where(function($query) use($value) {
            $query->where('id', $value);
        })->firstOrFail();

        return View::share('playlist', $playlist);
        /*
        $playlist = Cache::remember('Playlist::getPlaylist-'. $value, 10, function() use($value) {
            return \Foo\Bar\Models\Playlist::where(function($query) use($value) {
                $query->where('id', $value);
            })->firstOrFail();
        });

        return View::share('playlist', $playlist);*/
    });

    Route::bind('user', function($value, $route) {
        $user = Cache::remember('User::getUser-'. $value, 10, function() use($value) {
            return User::where(function($query) use($value) {
                $query->where('username', $value)->orWhere('id', Hashids::decode($value)[0] ?? $value);
            })->firstOrFail();
        });

        return View::share('user', $user);
    });
});
