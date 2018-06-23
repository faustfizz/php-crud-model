<?php
/**
 * Recursive autoloader class for namespaced model structure
 *
 * @category MVC Model
 * @package  maarsson/model
 * @author   Viktor Maarsson <viktor@maarsson.se>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Licence
 * @link     http://maarsson.se/model
 */

namespace Maarsson;

class Autoloader
{
    /**
     * Default file extension.
     * It can be overrided by: Autoloader::setExt();
     */
    protected static $_ext = '.php';

    /**
     * Main directory to be iterated for files.
     * It can be overrided by: Autoloader::setPath();
     */
    protected static $_path = array(__DIR__);

    /**
     * \RecursiveDirectoryIterator storage variable.
     */
    protected static $_pathIterator = [];


    /**
     * Go through the path directories for load and register class
     * if the namespaced name and the file path match.
     *
     * Underscores and slashes will be converted to directory separator.
     * All names will be converted to lowercase.
     *
     * @param (string) $class
     * @return (void)
     */
    public static function load($class)
    {
        $class = str_replace('_',  DIRECTORY_SEPARATOR, $class);
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        $filename = strtolower($class . static::$_ext);

        foreach (static::pathIterator() as $iterator) {
            foreach ($iterator as $file) {
                if (static::endsWith($filename,strtolower($file->getFilename()))) {
                    if ($file->isReadable()) {
                        include_once($file->getPathname());
                    }
                    break;
                }
            }
        }
    }


    /**
     * Recursively iterate path
     *
     * @return (object) RecursiveIteratorIterator
     */
    private static function pathIterator()
    {
        foreach (static::$_path as $path) {
            $directory = new \RecursiveDirectoryIterator(
                $path, \RecursiveDirectoryIterator::SKIP_DOTS
            );

            if (!isset(static::$_pathIterator[$path])) {
                static::$_pathIterator[$path] = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::LEAVES_ONLY);
            }
        }

        return static::$_pathIterator;
    }


    /**
     * Internal helper for path matching.
     *
     * @param (string) $haystack
     * @param (string) $needle
     * @return (bool)
     */
    private static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length === 0 || (substr($haystack, -$length) === $needle);
    }


    /**
     * Set different extension in place of default.
     *
     * @param (string) $ext
     * @return (void)
     */
    public static function setExt($ext)
    {
        static::$_ext = $ext;
    }

    /**
     * Set different top path in place of default.
     *
     * @param (string) $path
     * @return (void)
     */
    public static function addPath($path)
    {
        static::$_path[] = $path;
    }
}

// register this class as first, to be able to use it later for registering
spl_autoload_register('Maarsson\Autoloader::load');
