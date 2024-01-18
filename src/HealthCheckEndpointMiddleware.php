<?php

declare(strict_types=1);

namespace BenyCode\Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HealthCheckEndpointMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request
            ->getUri()
            ->getPath()
          ;

        if ($uri === '/_health') {
            $response = $handler
                ->handle($request)
				;

            return $response
                ->withStatus(200)
                ->withJson(
					[
						'status' => 'OK',
					]
		    	);
        }

        return $handler
			->handle($request)
			;
    }
}
