# CMYii CMS core

CMYii - is CMS admin system based on Yii Framework 2.

This is core module that provides the functionality of CMS for front-part of site, but does not include the admin system itself.  

## Install

If you need the complete CMS, see install [cmyii](https://github.com/paulzi/cmyii#install).

If you only need a core of CMS, use:

```bash
composer require cmyii-core
```

## Usage

See [cmyii usage](https://github.com/paulzi/cmyii#usage) for full feature usage guide.

Apply migrations in `migrations` folder. To do this, use one of the following methods:

1) Add `paulzi\cmyii\migrations` namespace to your console app:

```php
return [
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'console\migrations',
                'paulzi\cmyii\migrations',
            ],
        ],
    ],
]
```

2) Run command:

```bash
./yii migrate --migrationPath= --migrationNamespaces=paulzi\cmyii\migrations
```

To use only the core, specify in the configs of the application:

```php
return [
    'bootstrap' => ['cmyii'],
    'modules' => [
        'cmyii' => [
            'class' => 'paulzi\cmyii\Cmyii',
        ],
    ],
];
```

## Documentation

See [cmyii documentation](https://github.com/paulzi/cmyii#documentation)