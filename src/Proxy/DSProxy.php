<?php
declare(strict_types=1);

namespace TanoWAF\YaDSP\Proxy;

use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use TanoWAF\WAFCore\Filter\Bidirectional\BidirectionalFilterInterface;
use TanoWAF\WAFCore\Proxy\FixedUpstreamProxy as BaseProxy;
use TanoWAF\WAFCore\UpstreamClient\UpstreamClientInterface;

class DSProxy extends BaseProxy
{
    const DEFAULT_UPSTREAM = '/var/run/docker.sock';

    /**
     * @todo... disallow http/https upstreams?
     * @throws \Exception
     */
    protected function setUpstream(string $upstream, UpstreamClientInterface|null $httpClient = null): UpstreamClientInterface
    {
        return parent::setUpstream($upstream, $httpClient);
    }
}
