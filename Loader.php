<?php

namespace Uwi\Loader;

use RuntimeException;

/**
 * ------------------------------------------------------------------------------------
 * Implementation of class autoloading for the Uwi Framework, corresponding to PSR-4
 * ------------------------------------------------------------------------------------
 *
 * @author Alexandr Shamanin <@slpAkkie>
 * @package uwi-loader
 *
 */
final class Loader
{
    /**
     * The name of the method that will be registered as a class autoloading function
     *
     * @var string
     */
    private const SPL_FUNC_NAME = 'loadClass';

    /**
     * PHP class file extension
     *
     * @var string
     */
    private const CLASS_FILE_EXT = '.php';

    /**
     * An array of aliases and route matches
     *
     * @var array
     */
    private static array $aliases = array();

    /**
     * Class Constructor
     *
     * Made private so that you cannot create an instance of the class in order to prevent misuse.
     */
    private function __construct()
    {
        // There is no need for a constructor
    }

    /**
     * Autoloader registration function
     *
     * @return void
     * @throws TypeError
     */
    public static function register(): void
    {
        spl_autoload_register(
            array(self::class, self::SPL_FUNC_NAME)
        );
    }

    /**
     * Adds a route for an alias
     *
     * @param string $alias
     * @param string $path
     * @param string $rootPath
     * @return void
     */
    private static function addAliasRoute(string $alias, string $path, string $rootPath = ''): void
    {
        if (in_array($path, self::$aliases[$alias])) {
            return;
        }

        self::$aliases[$alias][] = self::concatPath($rootPath, $path);
    }

    /**
     * Adds array of routes for an alias
     *
     * @param string $alias
     * @param array $path
     * @param string $rootPath
     * @return void
     */
    private static function addAliasRoutes(string $alias, array $paths, string $rootPath = ''): void
    {
        $paths = array_filter($paths, function ($path) use ($alias) {
            return !in_array($path, self::$aliases[$alias]);
        });

        $paths = array_map(fn($path) => self::concatPath($rootPath, $path), $paths);

        self::$aliases[$alias] = array_merge(self::$aliases[$alias], $paths);
    }

    /**
     * Public function for adding alias
     *
     * @param string $alias
     * @param string $path
     * @param string $rootPath
     * @return void
     */
    public static function addAlias(string $alias, string|array $path, string $rootPath = ''): void
    {
        if (key_exists($alias, self::$aliases)) {
            return;
        }

        self::$aliases[$alias] = array();

        if (is_array($path)) {
            self::addAliasRoutes($alias, $path, $rootPath);
        } else {
            self::addAliasRoute($alias, $path, $rootPath);
        }
    }

    /**
     * Public function for adding aliases
     *
     * @param array $aliases
     * @param string $rootPath
     * @return void
     */
    public static function addAliases(array $aliases, string $rootPath = ''): void
    {
        foreach ($aliases as $alias => $path) {
            self::addAlias($alias, $path, $rootPath);
        }
    }

    /**
     * Load class according to aliases
     *
     * @param string $class
     * @return void
     */
    public static function loadClass(string $class): void
    {
        foreach (self::$aliases as $alias => $paths) {
            if (!str_starts_with($class, $alias)) {
                continue;
            }

            foreach ($paths as $path) {
                $classPath = self::getClassPathWithAlias($class, $alias, $path);

                if (file_exists($classPath)) {
                    @require_once $classPath;
                    return;
                }
            }
        }

        $classPath = self::getClassPath($class);

        if (file_exists($classPath)) {
            @require_once $classPath;
        }
    }

    /**
     * Get the path to the class file according to alias path
     *
     * @param string $class
     * @param string $alias
     * @param string $path
     * @return string
     */
    private static function getClassPathWithAlias(string $class, string $alias, string $path): string
    {
        $classPath = str_replace(
            array($alias, '\\'),
            array($path, '/'),
            $class
        );

        $classPath .= self::CLASS_FILE_EXT;

        return $classPath;
    }

    /**
     * Get the path to the class file
     *
     * @param string $class
     * @return string
     */
    private static function getClassPath(string $class): string
    {
        $classPath = str_replace(
            array('\\'),
            array('/'),
            $class
        );

        $classPath .= self::CLASS_FILE_EXT;

        return $classPath;
    }

    /**
     * Connects two paths
     *
     * @param string $str1
     * @param string $str2
     * @return string
     */
    private static function concatPath(string $str1, string $str2): string
    {
        return rtrim($str1, '\\/') . '/' . trim($str2, '\\/');
    }
}
