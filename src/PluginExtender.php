<?php

namespace Eden\PlugInManager;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use function Composer\Autoload\includeFile;

class PluginExtender
{
    protected Application $app;

    protected PluginManager $pluginManager;

    protected string $classMapCacheFile;
    private string $directorySep = DIRECTORY_SEPARATOR;

    public function __construct(PluginManager $pluginManager, Application $app)
    {
        $this->pluginManager = $pluginManager;
        $this->app = $app;
        $this->classMapCacheFile = storage_path('plugins/classmap.json');
    }

    public function extendAll(): void
    {
        if (!$this->load()) {
            foreach ($this->pluginManager->getPlugins() as $plugin) {
                $this->extend($plugin, false);
            }

            $this->writeCache();
        }
    }

    public function extend(Plugin $plugin, bool $writeCache = true): void
    {
        foreach ($this->getAllPluginClasses($plugin) as $pluginClassPath) {
            $classNamespace = $this->getClassNamespaceFromFilename($pluginClassPath);
            if (is_string($classNamespace))
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
                    if(is_string($extendingClassNamespace)) {
                        if (class_exists($extendingClassNamespace)) {
                            $reflector = new ReflectionClass($extendingClassNamespace);


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
                    }
                } catch (ReflectionException $e) {
                    error_log("Failed while using extend function" . $e);
                    return;
                }
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

                $this->app['files']->put($storagePath, $newFileContent);
                if (is_string($classNamespace) && is_string($storagePath)) {

                    $this->pluginManager->addClassMapping($classNamespace, $storagePath);
                }
            }
        }
        if ($writeCache) {
            $this->writeCache();
        }
    }

    protected function removeLastBracket(array &$target): void
    {
        if (false !== $index = $this->getIndexForFirstOccurrence('}', array_reverse($target, true))) {
            unset($target[$index]);
        }
    }

    protected function getIndexForFirstOccurrence(string $search, array $subject): bool|int|string
    {
        foreach ($subject as $key => $value) {
            if (str_contains($value, $search)) {
                return $key;
            }
        }
        return false;
    }

    protected function insertAddMethods(array $methods, array &$fileContentExploded): void
    {
        $fileContentExploded = array_merge($fileContentExploded, [''], $methods);
    }

    protected function insertBeforeReturnMethods(array $methods, array $fileContentExploded): void
    {
        foreach ($methods as $method) {
            $methodExploded = explode(PHP_EOL, $method);
            $firstOccurrenceOfOpenBracket = $this->getIndexForFirstOccurrence('{', $methodExploded);
            $firstOccurrenceOfClosedBracket = $this->getIndexForFirstOccurrence('}', array_reverse($methodExploded, true));
            if (is_int($firstOccurrenceOfOpenBracket)) {
                $innerStart = $firstOccurrenceOfOpenBracket + 1;
            } else {
                return;
            }
            if (is_int($firstOccurrenceOfClosedBracket)) {
                $innerEnd = $firstOccurrenceOfClosedBracket - 1;
            } else {
                return;
            }


            $inner = array_slice($methodExploded, $innerStart, $innerEnd - $innerStart + 1);
            if (false == $methodName = $this->getMethodNameFromString($method)) {
                continue;
            }
            if (is_string($methodName)) {
                $originalMethodStartIndex = $this->getIndexForFirstOccurrence($methodName, $fileContentExploded);
                if (is_int($originalMethodStartIndex)) {

                    $originalMethodEndIndex = $this->getIndexForFirstOccurrence('return',
                        array_slice($fileContentExploded,
                            $originalMethodStartIndex,
                            null,
                            true
                        ));

                    $fileContentBeforeReturn = array_slice($fileContentExploded, 0, $originalMethodStartIndex);
                    if (is_int($originalMethodEndIndex)) {
                        $fileContentAfterReturn = array_slice($fileContentExploded, $originalMethodEndIndex);
                        $fileContentExploded = array_merge($fileContentBeforeReturn, $inner, [''], $fileContentAfterReturn);
                    }

                }
            }

        }
    }

    protected function getMethodNameFromString(string $string): bool|string
    {
        preg_match('/.*function\s+(.*)\(.*/i', $string, $methodName);

        return $methodName[1] ?? false;
    }

    protected function insertConstants(array $constants, array &$fileContentExploded): void
    {
        $lastConstantIndex = $this->getIndexForFirstOccurrence('const', array_reverse($fileContentExploded, true));

        if ($lastConstantIndex === false) {
            $lastConstantIndex = $this->getIndexForFirstOccurrence('{', $fileContentExploded);
        }
        if ($lastConstantIndex !== false) {
            if (is_int($lastConstantIndex)) {
                $before = array_slice($fileContentExploded, 0, $lastConstantIndex + 1);
                $after = array_slice($fileContentExploded, $lastConstantIndex + 1);
            } else {
                error_log("Unable to do array_slice on file content exploded.");
                return;
            }
            $fileContentExploded = $before;

            foreach ($constants as $constantName => $constantValue) {
                if (is_bool($constantValue)) {
                    $constantValue = $constantValue ? 'true' : 'false';
                } elseif (is_array($constantValue)) {
                    $constantValue = '[' . implode(',', $constantValue) . ']';
                }
                $fileContentExploded[] = '    const ' . $constantName . ' = ' . $constantValue . ';';
            }

            $fileContentExploded = array_merge($fileContentExploded, $after);
        }
    }

    protected function collectMethodsOfType(string $type, string $fileContent, ReflectionClass $reflector): array
    {
        $methods = [];
        $fileContentExploded = explode(PHP_EOL, $fileContent);

        foreach ($reflector->getMethods() as $reflectionMethod) {
            $doc = $reflectionMethod->getDocComment();
            if (is_string($doc)) {
                if (str_contains($doc, '@' . $type)) {
                    $methods[] = $this->getMethodString($reflectionMethod, $fileContentExploded);
                }
            }
        }
        return $methods;
    }

    #[Pure] protected function collectConstants(ReflectionClass $class): array
    {
        return $class->getConstants();
    }

    #[Pure] protected function getMethodString(ReflectionMethod $method, array $splitContent): string
    {
        $methodString = '';

        for ($i = $method->getStartLine(); $i <= $method->getEndLine(); $i++) {
            $methodString .= $splitContent[$i - 1] . PHP_EOL;
        }
        return $methodString;
    }

    protected function load(): bool
    {
        if (!env('PLUGIN_CACHE', true)) {
            return false;
        }

        try {
            $jsonDecoded = json_decode($this->app['files']->get($this->classMapCacheFile), true);
            if (is_array($jsonDecoded)) {
                $this->pluginManager->setClassMap($jsonDecoded);
            }
        } catch (FileNotFoundException $error) {
            error_log($error);
            return false;
        }

        return true;
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

    protected function fileContainsClass(string $file): bool
    {
        $tokens = token_get_all($file);
        foreach ($tokens as $idx => $token) {

            if (is_array($token)) {
                $tokenFound = $tokens[$idx - 1][0];
                if (is_int($tokenFound)) {
                    if (token_name($token[0]) == 'T_CLASS' && token_name($tokenFound) != 'T_DOUBLE_COLON') {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    protected function getClassNamespaceFromFilename(string $file): array|string
    {
        $namespace = str_replace($this->pluginManager->getPluginDirectory(), 'App\plugins', $file);
        $namespace = str_replace('/', '\\', $namespace);
        return str_replace('.php', '', $namespace);
    }

    protected function classExists(string $class, bool $autoload = false): bool
    {
        return class_exists($class, $autoload);
    }

    protected function getExtensionFilesFromFile(string $file, Plugin $plugin): array
    {
        $files = [];
        $classPath = str_replace($this->pluginManager->getPluginDirectory(), $this->directorySep . 'plugins', $file);
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


    protected function getExtendedClassStoragePath(string $file): string|null
    {
        $classNamespace = $this->getClassNamespaceFromFilename($file);
        if ($classNamespace != null) {
            $replacedString = str_replace('\\', '_', $classNamespace);
            $path = `plugins$this->directorySep$replacedString.php`;
            return storage_path(
                $path
            );
        }
        return null;
    }

    /**
     * @return mixed
     */
    protected function writeCache(): mixed
    {
        if (!file_exists($this->classMapCacheFile)) {
            mkdir(storage_path('plugins/'));
            fopen($this->classMapCacheFile, 'w');

        }
        return $this->app['files']->put($this->classMapCacheFile, json_encode($this->pluginManager->getClassMap()));

    }
}
