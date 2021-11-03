<?php

namespace Eden\PlugInManager;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;

abstract class Plugin
{
    protected $app;

    /**
     * The Plugin Name.
     *
     * @var string
     */
    public $name;
    public $uuid;
    public $description;

    public $version;

    private $reflector = null;

    public function __construct(Application $app)
    {
        $this->app = $app;


        $this->checkPluginName();

    }

    abstract public function boot();

    private function checkPluginName()
    {
        if (!$this->name) {
            throw new \InvalidArgumentException('Missing Plugin Name, please verify name exists');
        }
    }

    private function getViewNamespace(): string
    {
        return 'Plugin:' . Str::camel(
                mb_substr(
                    get_called_class(),
                    strpos(get_called_class(), '\\') + 1,
                    -6
                )
            );
    }

    protected function enableViews($path = 'views')
    {
        $this->app['view']->addNamespace(
            $this->getViewNamespace(),
            $this->getPluginPath() . DIRECTORY_SEPARATOR . $path
        );
    }

    protected function enableRoutes($path = 'routes.php', $middleware = 'web')
    {
        $this->app->router->group(
            [
                'namespace' => $this->getPluginControllerNamespace(),
                'middleware' => $middleware,
            ],
            function ($app) use ($path) {
                require $this->getPluginPath() . DIRECTORY_SEPARATOR . $path;
            }
        );
    }

    protected function enableMigrations($path = 'migrations')
    {
        $paths = [];
        $this->app->afterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array)$paths as $path) {
                $migrator->path($this->getPluginPath() . DIRECTORY_SEPARATOR . $path);
            }
        });
    }

    protected function enableTranslations($path = 'lang')
    {
        $this->app->afterResolving('translator', function ($translator) use ($path) {
            $translator->addNameSpace(
                $translator->getViewNamespace(),
                $this->getPluginPath() . DIRECTORY_SEPARATOR . $path
            );
        });
    }

    public function getPluginPath(): string
    {
        $reflector = $this->getReflector();
        $fileName = $reflector->getFileName();
        return dirname($fileName);
    }

    protected function getPluginControllerNamespace(): string
    {
        $reflector = $this->getReflector();
        $baseDir = str_replace($reflector->getShortName(), '', $reflector->getName());
        return $baseDir . 'Http\\Controllers';
    }

    private function getReflector(): \ReflectionClass
    {
        if (is_null($this->reflector)) {
            $this->reflector = new \ReflectionClass($this);
        }
        return $this->reflector;
    }

    protected function view($view): \Illuminate\View\View
    {
        return view($this->getViewNamespace() . '::' . $view);
    }

    protected function GUID(): string
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
}
