# Slim Middleware bundle

A Slim 4 Framework useful middlewares.

## Features

- health check endpoint;
- info endpoint.

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

return [
    ......
    HealthCheckEndpointMiddleware::class => function (ContainerInterface $container) {
        return new HealthCheckEndpointMiddleware();
    },
    InfoEndpointMiddleware::class => function (ContainerInterface $container) {
        return new InfoEndpointMiddleware(<<inject version var>>);
    },
];
```

add a **Middlewares** to `any` route at the end of the routes:

```php
use BenyCode\Slim\RequestLoggerMiddleware\RequestLogMiddleware;
use BenyCode\Slim\RequestLoggerMiddleware\ResponseLogMiddleware;

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
