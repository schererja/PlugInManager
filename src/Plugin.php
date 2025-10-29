<?php

namespace Schererja\PlugInManager;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\View\View;
use InvalidArgumentException;
use ReflectionClass;

abstract class Plugin
{
    public int $id;

    public string $name;

    public string $uuid;

    public string $description;

    public string $version;

    protected Application $app;

    private ReflectionClass $reflector;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->checkPluginName();

    }

    abstract public function boot(): void;

    public function getPluginPath(): string
    {
        $reflector = $this->getReflector();
        $fileName = $reflector->getFileName();
        if (is_string($fileName)) {
            return dirname($fileName);
        }

        return '';
    }

    protected function enableViews(string $path = 'views'): void
    {
        $this->app['view']->addNamespace(
            $this->getViewNamespace(),
            $this->getPluginPath().DIRECTORY_SEPARATOR.$path
        );
    }

    protected function enableRoutes(string $path = 'routes.php', string $middleware = 'web'): void
    {
        $this->app->router->group(
            [
                'namespace' => $this->getPluginControllerNamespace(),
                'middleware' => $middleware,
            ],
            function ($app) use ($path) {
                require $this->getPluginPath().DIRECTORY_SEPARATOR.$path;
            }
        );
    }

    protected function enableMigrations(string $paths = 'migrations'): void
    {
        $this->app->afterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array) $paths as $path) {
                $migrator->path($this->getPluginPath().DIRECTORY_SEPARATOR.$path);
            }

        });
    }

    protected function enableTranslations(string $path = 'lang'): void
    {
        $this->app->afterResolving('translator', function ($translator) use ($path) {
            $translator->addNameSpace(
                $translator->getViewNamespace(),
                $this->getPluginPath().DIRECTORY_SEPARATOR.$path
            );
        });
    }

    protected function getPluginControllerNamespace(): string
    {
        $reflector = $this->getReflector();
        $baseDir = str_replace($reflector->getShortName(), '', $reflector->getName());

        return $baseDir.'Http\\Controllers';
    }

    protected function view(string $view): View
    {
        $viewNameSpace = $this->getViewNamespace().'::'.$view;

        return view($viewNameSpace);
    }

    protected function GUID(): string
    {
        if (function_exists('com_create_guid') === true) {
            if (is_string(com_create_guid())) {
                return trim(com_create_guid(), '{}');
            }
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    private function checkPluginName(): void
    {
        if (! $this->name) {
            throw new InvalidArgumentException('Missing Plugin Name, please verify name exists');
        }
    }

    private function getViewNamespace(): string
    {
        $config = $this->app->make('config');
        $prefix = $config->get('plugin-manager.view_namespace_prefix', 'plugin');
        $suffix = $config->get('plugin-manager.plugin_class_suffix', 'Plugin');

        $className = get_called_class();
        $pluginName = mb_substr(
            $className,
            strrpos($className, '\\') + 1,
            -strlen($suffix)
        );

        return $prefix.':'.strtolower($pluginName);
    }

    private function getReflector(): ReflectionClass
    {
        $this->reflector = new ReflectionClass($this);

        return $this->reflector;
    }
}
