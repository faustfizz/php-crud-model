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

namespace Maarsson;

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
            $dsn = "mysql:host=".$_ENV['DB_HOST'].";port=".$_ENV['DB_PORT'].";dbname=".$_ENV['DB_DATABASE'].";charset=".$_ENV['DB_CHARSET'];
            $opt = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$db = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWD'], $opt);
        }
        return self::$db;
    }
}

