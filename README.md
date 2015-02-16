# Bogardo\Multiauth

[![Latest Stable Version](https://img.shields.io/github/release/Bogardo/Multiauth.svg?style=flat)](https://packagist.org/packages/bogardo/multiauth)
[![MIT License](https://img.shields.io/packagist/l/Bogardo/Multiauth.svg?style=flat)](https://packagist.org/packages/bogardo/multiauth)
[![Build Status](https://travis-ci.org/Bogardo/Multiauth.svg?branch=master)](https://travis-ci.org/Bogardo/Multiauth)
[![Coverage Status](https://coveralls.io/repos/Bogardo/Multiauth/badge.svg)](https://coveralls.io/r/Bogardo/Multiauth)

> This is a work in progress and should probably not be used in production yet.

Laravel authentication driver which enables you to use multiple Eloquent models for authentication.

> This package supports Laravel's password reminders by default and needs no extra configuration.

## Installation

> Currently only compatible with Laravel 4.2 <br />
> Support for Laravel 5 is on the roadmap

#### Install the package with composer

`$ composer require bogardo\multiauth`

#### Add the service provider

Add the following to your `providers` array in `app/config/app.php`

`'Bogardo\Multiauth\MultiauthServiceProvider',`


## Configuration

Change the following in your `app/config/auth.php` file.

Set your driver to `multiauth`

```php
'driver'    => 'multiauth'
```

Add your multiauth settings

```php
'multiauth' => [
 
    'identifier_key' => 'email',

    'entities' => [
        [
            'type'       => 'user',
            'table'      => 'users',
            'model'      => 'User',
            'identifier' => 'username'
        ],
        [
            'type'       => 'administrator',
            'table'      => 'admins',
            'model'      => 'Admin',
            'identifier' => 'email'
        ]
    ]
]
```

## Usage

### Prepare your models

There are 2 things that must be changed in your models<br />
1. Add the `Bogardo\Multiauth\User\UserTrait` (replace it if it is already present)<br />
2. Add a public `$authtype` property. The value of the property should match the `type` key defined in the `multiauth` configuration.

#### Examples

##### User model
```php
use Bogardo\Multiauth\User\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends Eloquent implements UserInterface, RemindableInterface
{

    use UserTrait, RemindableTrait;

    public $authtype = 'user';
    
}
```

##### Admin model
```php
use Bogardo\Multiauth\User\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class Admin extends Eloquent implements UserInterface, RemindableInterface
{

    use UserTrait, RemindableTrait;

    public $authtype = 'administrator';
    
}
```

### Validation 

When working with just one table for users you'd normally only check for unique emails/usernames in your users table. But when you're working with multiple tables you have to make sure that your emails/usernames are unique on all relevant tables.

This package provides a convient validation rule to do exactly that.

It will check if the supplied value (email/username) exists on any of the tables defined in the multiauth configuration. It will take note of the identifier (email/username/custom) defined in the configuration.

When using the rule without extra parameters it will make sure the supplied value does not exist in any of the tables. You'd probably use this for creating new users.

```php
multiAuthUnique
```

When updating an existing user you'd want to check if the supplied value is unique **except** for the record you are updating. You can do this by supplying two parameters:
- The `type` of the user being updated
- The `id` of the user being updated

```php
multiAuthUnique:user,2
```

#### Usage

Creating new record

```php
Validator::make(
    ['email' => 'email@example.com'], // data array
    ['email' => 'multiAuthUnique']    // rules array
);
```

Updating an existing Admin user with an id of 5

```php
Validator::make(
    ['email' => 'email@example.com'],              // data array
    ['email' => 'multiAuthUnique:administrator,5'] // rules array
);
```

### API

Get the Multiauth service

```php
/** @var Bogardo\Multiauth\Service $service */
$service = App::make('multiauth.service');
```

Get a collection of all registered entities
The `EntityCollection` extends Laravel's `Collection`

```php
$entities = $service->getEntities();

// $entities Output
object(Bogardo\Multiauth\Entity\EntityCollection)
  protected 'items' => 
    array (size=2)
	   0 => 
        object(Bogardo\Multiauth\Entity\Entity)
          public 'type' => string 'user'
          public 'table' => string 'users'
          public 'model' => string 'User'
          public 'identifier' => string 'username'
      1 => 
        object(Bogardo\Multiauth\Entity\Entity)
          public 'type' => string 'administator'
          public 'table' => string 'admins'
          public 'model' => string 'Admin'
          public 'identifier' => string 'email'
      

```

Get an entity by type

```php
$entity = $service->getEntityByType('user');

// $entity Output
object(Bogardo\Multiauth\Entity\Entity)
  public 'type' => string 'user'
  public 'table' => string 'users'
  public 'model' => string 'User'
  public 'identifier' => string 'username'

```


---

#### Filters example implementation

You could use this, for example, to create custom route filters for each user type.


```php

/** @var Bogardo\Multiauth\Service $service */
$service = App::make('multiauth.service');
$types = $service->getEntities()->lists('type');

foreach ($types as $type) {
    Route::filter('multiauth.' . $type, function($route, $request) use ($type) {

        if (Auth::guest()) {
            // The user is not logged in.
            return Redirect::to('/');
        }

        if (Auth::user()->authtype !== $type) {
            // The user is logged in, but is not of correct type
            return Redirect::to('/');
        }
    });
}
```

This will create a route filter for every registered user type:
- user: `multiauth.user`
- administrator: `multiauth.administrator`

Which you could use to restrict access to a route which should only be accessible for a specific user type.

```php
Route::get('admin', ['before' => 'multiauth.administrator', function() {
    echo 'Admins Only!!';
}]);
```


## FAQ

### - I'm unable to login using the loginUsingId and onceUsingId methods

The `loginUsingId()` and `onceUsingId()` methods should be passed the **type and the id** of the user, **separated by a dot**. This deviates from the default usage, where you'd only have to pass the ID of the user.

```php
Auth::loginUsingId('administrator.2');

Auth::onceUsingId('user.54')
```

---

## Changelog

#### v0.1.0
- Multiauth implementation
- Updated docs (formatting)

#### v0.0.1
- Initial setup with just documentation

## Todo
- Tests
- Support for Laravel 5
