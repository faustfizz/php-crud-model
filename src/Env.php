<?php
/**
 * Enviroment variables loader using INI file
 *
 * @category MVC Model
 * @package  maarsson/model
 * @author   Viktor Maarsson <viktor@maarsson.se>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Licence
 * @link     http://maarsson.se/model
 */

namespace Maarsson;

class Env {

    /**
     * Internal storage
     */
    private static $parsedIni;


    /**
     * Load the INI file content to enviroment variables.
     *
     * @param (string) $file
     * @param (string) $prefix
     * @return (void)
     */
    public static function parse($file, $prefix = null) {
        if (self::load($file)) {
            self::parseArray(static::$parsedIni, $prefix);
        }
    }


    /**
     * Load to enviroment variables.
     *
     * @param (string) $file
     * @param (string) $prefix
     * @return (void)
     */
    private static function parseArray($array, $prefix) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                self::parseArray($value, self::prefix($prefix).$key);
            } else {
                $env = strtoupper(self::prefix($prefix).$key);
                putenv($env.'='.$value);
            }
        }
    }


    /**
     * Load the file.
     *
     * @param (string) $file
     * @return (bool)
     */
    private static function load($file) {
        if(file_exists($file)) {
            static::$parsedIni = parse_ini_file($file, true);
            return true;
        }
        return false;
    }


    /**
     * Prepare prefix.
     *
     * @param (string) $prefix
     * @return (string)
     */
    private static function prefix($prefix = null) {
        if(isset($prefix) && $prefix !== '') {
            return $prefix.'_';
        }
        return null;
    }

}
