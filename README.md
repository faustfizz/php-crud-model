# ABOUT

This class creates model objects according to database table structure, and handle data.
- Model uses timestamping and soft deleting by default, but it can be overrided for certain models.
- Properties that missing from the data table, will be ignored.
- Manipulating the `id` property will be ignored as well.

It also contains a basic PDO initializer, what can be used standalone for raw queries.

# INSTALLATION
Install package in your app folder via Composer:
```sh
composer require maarsson/model
```
Classes will be available via Composer´s autoloader, under the `Maarsson` namespace.

Now you have to configure your database credentials somewhere in your app´s `bootstrap.php` or other loader:
```php
define('DB_HOST',     "127.0.0.1");
define('DB_PORT',     "3306");
define('DB_USER',     "user");
define('DB_PASSWD',   "secret");
define('DB_DATABASE', "my_database");
define('DB_CHARSET',  "utf8mb4");
```


# USAGE

## In your model:

Create your data table. Then your can use your own models by extending this base class in your **My_Model.php**.
```php
use Maarsson\Model;

class My_Model extends Model
{
}
```

Always use `parent::__construct()` if you want to extend your models constructor with own functions.
```php
use Maarsson\Model;

class My_Model extends Model
{
    protected function __construct(Array properties = null) {
        parent::__construct($properties);
        /*
            your custom code
        */
    }
}
```

## In your app:

### Creating a new object:

```php
$properties = array(
    'title'     => 'First Object',
    'size'      => 99,
    'undefined' => 'Value',             // will be ignored as not existing property
);
$my_model = My_Model::create($properties);
```

Undeclared properties will get the data table columns default setting.


### Getting object(s):

```php
// single object by ID
$my_model = My_Model::find(1);

// single object by property value
$my_model = My_Model::find('First Object','title');

// all objects
$my_model = My_Model::all();
```

The `find()` method will return only one object even if more satisfy search condition. 
Emtpy results will return as `null`.


### Getting object(s) by complex conditions:
```php
$properties = array(
    'title'     => 'First Object',      // equivalent with ['title','=', 'First Object']
    'undefined' => 'Value',             // will be ignored as not existing property
    ['size','>=', 99],
    ['undefined','LIKE', '%value%']     // will be ignored as not existing property
);
$my_model = My_Model::where($properties);
```
The `where()` method will return with array of objects, even if only one satisfy search condition. 
But emtpy results will return as `null`.


### Creating object if not exists:
```php
$properties = array(
    'title'     => 'First Object',
    'undefined' => 'ignored',           // will be ignored as not existing property
    ['size','>=', 99],                  // will be ignored as not allowed
    ['undefined','LIKE', '%ignored%']   // will be ignored as not allowed
);
$my_model = My_Model::findOrCreate($properties);
```
The `findOrCreate()` method will return only one object even if more satisfy search condition. 
Emtpy results will return as `null`.


### Updating object:
```php
$my_model = My_Model::find(1);

// update one property
$my_model->updateProperty('name','Updated Name');

// mass-update properties
$properties = array(
    'title'     => 'Updated Object',
    'size'      => 100,
    'undefined' => 'Updated Value'      // will be ignored as not existing property
);
$my_model->update($properties);
```
The `update()` and `updateProperty()` methods will `true` or `false`, according to the result of updating.


### Deleting object:
```php
$my_model = My_Model::find(1);
$my_model->delete();
```
The `update()` and `updateProperty()` methods will `true` or `false`, according to the result of updating.


## Relationships

Models can have different types of relationships:
- *has One* (eg. `User` has one `Account`)
- *has Many* (eg. `User` has many `Phone`)
- *belongs to One* (eg. `Account` belongs to one `User`)
- *belongs to Many* (eg. `User` belongs to many `Role`)

The relationship keys in the database are the `id` and the `related_table_id` columns.

### Defining relationship in model:

```php
class User extends Model
{
    protected static $_hasOne = [
        'account' => 'Account'          // 'property_name_in_model' => 'Existing_Model'
    ];
    protected static $_hasMany = [
        'phones' => 'Phone'
    ];
}
```

### Using relationship in your app:

```php
// load the model
$user = User::find(1);
dump($user);
```
Will print out:
```php
User Object
(
    [id:protected] => 1
    [name] => John Doe
    [phones] => Array
        (
            [0] => Phone Object
                (
                    [id:protected] => 1
                    [number] => 040-111-1111
                )
            [1] => Phone Object
                (
                    [id:protected] => 2
                    [number] => 040-222-2222
                )
        )
    [account] => Account Object
        (
            [id:protected] => 1
            [username] => johndoe
        )
)
```

## PDO Connector:

You can use the `Maarsson\DbConnection` class for custom parametered queries:

```php
$id = 1;
$query = "SELECT * FROM my_table WHERE id = :Id";
$stmt = Maarsson\DbConnection::init();
$stmt->prepare($query);
$stmt->bindParam(':Id', $id);
$result = $stmt->execute();
$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
```
