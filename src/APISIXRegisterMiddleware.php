<?php
 
declare(strict_types=1);
 
namespace BenyCode\Slim\Middleware;
 
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use BenyCode\Slim\Middleware\Traits\WithLogger;
 
final class APISIXRegisterMiddleware implements MiddlewareInterface
{
    use WithLogger;
 
    private string $registerEndpoint = '/_health';
 
    public function __construct(
        private array $config = [],
        private ?LoggerInterface $logger = null,
    ) {
        if(!isset($config['service_id'])) {
            throw new \InvalidArgumentException('`service_id` parameter is not defined.');
        }
 
        if(!isset($config['service'])) {
            throw new \InvalidArgumentException('`service` parameter is not defined.');
        }
 
        if(!isset($config['route'])) {
            throw new \InvalidArgumentException('`route` parameter is not defined.');
        }
 
        if(!isset($config['api_admin_secret'])) {
            throw new \InvalidArgumentException('`api_admin_secret` parameter is not defined.');
        }
 
        if(!isset($config['api_endpoint'])) {
            throw new \InvalidArgumentException('`api_endpoint` parameter is not defined.');
        }
    }
 
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $uri = $request
            ->getUri()
            ->getPath()
        ;
 
        if(isset($this->config['register_endpoint'])) {
            $this->registerEndpoint = $this->config['register_endpoint'];
        }
 
        if ($this->registerEndpoint === $uri) {
 
            $this
                ->info($this->config['service_id'], 'APISIX service & route registration procedure started.')
            ;
 
            $leader = $request
                ->getAttribute('im_leader')
            ;
 
            if(true === $leader) {
 
                $this
                    ->info($this->config['service_id'], 'I`m a leader. Lets register a route!')
                ;
 
                $this
                    ->createService()
                ;
 
                $this
                    ->createRoute()
                ;
            } else {
                $this
                    ->info($this->config['service_id'], 'I`m not a leader :(. Bye. I will back as soon as possible again.')
                ;
            }
 
            $this
                ->info($this->config['service_id'], 'APISIX service & route registration procedure ended.')
            ;
        }
 
        return $handler
            ->handle($request)
        ;
    }
 
    private function createService(string $endpointSuffix = '/apisix/admin/services') : void
    {
        $ch = \curl_init(
            \sprintf(
                '%s%s/%s',
                $this->config['api_endpoint'],
                $endpointSuffix,
                $this->config['service_id'],
            ),
        );
 
        $headers = array(
            \sprintf('X-API-KEY: %s', $this->config['api_admin_secret']),
        );
 
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($this->config['service']));
 
        $this
            ->info($this->config['service_id'], 'creating service', $this->config['service'])
        ;
 
        $response = \curl_exec($ch);
 
        if (false === $response) {
            $this
                ->error($this->config['service_id'], 'route creation failed', ['cURL error' => (string)\curl_error($ch)])
            ;
        } else {
 
            $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
            $this
                ->info($this->config['service_id'], \sprintf('APISIX responsed with code `%s`', $httpCode))
            ;
 
            if(200 === $httpCode) {
                $this
                    ->info($this->config['service_id'], 'service successfully created/updated', ['response' => (string)$response])
                ;
            } else {
                $this
                    ->error($this->config['service_id'], 'service creation failed', ['response' => (string)$response])
                ;
            }
        }
 
        \curl_close($ch);
    }
 
    private function createRoute(string $endpointSuffix = '/apisix/admin/routes') : void
    {
 
        $ch = \curl_init(
            \sprintf(
                '%s%s/%s',
                $this->config['api_endpoint'],
                $endpointSuffix,
                $this->config['service_id'],
            ),
        );
 
        $headers = array(
            \sprintf('X-API-KEY: %s', $this->config['api_admin_secret']),
        );
 
        $data = [
            $this->config['route'],
        ];
 
        $this
            ->info($this->config['service_id'], 'creating route', $data)
        ;
 
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($this->config['route']));
 
        $response = \curl_exec($ch);
 
        if (false === $response) {
            $this
                ->error($this->config['service_id'], 'route creation failed', ['cURL error' => (string)\curl_error($ch)])
            ;
        } else {
 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
            $this
                ->info($this->config['service_id'], \sprintf('APISIX responsed with code `%s`', $httpCode))
            ;
 
            if(200 === $httpCode) {
                $this
                    ->info($this->config['service_id'], 'route successfully created/updated', ['response' => (string)$response])
                ;
            } else {
                $this
                    ->error($this->config['service_id'], 'route creation failed', ['response' => (string)$response])
                ;
            }
        }
 
        \curl_close($ch);
    }
}
