# suitgenerator

Model, Controller, View Generator from migration for SuitCMS, still alpha version

* [Installation](#installation)
* [Use](#use)


## Installation

First, you'll need to install the package via Composer:

```shell
$ composer require fznoviar/suitgenerator
```

Then, update `config/app.php` by adding an entry for the service provider.

```php
'providers' => [
    // ...
    Fznoviar\SuitGenerator\SuitGeneratorServiceProvider::class,
];
```


## Use

For generate model from a table,

```shell
php artisan suit:model --table=posts
```

For generate controller from 2 tables,

```shell
php artisan suit:controller --table=posts,gallery_categories
```

For generate view from all tables,

```shell
php artisan suit:view --all
```

For generate whole mvc from a table,

```shell
php artisan suit:module --table=posts
```