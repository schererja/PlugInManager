<?php

namespace Eden\PlugInManager;

use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\Finder\Finder;
use  Illuminate\Contracts\Foundation\Application;
class PluginManager
{
    private static ?PluginManager $instance = null;
    protected string $PluginDirectory;
    protected array $plugins = [];
    protected array $classMap = [];
    protected PluginExtender $PluginExtender;
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->PluginDirectory = $app->path() . DIRECTORY_SEPARATOR . 'Plugins';

        $this->PluginExtender = new PluginExtender($this, $app);
        $this->bootPlugins();
        $this->PluginExtender->extendAll();
        $this->registerClassLoader();
    }

    public static function getInstance(Application $app): ?PluginManager
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($app);
        }

        return self::$instance;
    }

    function getPluginClassNameFromDirectory(string $directory): string
    {
        return "App\\Plugins\\$directory\\$directory";
    }

    public function getClassMap(): array
    {
        return $this->classMap;
    }

    public function setClassMap(array $classMap): PluginManager
    {
        $this->classMap = $classMap;

        return $this;
    }

    public function addClassMapping(string $classNamespace, string $storagePath) : void
    {
        if(is_array($this->classMap)) {
            $this->classMap[$classNamespace] = $storagePath;
        }
    }

    /**
     * @return array
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @return string
     */
    public function getPluginDirectory(): string
    {
        return $this->PluginDirectory;
    }

    protected function bootPlugins() : void
    {
        if (!file_exists($this->PluginDirectory)) {
            mkdir($this->PluginDirectory);
        }
        $id = 1;
        foreach (Finder::create()->in($this->PluginDirectory)->directories()->depth(0) as $dir) {
            $directoryName = $dir->getBasename();
            $pluginClass = $this->getPluginClassNameFromDirectory($directoryName);

            if (!class_exists($pluginClass)) {
                dd('Plugin ' . $directoryName . ' needs a ' . $directoryName . ' Plugin class');
            }

            try {
                $Plugin = $this->app->makeWith($pluginClass, [$this->app]);
            } catch (BindingResolutionException $error) {
                dd('Plugin ' . $directoryName . ' could not be booted: "' . $error->getMessage() . '"');
            }

            if (!$Plugin instanceof Plugin) {
                dd('Plugin ' . $directoryName . ' must extends the Plugin Base Class');
            }

            $Plugin->boot();
            $Plugin->id = $id;
            $id++;
            $this->plugins[$Plugin->name] = $Plugin;

        }
    }

    private function registerClassLoader() : void
    {
        spl_autoload_register([new ClassLoader($this), 'loadClass'], true, true);
    }
}
