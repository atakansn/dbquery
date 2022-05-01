# PHP PDO Basic Query Builder

### It is a similar version of the laravel database engine.

#### Configuration

````php
$params = [
    'host' => 'localhost',
    'dbname' => 'migration_exam',
    'user' => 'root',
    'password' => 'root'
];

$builder = new \DBQuery\Builder($params)
````

#### Insert

```php
$builder->table('users')
    ->insert([
   'name'=>'aa',
   'surname'=>'bb',
   'username' => 'aabb' 
]);
```

#### Update

```php
$builder->table('users')
    ->where('id',9)
    ->update([
        'name'=>'cc' 
]);
```

#### Delete

```php
$builder->table('users')
    ->where('id',9)
    ->delete();
```

#### Delete with id

```php
$builder->table('users')
    ->delete(9);
```

#### Increment and Decrement

```php
$builder->table('users')
    ->increment('number');

//With id
$builder->table('users')
    ->increment('number',['id'=>9]);

$builder->table('users')
    ->decrement('number');

//With id
$builder->table('users')
    ->decrement('number',['id'=>9]);
```

#### Count, Avg, Sum, Min, Max

```php

$builder->table('users')
    ->count();

$builder->table('users')
    ->avg();

$builder->table('users')
    ->sum('number');

$builder->table('users')
    ->min();

$builder->table('users')
    ->max();
```

#### where, whereNull, whereIn

```php
$builder->table('users')
    ->where('id',9)
    ->get();

$builder->table('users')
    ->whereNull('name')
    ->get();

$builder->table('users')
    ->whereIn('name',[1,2,3,4])
    ->get();
```

#### Returns the data of the specified table

```php
$builder->table('users')
    ->get();

//or by column name
$builder->table('users')
    ->get('name');
```

#### updateOrInsert()

```php
$builder->table('users')
//The values in the first array exist in the database, and update it with the second array, but if not, it creates a new record in the database. 
    ->updateOrInsert(
        ['name'=>'aa','surname'=>'bb'],
        ['username'=>'aa_new']
    );
```

#### insertGetId()

```php
$builder->table('users')
//Returns the last id of the inserted data. 
    ->insertGetId([
        'name'=>'aa','surname'=>'bb'
    ]);
```

#### truncate()

```php
$builder->table('users')
    ->truncate();
```

#### first()

```php
$builder->table('users')
    ->first();
    
$builder->table('users')
    ->first('name');

$builder->table('users')
    ->where('id',9)
    ->first();
```

#### find()

```php
$builder->table('users')
    ->find(9);
```

#### exists()

```php
$builder->table('users')
    ->where('id',9)
    ->exists();
```

#### Joins

```php
$builder->table('users')
    ->join('profile_images','users.id','=','profile_images.user_id')
    ->select('users.*','profile_images.link')
    ->get();
```

#### OrderBy
```php
$builder->table('users')
    ->orderBy('created_at','DESC')
    ->get();

$builder->table('users')
    ->orderBy('created_at','ASC')
    ->get();

$builder->table('users')
    ->orderByDesc('created_at')
    ->get();

$builder->table('users')
    ->orderByAsc('created_at')
    ->get();
```

