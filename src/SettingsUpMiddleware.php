<?php

declare(strict_types=1);

namespace BenyCode\Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SettingsUpMiddleware implements MiddlewareInterface
{
    public function __construct(
        private array $settings = [],
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $request = $request
            ->withAttribute('settings', $this->settings)
        ;

        return $handler
            ->handle($request)
        ;
    }
}
