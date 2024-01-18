<?php
 
declare(strict_types=1);
 
namespace BenyCode\Slim\Middleware;
 
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
 
final class HealthCheckEndpointMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $uri = $request
            ->getUri()
            ->getPath()
        ;
 
        if ('/_health' === $uri) {
            $response = new Response;
            $response
                ->getBody()
                ->write('OK')
            ;
 
            return $response
                ->withStatus(200)
            ;
        }
 
        return $handler
            ->handle($request)
        ;
    }
}
