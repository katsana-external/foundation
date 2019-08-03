<?php

namespace Orchestra\Foundation\Bootstrap;

use Orchestra\Model\Memory\UserProvider;
use Orchestra\Model\Memory\UserRepository;
use Illuminate\Contracts\Foundation\Application;

class LoadUserMetaData
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->make('orchestra.memory')->extend('user', static function ($app, $name) {
            return new UserProvider(
                new UserRepository($name, [], $app)
            );
        });
    }
}
