# ABOUT

This library includes:
- **model class** to create model objects according to database table structure, and handle its data
- **autoloader class** for easy use namespaced models
- **configuration loader class** to handle config (INI) files
- **PDO connector class** what can be used standalone for raw queries.


# INSTALLATION

Install package to your app via Composer:
```sh
composer require maarsson/model
```
Classes now available in your app under the `Maarsson` namespace, via Composer´s PSR-4 autoloader.

Next step to configure your database credentials, by a config file, eg. `config/database.ini`:
```
# database configuration file
[database]
host     = "127.0.0.1"
port     = "3306"
user     = "user"
passwd   = "secret"
database = "my_database"
charset  = "utf8mb4"
```

> **IMPORTANT:** Make sure that your INI files are not accessible by web browser.

Load this config file **with the `db` prefix** somewhere in your app´s `bootstrap.php` or other loader file, right after your composer´s autoloader. Create your model folder (eg. `app/model`) and register here as well. So your loader should look like:
```php
require __DIR__ . '/vendor/autoload.php';
Maarsson\Env::parse(__DIR__ . '/config/database.ini', 'db');
Maarsson\Autoloader::addPath(__DIR__.'app/model/');
```

Now you can put your models into your app folder, with mathcing namespaces and folder structure.
```
[my_app]
    |-- [app]
        |-- [Car]
            |-- LicensePlate.php
            |-- Part.php
        |-- [Garage]
            |-- Address.php
        |-- Car.php
        |-- Garage.php
    |-- [config]
        |-- database.ini
    |-- [public]
        |-- index.php
    |-- [vendor]
    |-- bootstrap.php
```

In this example you can use your models by full namespaced name...
```php
$car               = new App\Car();
$car_license_plate = new App\Car\LicensePlate();
$car_part          = new App\Car\Part();
$garage            = new App\Garage();
$garage_address    = new App\Garage\Address();
```

...or by basename (or alias) with `use`.
```php
use App\Car;
use App\Car\LicensePlate;
use App\Car\Part;
use App\Garage;
use App\Garage\Address as GarageAddress;    // your can also use alias

$car               = new Car();
$car_license_plate = new LicensePlate();
$car_part          = new Part();
$garage            = new Garage();
$garage_address    = new GarageAddress();
```


# USAGE

## In your model:

Create your data table. A pure table structure for a model should be:
```
CREATE TABLE `model` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` int(11) NOT NULL DEFAULT '0',
  `updated_at` int(11) NOT NULL DEFAULT '0',
  `deleted_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
);
```
Use this structure, and add a column for all of your custom properties, like name, value, etc. 
> Model uses timestamping and soft deleting by default, but it can be overrided for certain models.
> Properties that has no matching column in the data table, will be ignored.
> Manipulating the `id` property will be ignored as well.

Then your can use your own models by extending this base class in your **MyModel.php**.
```php
namespace App;

use Maarsson\Model;

class MyModel extends Model
{
}
```

Always use `parent::__construct()` if you want to extend your models constructor with own functions.
```php
namespace App;

use Maarsson\Model;

class MyModel extends Model
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
$my_model = MyModel::create($properties);
```

Undeclared properties will get the data table columns default setting.


### Getting object(s):
```php
// single object by ID
$my_model = MyModel::find(1);

// single object by property value
$my_model = MyModel::find('First Object','title');

// all objects
$my_model = MyModel::all();
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
$my_model = MyModel::where($properties);
```
The `where()` method will return with array of objects, even if only one satisfy search condition.
But emtpy results will return as `null`.


### Getting object(s) by order:
Simple ascending order by one property:
```php
$properties = array();
$my_model = MyModel::where($properties, 'name');
```

Complex ordering:
```php
$properties = array();
$orderBy = array(
    'name' => 'asc',
    'value' => 'desc'
);
$my_model = MyModel::where($properties, $orderBy);
```


### Creating object if not exists:
```php
$properties = array(
    'title'     => 'First Object',
    'undefined' => 'ignored',           // will be ignored as not existing property
    ['size','>=', 99],                  // will be ignored as not allowed
    ['undefined','LIKE', '%ignored%']   // will be ignored as not allowed
);
$my_model = MyModel::findOrCreate($properties);
```
The `findOrCreate()` method will return only one object even if more satisfy search condition.
Emtpy results will return as `null`.


### Updating object:
```php
$my_model = MyModel::find(1);

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
$my_model = MyModel::find(1);
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
Always use full namespaced class names for definitions.
```php
class User extends Model
{
    protected static $_hasOne = [
        'account' => 'App\Account'          // 'property_name_in_this_model' => 'Other_Model'
    ];
    protected static $_hasMany = [
        'phones' => 'App\User\Phone'
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

## Autoloader:

You can use autoloader to load your classes from multiple direcories. Just add:

```php
Maarsson\Autoloader::addPath('path/to/classes');
Maarsson\Autoloader::addPath('path/to/modules');
```

## INI loader:

You can use the `Maarsson\Env` class to reach your other settings. Every INI file entry became an uppercased constans (you can also add prefix, if wanted).

```
# in your configuration file
name = "My App"
```
```php
// in your code
Maarsson\Env::parse('config.ini');
var_dump(NAME);                 // string(6) "My App"

// or with prefix
Maarsson\Env::parse('config.ini', 'app');
var_dump(APP_NAME);             // string(6) "My App"
```

> **IMPORTANT:** Make sure that your INI files are not accessible by web browser.
