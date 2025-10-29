<?php

namespace Schererja\PlugInManager;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;

class PluginManager
{
    private static ?PluginManager $instance = null;

    protected string $pluginDirectory;

    protected string $pluginNamespace;

    protected string $pluginClassSuffix;

    protected string $errorMode;

    protected bool $autoCreateDirectory;

    protected array $plugins = [];

    protected array $classMap = [];

    protected PluginExtender $pluginExtender;

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->loadConfiguration();
        $this->initializePluginDirectory();

        $this->pluginExtender = new PluginExtender($this, $app);
        $this->bootPlugins();
        $this->pluginExtender->extendAll();
        $this->registerClassLoader();
    }

    public static function getInstance(Application $app): ?PluginManager
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($app);
        }

        return self::$instance;
    }

    private function loadConfiguration(): void
    {
        $config = $this->app->make('config');

        $pluginDirectory = $config->get('plugin-manager.plugin_directory', 'app/Plugins');
        $this->pluginDirectory = $this->app->basePath($pluginDirectory);

        $this->pluginNamespace = $config->get('plugin-manager.plugin_namespace', 'App\\Plugins');
        $this->pluginClassSuffix = $config->get('plugin-manager.plugin_class_suffix', 'Plugin');
        $this->errorMode = $config->get('plugin-manager.error_mode', 'exception');
        $this->autoCreateDirectory = $config->get('plugin-manager.auto_create_directory', true);
    }

    private function initializePluginDirectory(): void
    {
        if (! file_exists($this->pluginDirectory) && $this->autoCreateDirectory) {
            mkdir($this->pluginDirectory, 0755, true);
        }
    }

    public function getPluginClassNameFromDirectory(string $directory): string
    {
        return $this->pluginNamespace."\\$directory\\$directory".$this->pluginClassSuffix;
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

    public function addClassMapping(string $classNamespace, string $storagePath): void
    {
        if (is_array($this->classMap)) {
            $this->classMap[$classNamespace] = $storagePath;
        }
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function getPluginDirectory(): string
    {
        return $this->pluginDirectory;
    }

    public function getPluginNamespace(): string
    {
        return $this->pluginNamespace;
    }

    public function getPluginClassSuffix(): string
    {
        return $this->pluginClassSuffix;
    }

    protected function bootPlugins(): void
    {
        if (! file_exists($this->pluginDirectory)) {
            return;
        }

        $id = 1;
        foreach (Finder::create()->in($this->pluginDirectory)->directories()->depth(0) as $dir) {
            $directoryName = $dir->getBasename();
            $pluginClass = $this->getPluginClassNameFromDirectory($directoryName);

            if (! class_exists($pluginClass)) {
                $this->handleError("Plugin '$directoryName' needs a '$directoryName{$this->pluginClassSuffix}' class");

                continue;
            }

            try {
                $Plugin = new $pluginClass($this->app);
            } catch (\Exception $error) {
                $this->handleError("Plugin '$directoryName' could not be booted: \"{$error->getMessage()}\"");

                continue;
            }

            if (! $Plugin instanceof Plugin) {
                $this->handleError("Plugin '$directoryName' must extend the Plugin base class");

                continue;
            }

            $Plugin->boot();
            $Plugin->id = $id;
            $id++;
            $this->plugins[$Plugin->name] = $Plugin;
        }
    }

    private function handleError(string $message): void
    {
        switch ($this->errorMode) {
            case 'exception':
                throw new \RuntimeException($message);
            case 'log':
                Log::error("PluginManager: $message");
                break;
            case 'silent':
                // Do nothing
                break;
            default:
                throw new \RuntimeException($message);
        }
    }

    private function registerClassLoader(): void
    {
        spl_autoload_register([new ClassLoader($this), 'loadClass'], true, true);
    }
}
