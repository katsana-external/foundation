<?php

namespace Orchestra\Foundation;

use Closure;
use Exception;
use Orchestra\Http\RouteManager;
use Orchestra\Extension\RouteGenerator;
use Orchestra\Contracts\Memory\Provider;
use Orchestra\Foundation\Http\Handlers\UserMenuHandler;
use Orchestra\Foundation\Http\Handlers\SettingMenuHandler;
use Orchestra\Foundation\Http\Handlers\ExtensionMenuHandler;
use Orchestra\Contracts\Foundation\Foundation as FoundationContract;

class Foundation extends RouteManager implements FoundationContract
{
    /**
     * Booted indicator.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The config repository implementation.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Application status/mode implementation.
     *
     * @var \Orchestra\Contracts\Extension\StatusChecker
     */
    protected $status;

    /**
     * Construct a new instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->config = $app->make('config');
        $this->status = $app->make('orchestra.extension.status');
    }

    /**
     * Get acl services.
     *
     * @return \Orchestra\Contracts\Authorization\Authorization
     */
    public function acl()
    {
        return $this->app->make('orchestra.platform.acl');
    }

    /**
     * Start the application.
     *
     * @return $this
     */
    public function boot()
    {
        if (! $this->booted) {
            // Mark the application as booted and boot the application.
            $this->booted = true;

            $this->bootApplication();
        }

        return $this;
    }

    /**
     * Get installation status.
     *
     * @return bool
     */
    public function installed()
    {
        return $this->app->make('orchestra.installed');
    }

    /**
     * Get application mode.
     *
     * @return
     */
    public function mode()
    {
        return $this->status->mode();
    }

    /**
     * Get memory services.
     *
     * @return \Orchestra\Contracts\Memory\Provider
     */
    public function memory()
    {
        return $this->app->make('orchestra.platform.memory');
    }

    /**
     * Get menu services.
     *
     * @return \Orchestra\Widget\MenuWidgetHandler
     */
    public function menu()
    {
        return $this->app->make('orchestra.platform.menu');
    }

    /**
     * Get widget services by type.
     *
     * @param  string  $type
     *
     * @return \Orchestra\Widget\Handler
     */
    public function widget($type)
    {
        return $this->app->make('orchestra.widget')->make("{$type}.orchestra");
    }

    /**
     * Register the given Closure with the "group" function namespace set.
     *
     * @param  string|null  $namespace
     * @param  \Closure|null  $callback
     *
     * @return void
     */
    public function namespaced($namespace, Closure $callback)
    {
        $attributes = [];

        if (! empty($namespace) && $namespace != '\\') {
            $attributes['namespace'] = $namespace;
        }

        $attributes['middleware'] = ['orchestra'];

        $this->group('orchestra/foundation', 'orchestra', $attributes, $callback);
    }

    /**
     * Get extension route.
     *
     * @param  string  $name
     * @param  string  $default
     *
     * @return \Orchestra\Contracts\Extension\RouteGenerator
     */
    public function route($name, $default = '/')
    {
        // Boot the application.
        $this->boot();

        if (in_array($name, ['orchestra', 'orchestra/foundation'])) {
            $name = 'orchestra';
        }

        return parent::route($name, $default);
    }

    /**
     * Boot application.
     *
     * @return void
     */
    protected function bootApplication()
    {
        $this->registerBaseServices();

        try {
            $memory = $this->bootInstalledApplication();
        } catch (Exception $e) {
            $memory = $this->bootNewApplication();
        }

        $this->app->instance('orchestra.platform.memory', $memory);

        $this->registerComponents($memory);

        $this->app->make('events')->fire('orchestra.started', [$memory]);
    }

    /**
     * Run booting on installed application.
     *
     * @return \Orchestra\Contracts\Memory\Provider
     *
     * @throws \Exception
     */
    protected function bootInstalledApplication()
    {
        // Initiate Memory class from App, this to allow advanced user
        // to use other implementation if there is a need for it.
        $memory = $this->app->make('orchestra.memory')->make();

        if (is_null($memory->get('site.name'))) {
            throw new Exception('Installation is not completed');
        }

        // In event where we reach this point, we can consider no
        // exception has occur, we should be able to compile acl and
        // menu configuration
        $this->acl()->attach($memory);

        // In any event where Memory failed to load, we should set
        // Installation status to false routing for installation is
        // enabled.
        $this->app->instance('orchestra.installed', true);

        $this->createAdminMenu();

        return $memory;
    }

    /**
     * Run booting on new application.
     *
     * @return \Orchestra\Contracts\Memory\Provider
     */
    protected function bootNewApplication()
    {
        // In any case where Exception is catched, we can be assure that
        // Installation is not done/completed, in this case we should
        // use runtime/in-memory setup
        $memory = $this->app->make('orchestra.memory')->make('runtime.orchestra');
        $memory->put('site.name', 'Orchestra Platform');

        $this->menu()->add('install')
            ->title('Install')
            ->link($this->handles('orchestra::install'))
            ->icon('power-off');

        $this->app->instance('orchestra.installed', false);

        return $memory;
    }

    /**
     * Create Administration Menu for Orchestra Platform.
     *
     * @return void
     */
    protected function createAdminMenu()
    {
        $menu   = $this->menu();
        $events = $this->app->make('events');

        $handlers = [
            UserMenuHandler::class,
            ExtensionMenuHandler::class,
            SettingMenuHandler::class,
        ];

        $menu->add('home')
            ->title($this->app->make('translator')->get('orchestra/foundation::title.home'))
            ->link($this->handles('orchestra::/'))
            ->icon('home');

        foreach ($handlers as $handler) {
            $events->listen('orchestra.started: admin', $handler);
        }
    }

    /**
     * Register base application services.
     *
     * @return void
     */
    protected function registerBaseServices()
    {
        $this->app->instance('orchestra.platform.menu', $this->widget('menu'));
        $this->app->instance('orchestra.platform.acl', $this->app->make('orchestra.acl')->make('orchestra'));

        $this->app->instance('app.menu', $this->app->make('orchestra.widget')->make('menu.app'));
    }

    /**
     * Register base application components.
     *
     * @param  \Orchestra\Contracts\Memory\Provider  $memory
     *
     * @return void
     */
    protected function registerComponents(Provider $memory)
    {
        $this->app->make('orchestra.notifier')->setDefaultDriver('orchestra');
        $this->app->make('orchestra.mail')->attach($memory);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateRouteByName($name, $default)
    {
        // Orchestra Platform routing is managed by `orchestra/foundation::handles`
        // and can be manage using configuration.
        if (in_array($name, ['orchestra'])) {
            return $this->app->make(RouteGenerator::class, [
                $this->config->get('orchestra/foundation::handles', $default),
                $this->app->make('request'),
            ]);
        }

        return parent::generateRouteByName($name, $default);
    }

    /**
     * Magic method to get services.
     *
     * @param  string   $method
     * @param  array    $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->app->{$method}(...$parameters);
    }
}
