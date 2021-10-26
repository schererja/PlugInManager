<?php

namespace Eden\SlabManager;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;

abstract class Slab
{
    protected $app;

    /**
     * The Plugin Name.
     *
     * @var string
     */
    public $name;

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
            throw new \InvalidArgumentException('Missing Slab Name, please verify name exists');
        }
    }

    private function getViewNamespace(): string
    {
        return 'slab:' . Str::camel(
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
            $this->getSlabPath() . DIRECTORY_SEPARATOR . $path
        );
    }

    protected function enableRoutes($path = 'routes.php', $middleware = 'web')
    {
        $this->app->router->group(
            [
                'namespace' => $this->getSlabControllerNamespace(),
                'middleware' => $middleware,
            ],
            function ($app) use ($path) {
                require $this->getSlabPath() . DIRECTORY_SEPARATOR . $path;
            }
        );
    }

    protected function enableMigrations($path = 'migrations')
    {
        $paths = [];
        $this->app->afterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array)$paths as $path) {
                $migrator->path($this->getSlabPath() . DIRECTORY_SEPARATOR . $path);
            }
        });
    }

    protected function enableTranslations($path = 'lang')
    {
        $this->app->afterResolving('translator', function ($translator) use ($path) {
            $translator->addNameSpace(
                $translator->getViewNamespace(),
                $this->getSlabPath() . DIRECTORY_SEPARATOR . $path
            );
        });
    }

    public function getSlabPath(): string
    {
        $reflector = $this->getReflector();
        $fileName = $reflector->getFileName();
        return dirname($fileName);
    }

    protected function getSlabControllerNamespace(): string
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
}
