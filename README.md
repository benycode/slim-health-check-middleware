# Slim Middleware bundle

A Slim 4 Framework useful middlewares.

## Features

- health check endpoint;
- info endpoint;
- settings setup;
- exception handler;
- APISIX auto route register;
- Leader election middleware.

## Table of contents

- [Install](#install)
- [Health check endpoint usage](#health check endpoint usage)
- [Info endpoint usage](#info endpoint usage)
- [Settings setup usage](#settings setup usage)
- [Exception handler usage](#exception handler usage)
- [APISIX auto route register usage](#apisix auto route register usage)
- [Leader election usage](#leader election usage)

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
        return new HealthCheckEndpointMiddleware(
           [
              'health_endpoint' => '/_health', // change if needed other endpoint
           ],
        );
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
- /_health or your defined

create health check.

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

Requires `curl` and `docker/k8s` health check mechanism.

Balanced with `LeaderElectionMiddleware`, bring more stability and activate one instance registration functionality.

Use [DI](https://www.slimframework.com/docs/v4/concepts/di.html) to inject the library Middleware classes:

```php
use BenyCode\Slim\Middleware\APISIXRegisterMiddleware;

return [
    ......
    APISIXRegisterMiddleware::class => function (ContainerInterface $container) {
       return new APISIXRegisterMiddleware(
       [
          'health_endpoint' => '/_health', // change if needed other endpoint
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
use BenyCode\Slim\Middleware\APISIXRegisterMiddleware;

$app
   ->get(
   '/{any:.*}',
   function (Request $request, Response $response) {
      throw new HttpNotFoundException($request);
   }
   )
   ....
   ->add(APISIXRegisterMiddleware::class)
   ->setName('any')
   ;
```

create health check `/_health` or your defined.

welcome, your app will be auto (re)registered in the APISIX on the every health check.

## Leader election usage

Idea: in the microservice world can be more then one instance who can execute the relevant commands and there is a need for those commands to be executed only by one. Vote for the leader using health check mechanizm!

Balanced with the `APISIXRegisterMiddleware`.

You can use it with:
- Docker health check;
- k8s health check;
- and more others....

Requires `curl`, `docker/k8s` health check mechanism and `ETCD v3`.

Use [DI](https://www.slimframework.com/docs/v4/concepts/di.html) to inject the library Middleware classes:

```php
use BenyCode\Slim\Middleware\LeaderElectionMiddleware;

return [
    ......
    LeaderElectionMiddleware::class => function (ContainerInterface $container) {
       return new LeaderElectionMiddleware(
          [
             'health_endpoint' => '/_health', // change if needed other endpoint
             'etcd_endpoint' => '<<etcd endpoint>>',
             'alection_frequency' => 5, // alection frequence in seconds
              <<inject you PSR7 logger if needed>>,
          ],
        );
    },
    ......
];
```

add the **Middleware** to `any` route at the end of the routes:

```php
use Slim\Exception\HttpNotFoundException;
use BenyCode\Slim\Middleware\LeaderElectionMiddleware;

$app
   ->get(
   '/{any:.*}',
   function (Request $request, Response $response) {
      throw new HttpNotFoundException($request);
   }
   )
   ....
   ->add(LeaderElectionMiddleware::class)
   ->setName('any')
   ;
```

get the leader status:

```php
protected function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
   $leader = $request
      ->getAttribute('im_leader')
   ;

   if($leader) {
      // the leader code
   }
}
```
