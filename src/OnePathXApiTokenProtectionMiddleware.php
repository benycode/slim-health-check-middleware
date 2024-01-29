<?php
 
declare(strict_types=1);
 
namespace BenyCode\Slim\Middleware;
 
use Slim\Psr7\Response;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use BenyCode\Slim\Middleware\Traits\WithLogger;
 
final class OnePathXApiTokenProtectionMiddleware implements MiddlewareInterface
{
    use WithLogger;
 
    public function __construct(
        private array $config = [],
        private ?LoggerInterface $logger = null,
    ) {
        if(!isset($config['path'])) {
            throw new \InvalidArgumentException('`path` parameter is not defined.');
        }
 
        if(!isset($config['x-api-token'])) {
            throw new \InvalidArgumentException('`x-api-token` parameter is not defined.');
        }
    }
 
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $uri = $request
            ->getUri()
            ->getPath()
        ;
 
        if ($this->config['path'] === $uri) {
 
            $this
                ->info('one path x api token protection', \sprintf('endpoint needed protections `%s` was detected. Is the api key valid?', $this->config['path']))
            ;
 
            $tokenHeader = $request
                ->getHeaderLine('X-Api-Token')
            ;
 
            if ($tokenHeader === $this->config['x-api-token']) {
 
                $this
                    ->info('one path x api token protection', \sprintf('Tokens `%s=%s` matched. Protection was revoked.', $tokenHeader, $this->config['x-api-token']))
                ;
 
                return $handler
                   ->handle($request)
                ;
            } else {
 
                $response = new Response;
 
                $this
                    ->info(
                        'one path x api token protection',
                        \sprintf(
                            'Tokens `%s=%s` not matched. Endpoint `%s` was successfully protected!',
                            $tokenHeader,
                            $this->config['x-api-token'],
                            $this->config['path']
                        )
                    )
                ;
 
                return $response
                   ->withStatus(401)
                ;
            }
        }
 
        return $handler
            ->handle($request)
        ;
    }
}
