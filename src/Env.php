<?php
/**
 * Simple INI file loader
 * Converts entries to constans, even with prefix.
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
     * Load the INI file and make uppercased constans for every variable.
     *
     * @param (string) $file
     * @param (string) $prefix
     * @return (void)
     */
    public static function parse($file, $prefix = null) {
        $prefix .= isset($prefix) ? '_' : null;

        $config = parse_ini_file($file);
        foreach ($config as $key => $value) {
            $constans = strtoupper($prefix.$key);
            define($constans,$value);
        }
    }
}
