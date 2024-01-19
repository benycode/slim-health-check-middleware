# Slim Middleware bundle

A Slim 4 Framework useful middlewares.

## Features

- health check endpoint;
- info endpoint;
- settings setup.

## Table of contents

- [Install](#install)
- [Usage](#usage)

## Install

Via Composer

``` bash
$ composer require benycode/slim-middleware
```

Requires Slim 4.

## Usage

Use [DI](https://www.slimframework.com/docs/v4/concepts/di.html) to inject the library Middleware classes:

```php
use BenyCode\Slim\Middleware\HealthCheckEndpointMiddleware;
use BenyCode\Slim\Middleware\InfoEndpointMiddleware;
use BenyCode\Slim\Middleware\SettingsUpMiddleware;

return [
    ......
    HealthCheckEndpointMiddleware::class => function (ContainerInterface $container) {
        return new HealthCheckEndpointMiddleware();
    },
    InfoEndpointMiddleware::class => function (ContainerInterface $container) {
        return new InfoEndpointMiddleware(<<inject version var>>);
    },
    SettingsUpMiddleware::class => function (ContainerInterface $container) {
        return new SettingsUpMiddleware([<<inject settings>>]);
    },
];
```

add the **Middlewares** to `any` route at the end of the routes:

```php
use BenyCode\Slim\RequestLoggerMiddleware\RequestLogMiddleware;
use BenyCode\Slim\RequestLoggerMiddleware\ResponseLogMiddleware;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app
   ->get(
   '/{any:.*}',
   function (Request $request, Response $response) {
      throw new HttpNotFoundException($request);
   }
   )
   ->add(HealthCheckEndpointMiddleware::class)
   ->add(InfoEndpointMiddleware::class)
   ->setName('any')
   ;
```

welcome, your app is within new paths:
- /_health
- /_info

add the **Middlewares** to a `global`:

```php
use BenyCode\Middleware\SettingsUpMiddleware;

return function (App $app) {
	...
	$app->add(SettingsUpMiddleware::class);
};
```

get the settings:

```php
protected function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
   $settings = $request
      ->getAttribute('settings')
   ;
}
```

