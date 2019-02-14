<?php

namespace Orchestra\Foundation\Bootstrap;

use Orchestra\Contracts\Messages\MessageBag;
use Illuminate\Contracts\Foundation\Application;

class NotifyIfSafeMode
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
        if ($app->make('orchestra.extension.status')->is('safe')) {
            $app->make('orchestra.messages')->extend(function (MessageBag $messages) {
                $messages->add('info', \trans('orchestra/foundation::response.safe-mode'));
            });
        }
    }
}
