# suitgenerator

Model, Controller, View Generator for SuitCMS, still alpha version

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