<?php
 
declare(strict_types=1);
 
namespace BenyCode\Slim\Middleware;
 
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use BenyCode\Slim\Middleware\Traits\WithLogger;
 
final class LeaderElectionMiddleware implements MiddlewareInterface
{
    use WithLogger;
 
    private string $idetifier;
 
    private string $leaderElectionEndpoint = '/_health';
 
    private string $etcdPreffix = 'slim-leader-election-middleware';
 
    private string $etcdLeaderKey = 'leader';
 
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
            ->info('leader election', 'checking for the leader', $data)
        ;
 
        $response = \curl_exec($ch);
 
        if (false === $response) {
            $this
                ->error('leader election', 'checking leader failed', ['cURL error' => (string)\curl_error($ch)])
            ;
        } else {
 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
            $this
                ->info('leader election', \sprintf('ETCD responsed with code `%s`', $httpCode))
            ;
 
            if(200 === $httpCode) {
                $this
                    ->info('leader election', 'leader successfully checked', ['response' => (string)$response])
                ;
 
                \curl_close($ch);
 
                $leader = \json_decode($response);
 
                if(property_exists($leader, 'kvs') && isset($leader->kvs[0]) && property_exists($leader->kvs[0], 'value')) {
                    return \base64_decode($leader->kvs[0]->value);
                }
 
                return '';
            } else {
                $this
                    ->error('leader election', 'leader check failed', ['response' => (string)$response])
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
            ->info('leader election', 'trying to be the leader', $data)
        ;
 
        $response = \curl_exec($ch);
 
        if (false === $response) {
            $this
                ->error('leader election', 'being a leader is unlucky', ['cURL error' => (string)\curl_error($ch)])
            ;
        } else {
 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
            $this
                ->info('leader election', \sprintf('ETCD responsed with code `%s`', $httpCode))
            ;
 
            if(200 === $httpCode) {
                $this
                    ->info('leader election', 'being a leader is successfully confirmed. I`m the leader!', ['response' => (string)$response])
                ;
 
                \curl_close($ch);
 
                return true;
            } else {
                $this
                    ->error('leader election', 'being a leader is unlucky', ['response' => (string)$response])
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
            ->info('leader election', 'I`m trying to prepare for leadership', $data)
        ;
 
        $response = \curl_exec($ch);
 
        if (false === $response) {
            $this
                ->error('leader election', 'failed to prepare for leadership', ['cURL error' => (string)\curl_error($ch)])
            ;
        } else {
 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
            $this
                ->info('leader election', \sprintf('ETCD responsed with code `%s`', $httpCode))
            ;
 
            if(200 === $httpCode) {
                $this
                    ->info('leader election', 'leadership successfully prepared', ['response' => (string)$response])
                ;
 
                \curl_close($ch);
 
                $lease = \json_decode($response);
 
                return (int)$lease->ID;
            } else {
                $this
                    ->error('leader election', 'failed to prepare for leadership', ['response' => (string)$response])
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
            $this->leaderElectionEndpoint = $this->config['leader_election_endpoint'];
        }
 
        if ($this->leaderElectionEndpoint === $uri) {
 
            $this
                ->info('leader election', 'Leader election procedure started.')
            ;
 
            $leader = $this
                ->isLeaderExists()
            ;
 
            if(is_string($leader) && empty($leader)) {
                $this
                    ->info('leader election', 'Leader not found. I wanna be the leader. OK?')
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
                        ->info('leader election', \sprintf('Och, I`m the leader... I will back after %s seconds... Bye!', $this->config['alection_frequency']))
                    ;
 
                    $request = $request
                        ->withAttribute('im_leader', true)
                    ;
                } else {
 
                    $this
                        ->info('leader election', \sprintf('Leader was found. I will back after %s seconds... Bye!', $this->config['alection_frequency']))
                    ;
 
                    $request = $request
                        ->withAttribute('im_leader', false)
                    ;
                }
            }
 
            $this
                ->info('leader election', 'Leader election procedure ended.')
            ;
        }
 
        return $handler
            ->handle($request)
        ;
    }
}
