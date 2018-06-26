<?php
/**
 * Abstract model for base CRUD functions
 *
 * @category MVC Model
 * @package  maarsson/model
 * @author   Viktor Maarsson <viktor@maarsson.se>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Licence
 * @link     http://maarsson.se/model
 */

namespace Maarsson;

abstract class Model
{
    /**
     * ID field is mandatory for the model.
     */
    protected $id;

    /**
     * Table name matches with lowercase model name by default.
     * It can be overrided in the given model by:
     *
     *      protected static $_table = 'my_custom_table';
     */
    protected static $_table;

    /**
     * Model uses soft deleting by default.
     * Deleted rows does not delete from database
     * but only flagged as deleted.
     *
     * It can be overrided in the given model by:
     *
     *      protected static $_soft_deletes = false;
     */
    protected static $_soft_deletes = true;

    /**
     * Model do timestamping by default.
     *
     * It can be disabled in the given model by:
     *
     *      protected static $_timestamps = false;
     */
    protected static $_timestamps   = true;

    /**
     * Default column names for timestamping
     * and for soft deletes.
     *
     * These can be overrided in the given model by:
     *
     *      protected static $_deleted_at = 'my_custom_column';
     *      protected static $_created_at = 'my_custom_column';
     *      protected static $_updated_at = 'my_custom_column';
     */
    protected static $_deleted_at   = 'deleted_at';
    protected static $_created_at   = 'created_at';
    protected static $_updated_at   = 'updated_at';

    /**
     * Table info storage variable for internal use.
     */
    protected static $_tableInfo      = null;

    /**
     * \PDO error storage variable for future possibilities.
     */
    protected static $_statementError = null;

    /**
     * For defining relationships
     *
     * Use with arrays:
     *
     *      protected static $_hasMany = [
     *           'properties1' => 'Other_Model_1',
     *           'properties2' => 'Other_Model_2',
     *      ];
     *
     */
    protected static $_hasOne        = null;
    protected static $_hasMany       = null;
    protected static $_belongsTo     = null;
    protected static $_belongsToMany = null;


    /**
     * Build up the model according the data table structure,
     * and match the model data with the column types.
     *
     * @param (array) $properties
     * @return (object) Model instance
     *
     * @todo match types in separated method
     * @todo match all possible types
     * @todo define and use foreign and local keys for relationships
     * @todo infinity loop avoiding still exists in some relationship cases (belongTo-belongsToMany)
     */
    public function __construct(Array $properties = null)
    {
        // build up available properties
        // and try to match types
        foreach (self::getFields() as $property) {
            $type = self::getFieldType($property);
            $type = explode('(',$type);
            $value = $properties[$property];
            switch ($type[0]) {
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'bigint':
                    $value = (int)$value;
                    break;
                case 'char':
                case 'varchar':
                case 'tinytext':
                case 'text':
                case 'mediumtext':
                case 'longtext':
                    $value = (string)$value;
                    break;
                case 'double':
                case 'float':
                case 'decimal':
                    $value = (float)$value;
                    break;
                default:
                    break;
            }
            $this->{$property} = $value;
        }

        // load relations as properties
        if(count(static::$_hasOne)) {
            foreach (static::$_hasOne as $property => $class) {
                $this->{$property} = $this->hasOne($class);
            }
        }

        if(count(static::$_hasMany)) {
            foreach (static::$_hasMany as $property => $class) {
                $this->{$property} = $this->hasMany($class);
            }
        }

        if(count(static::$_belongsTo)) {
            foreach (static::$_belongsTo as $property => $class) {
                // exclude vica-versa relations, to avoid infinity loop
                if ((isset($class::$_hasOne) && in_array(static::class, $class::$_hasOne)) || (isset($class::$_hasMany)  && in_array(static::class, $class::$_hasMany))) {
                    continue;
                }
                $this->{$property} = $this->belongsTo($class);
            }
        }

        if(count(static::$_belongsToMany)) {
            foreach (static::$_belongsToMany as $property => $class) {
                // exclude vica-versa relations, to avoid infinity loop
                if ((isset($class::$_hasOne) && in_array(static::class, $class::$_hasOne)) || (isset($class::$_hasMany)  && in_array(static::class, $class::$_hasMany))) {
                    continue;
                }
                $this->{$property} = $this->belongsToMany($class);
            }
        }
    }


    /**
     * Get static property values.
     * Used mainly for configurations.
     *
     * @param (string) $property
     * @return (mixed) Value of property
     * @return (bool)false if property not exists
     *
     * @todo only for variables start with underscore
     */
    protected static function _self($property)
    {
        // separated return for table name
        // since its name depends on model
        if ($property == '_table') {
            if (isset(static::$_table)) {
                return static::$_table;
            } else {
                return strtolower((new \ReflectionClass(get_called_class()))->getShortName());
            }
        }

        // for the other properties
        if (property_exists(get_called_class(), $property)) {
            return static::${$property};
        }
        return false;
    }


    /**
     * Build up database connection.
     *
     * @return (object) PDO
     */
    protected static function db()
    {
        return DbConnection::init();
    }


    /**
     * Get data table column info.
     *
     * @return (array)
     */
    protected static function getTableInfo()
    {
        if (!static::$_tableInfo OR static::$_tableInfo['table'] !== self::_self('_table')) {
            $stmt = self::db()->prepare(
                "DESCRIBE ".self::_self('_table').";"
            );
            $stmt->execute();
            static::$_tableInfo = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            static::$_tableInfo['table'] = self::_self('_table');
      }
        return static::$_tableInfo;
    }


    /**
     * Get model property fields by data table.
     *
     * @return (array) of available columns
     */
    public static function getFields()
    {
        $fields = [];
        foreach (self::getTableInfo() as $column) {
            if (is_array($column)) {
                $fields[] = $column['Field'];
            }
        }
        return $fields;
    }


    /**
     * Get type of model property.
     *
     * @param (string) $field
     * @return (mixed) SQL type of property
     * @return (bool)false if property not exists
     */
    protected static function getFieldType($field)
    {
        foreach (self::getTableInfo() as $column) {
            if ($column['Field'] == $field) {
                return($column['Type']);
            }
        }
        return false;
    }


    /**
     * Get protected property values for external use.
     *
     * @param (string) $property
     * @return (mixed) value of property
     * @return (bool)false if property not exists
     */
    public function _get($property)
    {
        if (property_exists(get_called_class(), $property)) {
            return $this->{$property};
        }
        return false;
    }


    /**
     * Search for models matching with properties.
     *
     * @param (array) $properties
     * @param (array) $orderBy
     * @return (array) of Models
     * @return (null) if no match
     */
    public static function where(Array $properties = null, $orderBy = null)
    {
        // set condition for soft deletes
        if(self::_self('_soft_deletes')) {
            $properties[] = [self::_self('_deleted_at'),'=', '0'];
        }

        // prepare data
        // use only the available properties
        $conditions = [];
        $parameters = [];
        foreach ($properties as $field => $condition) {
            if (is_array($condition) && in_array($condition[0], self::getFields())) {
                $parameters[] = $condition[2];
                $condition[2] = '?';
                $conditions[] = $condition;
            } else if (in_array($field, self::getFields())) {
                $conditions[] = [$field,'=','?'];
                $parameters[] = $condition;
            }
        }

        // build up query
        $preparedConditions = [];
        $query = "SELECT * FROM ".self::_self('_table');
        if (count($conditions)) {
            $query .= " WHERE ";
            foreach ($conditions as $condition) {
                $preparedConditions[] = implode(' ',$condition);
            }
        }
        $query .= implode(' AND ',$preparedConditions);
        if ($orderBy) {
            if (is_array($orderBy)) {
                $preparedOrderBy = [];
                foreach ($orderBy as $property => $order) {
                    $preparedOrderBy[] = $property.' '.strtoupper( $order);
                }
                $query .= " ORDER BY ".implode(', ',$preparedOrderBy);
            } elseif (in_array($orderBy, self::getFields())) {
                $query .= " ORDER BY ".$orderBy." ASC";
            }
        }

        // run query
        $stmt = self::db()->prepare($query);
        for ($i = 1; $i <= count($parameters); $i++) {
            $stmt->bindParam($i, $parameters[$i-1]);
        }
        $result = $stmt->execute();
        static::$_statementError = $stmt->errorInfo();

        // fetch data
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $return = [];
        if ($stmt->rowCount()) {
            foreach ($result as $row) {
                $return[] = new static($row);
            }
            return $return;
        }
        return null;
    }


    /**
     * Create new data row.
     *
     * @param (array) $properties
     * @return (object) Model instance
     * @return (bool)false if error occured
     */
    public static function create(Array $properties)
    {
        // set property for timestamping
        if(self::_self('_timestamps')) {
            $properties[self::_self('_created_at')] = strtotime('now');
        }

        // create the model
        $object = new static($properties);

        // prepare data
        // use only the available properties
        $fields = array();
        $values = array();
        $placeholders = array();

        foreach (self::getFields() as $field) {
            if(isset($object->{$field})) {
                switch ($field) {
                    case 'id':
                        break;
                    default:
                        $fields[]       = $field;
                        $values[]       = $object->{$field};
                        $placeholders[] = '?';
                        break;
                }
            }
        }

        // build up query
        $query = "INSERT INTO ".self::_self('_table')." (`"
            .implode('`, `', $fields)."`) VALUES (".implode(', ', $placeholders).")";

        // run query
        $stmt = self::db()->prepare($query);
        for ($i = 1; $i <= count($fields); $i++) {
            $stmt->bindParam($i, $values[$i-1]);
        }
        $result = $stmt->execute();
        static::$_statementError = $stmt->errorInfo();

        // return with result
        if ($result) {
            $object->id = self::db()->lastInsertId();
            return self::find($object->id);
        } else {
            return false;
        }
    }


    /**
     * Save the model.
     *
     * @return (bool) result of saving
     */
    protected function save()
    {
        // prepare data
        // use only the available properties
        $fields = array();
        $values = array();

        foreach (self::getFields() as $field) {
            switch ($field) {
                case 'id':
                    break;
                default:
                    $fields[] = $field;
                    $values[] = $this->{$field};
                    break;
            }
        }

        // build up query
        $query = "UPDATE ".self::_self('_table')." SET "
            .implode(' = ?, ', $fields)." = ? WHERE id = ?";

        // run query
        $stmt = self::db()->prepare($query);
        for ($i = 1; $i <= count($fields); $i++) {
            $stmt->bindParam($i, $values[$i-1]);
        }
        $stmt->bindParam($i++, $this->id);
        $result = $stmt->execute();
        static::$_statementError = $stmt->errorInfo();

        // return with result
        return $result;
    }


    /**
     * Delete model.
     *
     * @return (bool) result of deleting
     */
    public function delete()
    {
        if(self::_self('_soft_deletes')) {
            // set as deleted
            return $this->updateProperty(self::_self('_deleted_at'), strtotime('now'));
        } else {
            // build up and run query
            $query = "DELETE FROM ".self::_self('_table')." WHERE id = :Id";
            $stmt = self::db()->prepare($query);
            $stmt->bindParam('Id', $this->id);
            $result = $stmt->execute();
            static::$_statementError = $stmt->errorInfo();

            return $result;
        }
    }


    /**
     * Find one model in the database.
     *
     * @param (mixed) $value
     * @param (string) $field
     * @return (object) Model instance
     */
    public static function find($value, $field = 'id')
    {
        // search for model
        $object = self::where([$field => $value]);
        if ($object) {
            $object = reset($object);
        }
        return $object;
    }


    /**
     * Find one model in the database.
     * or create if not exists.
     *
     * @param (array) $properties
     * @return (object) Model instance
     * @return (null) if error occured
     */
    public static function findOrCreate(Array $properties = null)
    {
        // remove complex conditions
        foreach ($properties as $field => $condition) {
            if (is_array($condition)) {
                unset($properties[$field]);
            }
        }

        // search for model and create if not exists
        $object = self::where($properties);
        $object = reset($object);
        if ($object == null) {
            $object = self::create($properties);
        }
        return $object;
    }


    /**
     * Find all model in the database.
     *
     * @return (array) of Model instances
     */
    public static function all()
    {
        // search for models
        $objects = self::where();
        return $objects;
    }


    /**
     * Update model properties.
     *
     * @param (array) $properties
     * @return (bool) result of saving
     */
    public function update(Array $properties)
    {
        foreach ($properties as $field => $value) {
            if (property_exists($this, $field)) {
                $this->$field = $value;
            }
        }
        if(self::_self('_timestamps')) {
            $this->{self::_self('_updated_at')} = strtotime('now');
        }
        return $this->save();
    }


    /**
     * Update one property in the model.
     *
     * @param (string) $field
     * @param (mixed) $value
     * @return (bool) result of saving
     */
    public function updateProperty($field, $value)
    {
        return $this->update([$field => $value]);
    }


    /**
     * Helper for matching relationship keys and values.
     * Used for:
     *  - hasOne()
     *  - hasMany()
     *  - belongsToMany()
     *
     * @param (string) $foreign_key
     * @param (string) $local_key
     * @return (array)
     */
    private function hasRelationship($foreign_key = null, $local_key = null)
    {
        if (!$foreign_key) {
            $foreign_key = self::_self('_table').'_id';
        }
        if (!$local_key) {
            $local_key = 'id';
        }
        return [
            'field' => $foreign_key,
            'value' => $this->{$local_key}
        ];
    }


    /**
     * Helper for matching relationship keys and values.
     * Used for:
     *  - belongsTo()
     *
     * @param (string) $foreign_key
     * @param (string) $local_key
     * @return (array)
     */
    private function belongsRelationship($class, $foreign_key = null, $local_key = null)
    {
        if (!$foreign_key) {
            $foreign_key = 'id';
        }
        if (!$local_key) {
            $local_key = $class::_self('_table').'_id';
        }
        return [
            'field' => $foreign_key,
            'value' => $this->{$local_key}
        ];
    }


    /**
     * Get object in relationship with.
     *
     * @param (string) $class
     * @param (string) $foreign_key
     * @param (string) $local_key
     * @return (object) Model instance
     */
    public function hasOne($class, $foreign_key = null, $local_key = null)
    {
        $relation = $this->hasRelationship($foreign_key, $local_key);
        $object = $class::find($relation['value'],$relation['field']);
        return $object;
    }


    /**
     * Get all objects in relationship with.
     *
     * @param (string) $class
     * @param (string) $foreign_key
     * @param (string) $local_key
     * @return (array) of Models
     */
    public function hasMany($class, $foreign_key = null, $local_key = null)
    {
        $relation = $this->hasRelationship($foreign_key, $local_key);
        $objects = $class::where([$relation['field'] => $relation['value']]);
        return $objects;
    }


    /**
     * Get object in relationship with.
     *
     * @param (string) $class
     * @param (string) $foreign_key
     * @param (string) $local_key
     * @return (object) Model instance
     */
    public function belongsTo($class, $foreign_key = null, $local_key = null)
    {
        $relation = $this->belongsRelationship($class, $foreign_key, $local_key);
        $object = $class::find($relation['value'],$relation['field']);
        return $object;
    }


    /**
     * Get all objects in relationship with.
     *
     * @param (string) $class
     * @param (string) $foreign_key
     * @param (string) $local_key
     * @return (array) of Models
     */
    public function belongsToMany($class, $foreign_key = null, $local_key = null)
    {
        $relation = $this->hasRelationship($foreign_key, $local_key);
        $objects = $class::where([$relation['field'] => $relation['value']]);
        return $objects;
    }
}
?>
