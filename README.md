# Standalone CRUD model class
CRUD (create, read, update, delete) model class for standalone PHP projects, but with Laravel-like usage. Beside the model, it includes an autoloader, a configuration file loader and a PDO connector, with table prefix/suffix options as well. For full documentation visit [project´s Wiki page](https://bitbucket.org/viktormaar/php-crud-model/wiki/Home)

## Usage

Install package to your app via Composer:
```sh
composer require maarsson/model
```
Classes will be under the `Maarsson` namespace, via Composer´s PSR-4 autoloader. After you configured your database connection ([see the documentation](https://bitbucket.org/viktormaar/php-crud-model/wiki/Home), how to do that), you can use your database tables as models by extending this base class in your **MyModel.php**.
```php
namespace App;

use Maarsson\Model;

class MyModel extends Model
{
}
```

### Creating a new object:
```php
$properties = array(
    'title'     => 'First Object',
    'size'      => 99
);
$my_model = MyModel::create($properties);
```


### Getting object(s):
```php
// single object by ID
$my_model = MyModel::find(1);

// single object by property value
$my_model = MyModel::find('First Object','title');

// all objects
$my_model = MyModel::all();
```


### Finding object(s) by complex conditions:
```php
$properties = array(
    'title'     => 'First Object',      // equivalent with ['title','=', 'First Object']
    ['size','>=', 99]
);
$my_model = MyModel::where($properties);
```


### Finding object(s) in custom order:
```php
// simple ascending order by a field
$properties = array();
$my_model = MyModel::where($properties, 'name');

// complex ordering
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
    ['size','>=', 99]
);
$my_model = MyModel::findOrCreate($properties);
```


### Updating object:
```php
$my_model = MyModel::find(1);

// update one property
$my_model->updateProperty('name','Updated Name');

// mass-update properties
$properties = array(
    'title'     => 'Updated Object',
    'size'      => 100
);
$my_model->update($properties);
```


### Deleting object:
```php
$my_model = MyModel::find(1);
$my_model->delete();
```


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
        'account' => 'App\\Account'          // 'property_name_in_this_model' => 'Other_Model'
    ];
    protected static $_hasMany = [
        'phones' => 'App\\User\\Phone'
    ];
}
```

Related data will automatically attached when getting the model.


## Autoloader:

You can use autoloader to load your classes from multiple direcories. Just add:

```php
Maarsson\Autoloader::addPath('path/to/classes');
Maarsson\Autoloader::addPath('path/to/modules');
```
