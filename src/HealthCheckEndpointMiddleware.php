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
 
final class HealthCheckEndpointMiddleware implements MiddlewareInterface
{
    use WithLogger;
 
    private string $healthEndpoint = '/_health';
 
    public function __construct(
        private array $config = [],
        private ?LoggerInterface $logger = null,
    ) {
    }
 
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $uri = $request
            ->getUri()
            ->getPath()
        ;
 
        if(isset($this->config['health_endpoint'])) {
            $this->healthEndpoint = $this->config['health_endpoint'];
        }
 
        if ($this->healthEndpoint === $uri) {
            $response = new Response;
            $response
                ->getBody()
                ->write('OK')
            ;
 
            $this
                ->info('health checker', 'health successfully checked.')
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
