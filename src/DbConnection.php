<?php
/**
 * PDO extended database connector class
 * with table prefix/suffix options.
 *
 * @category MVC Model
 * @package  maarsson/model
 * @author   Viktor Maarsson <viktor@maarsson.se>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Licence
 * @link     http://maarsson.se/model
 */

namespace Maarsson;

use PDO;

class DbConnection extends PDO
{
    /**
     * For connection storage.
     */
    private static $db;

    /**
     * Prefix/suffix storage.
     */
    protected $_table_prefix;
    protected $_table_suffix;


    /**
     * The PDO constructor extended by prefix and suffix parameters.
     */
    public function __construct($dsn, $user = null, $password = null, $driver_options = array(), $prefix = null, $suffix = null)
    {
        $this->_table_prefix = $prefix;
        $this->_table_suffix = $suffix;
        parent::__construct($dsn, $user, $password, $driver_options);
    }


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
            $prefix = isset($_ENV['DB_PREFIX']) ? $_ENV['DB_PREFIX'] : null;
            $suffix = isset($_ENV['DB_SUFFIX']) ? $_ENV['DB_SUFFIX'] : null;
            self::$db = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWD'], $opt, $prefix, $suffix);
        }
        return self::$db;
    }


    /**
     * The equivalent PDO method,
     * but statement extends by prefix and suffix parameters.
     */
    public function exec($statement)
    {
        $statement = $this->_tablePrefixSuffix($statement);
        return parent::exec($statement);
    }


    /**
     * The equivalent PDO method,
     * but statement extends by prefix and suffix parameters.
     */
    public function prepare($statement, $driver_options = array())
    {
        $statement = $this->_tablePrefixSuffix($statement);
        return parent::prepare($statement, $driver_options);
    }


    /**
     * The equivalent PDO method,
     * but statement extends by prefix and suffix parameters.
     */
    public function query($statement)
    {
        $statement = $this->_tablePrefixSuffix($statement);
        $args      = func_get_args();

        if (count($args) > 1) {
            return call_user_func_array(array($this, 'parent::query'), $args);
        } else {
            return parent::query($statement);
        }
    }


    /**
     * Apply the prefix and suffix to the statement
     * but statement extends by prefix and suffix parameters.
     *
     * @param (string) $statement
     * @return (string) $statement
     */
    protected function _tablePrefixSuffix($statement)
    {
        return sprintf($statement, $this->_table_prefix, $this->_table_suffix);
    }
}
