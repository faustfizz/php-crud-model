<?php
/**
 * Database connector
 *
 * @category MVC Model
 * @package  maarsson/model
 * @author   Viktor Maarsson <viktor@maarsson.se>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Licence
 * @link     http://maarsson.se/model
 */

use PDO;

class DbConnection
{
    /**
     * For connection storage.
     */
    private static $db;

    /**
     * Build up the model according the data table structure,
     * and match the model data with the column types.
     *
     * @return (object) PDO
     */
    public static function init()
    {
        if (!self::$db)
        {
            $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_DATABASE.";charset=".DB_CHARSET;
            $opt = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$db = new PDO($dsn, DB_USER, DB_PASSWD, $opt);
        }
        return self::$db;
    }
}

