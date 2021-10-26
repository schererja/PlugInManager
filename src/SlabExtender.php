<?php

namespace Eden\SlabManager;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class SlabExtender
{
    protected $app;

    protected $slabManager;

    protected $classMapCacheFile;

    public function __construct(SlabManager $slabManager, $app)
    {
        $this->app = $app;
        $this->classMapCacheFile = storage_path('plugins/classmap.json');
    }

    public function extendAll()
    {
        if (!$this->load()) {
            foreach ($this->slabManager->getSlabs() as $slab) {
                $this->extend($slab, false);
            }

            $this->writeCache();
        }
    }

    public function extend(Plugin $plugin, $writeCache = true)
    {
    }

    protected function insertConstants(array $constants, array &$fileContentExploded){
        $lastConstantIndex = $this->getIndexForFirstOccurrence('const', array_reverse($fileContentExploded, true));
    }
    protected function collectMethodsOfType($type, $fileContent, \ReflectionClass $reflector)
    {
        $methods = [];
        $fileContentExploded = explode(PHP_EOL, $fileContent);

        foreach ($reflector->getMethods() as $reflectionMethod) {
            $methods[] = $this->getMethodString($reflectionMethod, $fileContentExploded);
        }


        return $methods;
    }

    protected function collectConstants(\ReflectionClass $class)
    {
        return $reflector->getConstants();
    }

    protected function getMethodString(\ReflectionMethod $method, array $splittedContent)
    {
        $methodString = '';

        for($i = $method->getStartLine(); $i <= $method->getEndLine(); $i++) {
            $methodString .= $splittedContent[$i -1]. PHP_EOL;
        }
    }

    protected function getAllPluginClasses(Slab $slab): array
    {
        $files = [];
        foreach ($this->app['files']->allFiles($slab->getSlabPath()) as $file) {
            if (!$this->fileContainsClass($file->getContents())) {
                continue;
            }
            $files[] = $file->getPathname();
        }
        return $files;
    }

    protected function getExtensionFilesFromFile($file, Slab $slab): array
    {
        $files = [];
        $classPath = str_replace($this->slabManager->getSlabDirectory(), DIRECTORY_SEPARATOR . 'Slabs', $file);
        foreach ($this->slabManager->getSlabs() as $otherSlab) {
            if ($slab->name == $otherSlab->name) {
                continue;
            }

            $extensionFilePath = $otherSlab->getSlabPath() . $classPath;

            if (!$this->app['files']->exists($extensionFilePath)) {
                continue;
            }
            $files[] = $extensionFilePath;

        }

        return $files;
    }

    protected function fileContainsClass($file): bool
    {
        $tokens = token_get_all($file);
        foreach ($tokens as $idx => $token) {
            if (is_array($token)) {
                if (token_name($token[0]) == 'T_CLASS' && token_name($tokens[$idx - 1][0]) != 'T_DOUBLE_COLON') {
                    return true;
                }
            }
        }
        return false;
    }

    protected function getClassNamespaceFromFilename($file)
    {
        $namespace = str_replace($this->slabManager->getSlabDirectory(), 'App\Slabs', $file);
        $namespace = str_replace('/', '\\', $namespace);
        return str_replace('.php', '', $namespace);
    }


    protected function getExtendedClassStoragePath($file)
    {
        $classNamespace = $this->getClassNamespaceFromFilename($file);
    }

    protected function classExists($class, $autoload = false): bool
    {
        return class_exists($class, $autoload);
    }

    /**
     * @return bool
     */
    protected function load(): bool
    {
        if (!env('PLUGIN_CACHE', true)) {
            return false;
        }

        try {
            $this->slabManager->setClassMap(json_decode($this->app['files']->get($this->classMapCacheFile), true));
        } catch (FileNotFoundException $error) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    protected function writeCache()
    {
        return $this->app['files']->put($this->classMapCacheFile, json_encode($this->slabManager->getClassMap()));
    }
}
