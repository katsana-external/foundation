<?php namespace Orchestra\Foundation\Filters;

use Illuminate\Http\RedirectResponse;
use Orchestra\Foundation\Application;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Auth\Authenticator;

class InstalledFilter
{
    /**
     * The application implementation.
     *
     * @var \Orchestra\Foundation\Application
     */
    protected $app;

    /**
     * The authenticator implementation.
     *
     * @var \Illuminate\Contracts\Auth\Authenticator
     */
    protected $auth;

    /**
     * The config repository implementation.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create a new filter instance.
     *
     * @param  \Orchestra\Foundation\Application        $app
     * @param  \Illuminate\Contracts\Auth\Authenticator $auth
     * @param  \Illuminate\Contracts\Config\Repository  $config
     */
    public function __construct(Application $app, Authenticator $auth, Repository $config)
    {
        $this->app = $app;
        $this->auth = $auth;
        $this->config = $config;
    }

    /**
     * Run the request filter.
     *
     * @return mixed
     */
    public function filter()
    {
        if ($this->app->installed()) {
            $type = ($this->auth->guest() ? 'guest' : 'user');
            $url  = $this->config->get("orchestra/foundation::routes.{$type}");

            return new RedirectResponse(handles($url));
        }
    }
}
