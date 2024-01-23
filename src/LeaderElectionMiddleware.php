<?php
 
declare(strict_types=1);
 
namespace BenyCode\Slim\Middleware;
 
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
 
final class LeaderElectionMiddleware implements MiddlewareInterface
{
    private string $idetifier;
 
    private string $healthEndpoint = '/_health';
 
    private string $etcdPreffix = 'slim-leader-election-middleware';
 
    private string $etcdLeaderKey = 'leader';
 
    private string $etcdFailoverKey = 'failover';
 
    public function __construct(
        private array $config = [],
        private ?LoggerInterface $logger = null,
    ) {
        if(!isset($config['alection_frequency'])) {
            throw new \InvalidArgumentException('`alection_frequency` parameter is not defined.');
        }
 
        if(!isset($config['etcd_endpoint'])) {
            throw new \InvalidArgumentException('`etcd_endpoint` parameter is not defined.');
        }
 
        $this->idetifier = \gethostname();
    }
 
    private function isLeaderExists(string $endpointSuffix = '/kv/range') : ?string
    {
        $ch = \curl_init(
            \sprintf(
                '%s%s',
                $this->config['etcd_endpoint'],
                $endpointSuffix,
            ),
        );
 
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
 
        $data = [
            'key' => \base64_encode(\sprintf('%s/%s', $this->etcdPreffix, $this->etcdLeaderKey)),
        ];
 
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($data));
 
        $this
            ->info('checking leader', $data)
        ;
 
        $response = \curl_exec($ch);
 
        if (false === $response) {
            $this
                ->error('checking leader failed', ['cURL error' => (string)\curl_error($ch)])
            ;
        } else {
 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
            $this
                ->info(\sprintf('ETCD responsed with code `%s`', $httpCode))
            ;
 
            if(200 === $httpCode) {
                $this
                    ->info('leader successfully checked', ['response' => (string)$response])
                ;
 
                \curl_close($ch);
 
                $leader = \json_decode($response);
 
                if(property_exists($leader, 'kvs') && isset($leader->kvs[0]) && property_exists($leader->kvs[0], 'value')) {
                    return \base64_decode($leader->kvs[0]->value);
                }
 
                return '';
            } else {
                $this
                    ->error('leader check failed', ['response' => (string)$response])
                ;
            }
        }
 
        \curl_close($ch);
 
        return null;
    }
 
    private function createLeader(int $leaseId, string $endpointSuffix = '/kv/put') : bool
    {
        $ch = \curl_init(
            \sprintf(
                '%s%s',
                $this->config['etcd_endpoint'],
                $endpointSuffix,
            ),
        );
 
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
 
        $data = [
            'key' => \base64_encode(\sprintf('%s/%s', $this->etcdPreffix, $this->etcdLeaderKey)),
            'value' => \base64_encode($this->idetifier),
            'lease' => $leaseId,
        ];
 
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($data));
 
        $this
            ->info('creating leader', $data)
        ;
 
        $response = \curl_exec($ch);
 
        if (false === $response) {
            $this
                ->error('leader creation failed', ['cURL error' => (string)\curl_error($ch)])
            ;
        } else {
 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
            $this
                ->info(\sprintf('ETCD responsed with code `%s`', $httpCode))
            ;
 
            if(200 === $httpCode) {
                $this
                    ->info('leader successfully created', ['response' => (string)$response])
                ;
 
                \curl_close($ch);
 
                return true;
            } else {
                $this
                    ->error('leader creation failed', ['response' => (string)$response])
                ;
            }
        }
 
        \curl_close($ch);
 
        return false;
    }
 
    private function createLease(string $endpointSuffix = '/lease/grant') : ?int
    {
        $ch = \curl_init(
            \sprintf(
                '%s%s',
                $this->config['etcd_endpoint'],
                $endpointSuffix,
            ),
        );
 
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
 
        $data = [
            'json' => [
                'TTL' => $this->config['alection_frequency'] . 's',
            ],
        ];
 
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($data));
 
        $this
            ->info('creating lease', $data)
        ;
 
        $response = \curl_exec($ch);
 
        if (false === $response) {
            $this
                ->error('lease creation failed', ['cURL error' => (string)\curl_error($ch)])
            ;
        } else {
 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
            $this
                ->info(\sprintf('ETCD responsed with code `%s`', $httpCode))
            ;
 
            if(200 === $httpCode) {
                $this
                    ->info('lease successfully created', ['response' => (string)$response])
                ;
 
                \curl_close($ch);
 
                $lease = \json_decode($response);
 
                return (int)$lease->ID;
            } else {
                $this
                    ->error('lease creation failed', ['response' => (string)$response])
                ;
            }
        }
 
        \curl_close($ch);
 
        return null;
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
 
            $this
                ->info('Leader election procedure started.')
            ;
 
            $leader = $this
                ->isLeaderExists()
            ;
 
            if(is_string($leader) && empty($leader)) {
                $this
                    ->info('Leader not found. I wanna be leader. OK?')
                ;
 
                $leaseId = $this
                    ->createLease()
                ;
 
                if(null !== $leaseId) {
                    $leader = $this
                        ->createLeader($leaseId)
                    ;
                }
 
                $request = $request
                    ->withAttribute('im_leader', true)
                ;
 
            } elseif(!empty($leader)) {
 
                if($leader === $this->idetifier) {
                    $this
                        ->info(\sprintf('Och, I`m the leader... I will back after %s seconds... Bye!', $this->config['alection_frequency']))
                    ;
 
                    $request = $request
                        ->withAttribute('im_leader', true)
                    ;
                } else {
 
                    $this
                        ->info(\sprintf('Leader was found. I will back after %s seconds... Bye!', $this->config['alection_frequency']))
                    ;
 
                    $request = $request
                        ->withAttribute('im_leader', false)
                    ;
                }
            }
 
            $this
                ->info('Leader election procedure ended.')
            ;
        }
 
        return $handler
            ->handle($request)
        ;
    }
 
    private function error(string $message, array $context = [])
    {
        if(null !== $this->logger) {
            $this
                ->logger
                ->error(\sprintf('%s | %s', $this->idetifier, $message), $context)
            ;
        }
    }
 
    private function info(string $message, array $context = [])
    {
        if(null !== $this->logger) {
            $this
                ->logger
                ->info(\sprintf('%s | %s', $this->idetifier, $message), $context)
            ;
        }
    }
}
