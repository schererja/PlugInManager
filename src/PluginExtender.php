<?php

namespace Eden\PlugInManager;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use function Composer\Autoload\includeFile;

class PluginExtender
{
    protected $app;

    protected $pluginManager;

    protected $classMapCacheFile;

    public function __construct(PluginManager $pluginManager, $app)
    {
        $this->pluginManager = $pluginManager;
        $this->app = $app;
        $this->classMapCacheFile = storage_path('plugins/classmap.json');
    }

    public function extendAll()
    {
        if (!$this->load()) {
            foreach ($this->pluginManager->getPlugins() as $plugin) {
                $this->extend($plugin, false);
            }

            $this->writeCache();
        }
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
            $this->pluginManager->setClassMap(json_decode($this->app['files']->get($this->classMapCacheFile), true));
        } catch (FileNotFoundException $error) {
            return false;
        }

        return true;
    }

    public function extend(Plugin $plugin, $writeCache = true)
    {
        foreach ($this->getAllPluginClasses($plugin) as $pluginClassPath) {
            $classNamespace = $this->getClassNamespaceFromFilename($pluginClassPath);
            if ($this->classExists($classNamespace)) {
                continue;
            }

            $addMethods = [];
            $beforeReturnMethods = [];
            $constants = [];
            foreach ($this->getExtensionFilesFromFile($pluginClassPath, $plugin) as $file) {
                includeFile($file);
                $extendingClassNamespace = $this->getClassNamespaceFromFilename($file);

                $fileContent = $this->app['files']->get($file);
                try {
                    $reflector = new ReflectionClass($extendingClassNamespace);
                } catch (ReflectionException $e) {
                    return;
                }

                $addMethods = array_merge(
                    $addMethods,
                    $this->collectMethodsOfType('add', $fileContent, $reflector)
                );

                $beforeReturnMethods = array_merge(
                    $beforeReturnMethods,
                    $this->collectMethodsOfType('beforeReturn', $fileContent, $reflector)
                );

                $constants = array_merge(
                    $constants,
                    $this->collectConstants($reflector)
                );
            }
            if ($addMethods || $beforeReturnMethods || $constants) {
                $originalFileContent = $this->app['files']->get($pluginClassPath);
                $originalFileContentExploded = explode(PHP_EOL, $originalFileContent);

                $this->removeLastBracket($originalFileContentExploded);
                if ($addMethods) {
                    $this->insertAddMethods($addMethods, $originalFileContentExploded);
                }

                if ($beforeReturnMethods) {
                    $this->insertBeforeReturnMethods($beforeReturnMethods, $originalFileContentExploded);
                }

                if ($constants) {
                    $this->insertConstants($constants, $originalFileContentExploded);
                }

                $originalFileContentExploded[] = '}';

                $newFileContent = implode(PHP_EOL, $originalFileContentExploded);
                $storagePath = $this->getExtendedClassStoragePath($pluginClassPath);

                $this->pluginManager->addClassMapping($classNamespace, $storagePath);
            }
        }
        if ($writeCache) {
            $this->writeCache();
        }
    }

    protected function getAllPluginClasses(Plugin $plugin): array
    {
        $files = [];
        foreach ($this->app['files']->allFiles($plugin->getPluginPath()) as $file) {
            if (!$this->fileContainsClass($file->getContents())) {
                continue;
            }
            $files[] = $file->getPathname();
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
        $namespace = str_replace($this->pluginManager->getPluginDirectory(), 'App\plugins', $file);
        $namespace = str_replace('/', '\\', $namespace);
        return str_replace('.php', '', $namespace);
    }

    protected function classExists($class, $autoload = false): bool
    {
        return class_exists($class, $autoload);
    }

    protected function getExtensionFilesFromFile($file, Plugin $plugin): array
    {
        $files = [];
        $classPath = str_replace($this->pluginManager->getPluginDirectory(), DIRECTORY_SEPARATOR . 'plugins', $file);
        foreach ($this->pluginManager->getPlugins() as $otherPlugin) {
            if ($plugin->name == $otherPlugin->name) {
                continue;
            }

            $extensionFilePath = $otherPlugin->getpluginPath() . $classPath;

            if (!$this->app['files']->exists($extensionFilePath)) {
                continue;
            }
            $files[] = $extensionFilePath;

        }

        return $files;
    }

    protected function collectMethodsOfType($type, $fileContent, ReflectionClass $reflector): array
    {
        $methods = [];
        $fileContentExploded = explode(PHP_EOL, $fileContent);

        foreach ($reflector->getMethods() as $reflectionMethod) {
            $methods[] = $this->getMethodString($reflectionMethod, $fileContentExploded);
        }


        return $methods;
    }

    protected function getMethodString(ReflectionMethod $method, array $splittedContent)
    {
        $methodString = '';

        for ($i = $method->getStartLine(); $i <= $method->getEndLine(); $i++) {
            $methodString .= $splittedContent[$i - 1] . PHP_EOL;
        }
    }

    protected function collectConstants(ReflectionClass $class)
    {
        return $reflector->getConstants();
    }

    protected function removeLastBracket(array &$target)
    {
        if (false !== $index = $this->getIndexForFirstOccurrence('}', array_reverse($target, true))) {
            unset($target[$index]);
        }
    }

    protected function getIndexForFirstOccurrence($search, array $subject)
    {

        foreach ($subject as $key => $value) {
            if (strpos($value, $search) !== false) {
                return $key;
            }
        }
        return false;
    }

    protected function insertAddMethods(array $methods, array &$fileContentExploded)
    {
        $fileContentExploded = array_merge($fileContentExploded, [''], $methods);
    }

    protected function insertBeforeReturnMethods(array $methods, array $fileContentExploded)
    {
        foreach ($methods as $method) {
            $methodExploded = explode(PHP_EOL, $method);
            $innerStart = $this->getIndexForFirstOccurrence('{', $methodExploded) + 1;
            $innerEnd = $this->getIndexForFirstOccurrence('}', array_reverse($methodExploded, true)) - 1;
            $inner = array_slice($methodExploded, $innerStart, $innerEnd - $innerStart + 1);
            if (false == $methodName = $this->getMethodNameFromString($method)) {
                continue;
            }

            $originalMethodStartIndex = $this->getIndexForFirstOccurrence($methodName, $fileContentExploded);
            $originalMethodEndIndex = $this->getIndexForFirstOccurrence('return', array_slice($fileContentExploded, null, true));
            $fileContentBeforeReturn = array_slice($fileContentExploded, 0, $originalMethodStartIndex);
            $fileContentAfterReturn = array_slice($fileContentExploded, $originalMethodEndIndex);
            $fileContentExploded = array_merge($fileContentBeforeReturn, $inner, [''], $fileContentAfterReturn);

        }
    }

    protected function getMethodNameFromString($string)
    {
        preg_match('/.*function\s+(.*)\(.*/i', $string, $methodName);
        return $methodName[1] ?? null;
    }

    protected function insertConstants(array $constants, array &$fileContentExploded)
    {
        $lastConstantIndex = $this->getIndexForFirstOccurrence('const', array_reverse($fileContentExploded, true));
    }

    protected function getExtendedClassStoragePath($file)
    {
        $classNamespace = $this->getClassNamespaceFromFilename($file);
    }

    /**
     * @return mixed
     */
    protected function writeCache()
    {
        if (!file_exists($this->classMapCacheFile)) {
            mkdir(storage_path('plugins/'));
            fopen($this->classMapCacheFile, 'w')
            or die("Failed to create the file needed: " . $this->classMapCacheFile);

        }
        return $this->app['files']->put($this->classMapCacheFile, json_encode($this->pluginManager->getClassMap()));

    }
}
