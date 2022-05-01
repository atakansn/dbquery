<?php

require __DIR__ . '/vendor/autoload.php';

$params = [
    'host' => 'localhost',
    'dbname' => 'migration_exam',
    'user' => 'root',
    'password' => 'root'
];

$builder = new \DBQuery\Builder($params);

$a = $builder->table('users')
    ->whereIn('id',[1,2,3])
    ->get();

print_r($a);
