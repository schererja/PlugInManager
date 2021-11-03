<?php

namespace Eden\PlugInManager;

use ReflectionException;
use Symfony\Component\Finder\Finder;

class PluginManager
{
    private $app;

    private static $instance = null;

    protected $PluginDirectory;

    protected $plugins = [];

    protected $classMap = [];

    protected $PluginExtender;

    public function __construct($app)
    {
        $this->app = $app;
        $this->PluginDirectory = $app->path() . DIRECTORY_SEPARATOR . 'Plugins';

        $this->PluginExtender = new PluginExtender($this, $app);
        $this->bootPlugins();
        $this->PluginExtender->extendAll();
        $this->registerClassLoader();
    }

    private function registerClassLoader()
    {
        spl_autoload_register([new ClassLoader($this), 'loadClass'], true, true);
    }


    public static function getInstance($app): ?PluginManager
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($app);
        }

        return self::$instance;
    }

    protected function bootPlugins()
    {
        foreach (Finder::create()->in($this->PluginDirectory)->directories()->depth(0) as $dir) {
            $directoryName = $dir->getBasename();
            $pluginClass = $this->getPluginClassNameFromDirectory($directoryName);

            if (!class_exists($pluginClass)) {
                dd('Plugin ' . $directoryName . ' needs a ' . $directoryName . ' Plugin class');
            }

            try {

                $Plugin = $this->app->makeWith($pluginClass, [$this->app]);
            } catch (ReflectionException $error) {
                dd('Plugin ' . $directoryName . ' could not be booted: "' . $error->getMessage() . '"');
            }

            if (!$Plugin instanceof Plugin) {
                dd('Plugin ' . $directoryName . ' must extends the Plugin Base Class');
            }

            $Plugin->boot();

            $this->plugins[$Plugin->name] = $Plugin;

        }
    }

    function getPluginClassNameFromDirectory($directory): string
    {

        return "App\\Plugins\\${directory}\\${directory}";
    }

    /**
     * @return array
     */
    public function getClassMap(): array
    {
        return $this->classMap;
    }

    /**
     * @param $classMap
     * @return $this
     */
    public function setClassMap($classMap): PluginManager
    {
        $this->classMap = $classMap;

        return $this;
    }

    /**
     * @param $classNamespace
     * @param $storagePath
     */
    public function addClassMapping($classNamespace, $storagePath)
    {
        $this->classMap[$classNamespace] = $storagePath;
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
}
