<?php

namespace Eden\SlabManager;

use Symfony\Component\Finder\Finder;

class SlabManager
{
    private $app;

    private static $instance = null;

    protected $slabDirectory;

    protected $slabs = [];

    protected $classMap = [];

    protected $slabExtender;

    public function __construct($app)
    {
        $this->app = $app;
        $this->app = $app->path() . DIRECTORY_SEPARATOR . 'Plugins';
        $this->slabExtender = new Slab($this, $app);
        $this->bootSlabs();
        $this->slabExtender->extendAll();
        $this->registerClassLoader();
    }

    private function registerClassLoader()
    {
        spl_autoload_register([new ClassLoader($this), 'loadClass'], true, true);
    }


    public static function getInstance($app): ?SlabManager
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($app);
        }

        return self::$instance;
    }
    protected function bootSlabs()
    {
        foreach (Finder::create()->in($this->slabDirectory)->directories()->depth(0) as $dir) {
            $directoryName = $dir->getBasename();
            $slabClass = $this->getSlabClassNameFromDirectory($directoryName);
            if (!class_exists($slabClass)) {
                dd('Plugin ' . $directoryName . ' needs a ' . $directoryName . ' Plugin class');
            }

            try {
                $slab = $this->app->makeWith($slabClass, [$this->app]);
            }
            catch (\ReflectionException $error) {
                dd('Plugin ' . $directoryName . ' could not be booted: "' . $error->getMessage() . '"');
            }

            if (!$slab instanceof Slab) {
                dd('Plugin ' . $directoryName . ' must extends the Slab Base Class');
            }

            $slab->boot();

            $this->slabs[$slab->name] = $slab;

        }
    }

    function getSlabClassNameFromDirectory($directory): string
    {
        return "App\\Slabs\\${directory}\\${directory}Slab";
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
    public function setClassMap($classMap): SlabManager
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
    public function getSlabs(): array
    {
        return $this->slabs;
    }

    /**
     * @return string
     */
    public function getSlabDirectory(): string
    {
        return $this->slabDirectory;
    }
}
