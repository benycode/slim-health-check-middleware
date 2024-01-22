# Slim Middleware bundle

A Slim 4 Framework useful middlewares.

## Features

- health check endpoint;
- info endpoint;
- settings setup;
- exception handler;
- APISIX auto route register.

## Table of contents

- [Install](#install)
- [Health check endpoint usage](#Health check endpoint usage)
- [Info endpoint usage](#Info endpoint usage)
- [Settings setup usage](#Settings setup usage)
- [Exception handler usage](#Exception handler usage)
- [APISIX auto route register usage](#APISIX auto route register usage)

## Install

Via Composer

``` bash
$ composer require benycode/slim-middleware
```

Requires Slim 4.

## Health check endpoint usage

Use [DI](https://www.slimframework.com/docs/v4/concepts/di.html) to inject the library Middleware classes:

```php
use BenyCode\Slim\Middleware\HealthCheckEndpointMiddleware;

return [
    ......
    HealthCheckEndpointMiddleware::class => function (ContainerInterface $container) {
        return new HealthCheckEndpointMiddleware();
    },
    ......
];
```

add the **Middleware** to `any` route at the end of the routes:

```php
use Slim\Exception\HttpNotFoundException;
use BenyCode\Slim\Middleware\HealthCheckEndpointMiddleware;

$app
   ->get(
   '/{any:.*}',
   function (Request $request, Response $response) {
      throw new HttpNotFoundException($request);
   }
   )
   ....
   ->add(HealthCheckEndpointMiddleware::class)
   ->setName('any')
   ;
```

welcome, your app is within new path:
- /_health

## Info endpoint usage

Use [DI](https://www.slimframework.com/docs/v4/concepts/di.html) to inject the library Middleware classes:

```php
use BenyCode\Slim\Middleware\InfoEndpointMiddleware;

return [
    ......
    InfoEndpointMiddleware::class => function (ContainerInterface $container) {
        return new InfoEndpointMiddleware(<<inject version var>>);
    },
    ......
];
```

add the **Middleware** to `any` route at the end of the routes:

```php
use Slim\Exception\HttpNotFoundException;
use BenyCode\Slim\Middleware\InfoEndpointMiddleware;

$app
   ->get(
   '/{any:.*}',
   function (Request $request, Response $response) {
      throw new HttpNotFoundException($request);
   }
   )
   ....
   ->add(InfoEndpointMiddleware::class)
   ->setName('any')
   ;
```

welcome, your app is within new path:
- /_info

## Settings setup usage

add the **Middleware** to a `global` list:

```php
use BenyCode\Middleware\SettingsUpMiddleware;

return function (App $app) {
        ...
        $app->add(SettingsUpMiddleware::class);
        ...
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

## Exception handler usage

add the **Middleware** to a `global` list:

```php
use BenyCode\Middleware\ExceptionMiddleware;

return function (App $app) {
        ...
        $app->add(ExceptionMiddleware::class);
        ...
};
```

welcome, your app is within new error handler.

## APISIX auto route register usage

Idea: Auto create service and routes on the health check procedure.

You can use it with:
- Docker health check;
- k8s health check;
- and more others....

Requires: HealthCheckMiddleware and docker/k8s mechanism.

Use [DI](https://www.slimframework.com/docs/v4/concepts/di.html) to inject the library Middleware classes:

```php
use BenyCode\Slim\Middleware\HealthCheckEndpointMiddleware;

return [
    ......
    HealthCheckEndpointMiddleware::class => function (ContainerInterface $container) {
        return new HealthCheckEndpointMiddleware();
    },
    APISIXRegisterMiddleware::class => function (ContainerInterface $container) {
       return new APISIXRegisterMiddleware(
       [
          'service_id' => '<<describe your service name>>',
          'service' => [
             'upstream' => [
                'type' => 'roundrobin',
                'nodes' => [
                   '<<describe working endpoint>>:<<describe working port>>' => 1, // example: books-microservice:80
                ],
             ],
          ],
          'route' => [
             'uri' => "<<describe working path>>", // example: /books/*
             'service_id' => '<<describe service id>>', // example: books-microservice
          ],
          'api_admin_secret' => '<<describe APISIX admin secret>>',
          'api_endpoint' => '<<describe APISIX API endpoint url>>', // example: http://api-gateway:9180
        ],
	<<inject you PSR7 logger if needed>>,
        );
    },
    ......
];
```

add the **Middleware** to `any` route at the end of the routes:

```php
use Slim\Exception\HttpNotFoundException;
use BenyCode\Slim\Middleware\HealthCheckEndpointMiddleware;
use BenyCode\Slim\Middleware\APISIXRegisterMiddleware;

$app
   ->get(
   '/{any:.*}',
   function (Request $request, Response $response) {
      throw new HttpNotFoundException($request);
   }
   )
   ....
   ->add(HealthCheckEndpointMiddleware::class)
   ->add(APISIXRegisterMiddleware::class)
   ->setName('any')
   ;
```

welcome, your app will be auto registered in the APISIX on the every health check.
