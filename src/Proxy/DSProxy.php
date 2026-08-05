<?php
declare(strict_types=1);

namespace TanoWAF\YaDSP\Proxy;

use TanoWAF\WAFCore\Proxy\FixedUpstreamProxy;
use TanoWAF\WAFCore\UpstreamClient\UpstreamClientInterface;

/// @todo rename?
class DSProxy extends FixedUpstreamProxy
{
    const DEFAULT_UPSTREAM = '/var/run/docker.sock';

    /**
     * @todo... disallow http/https upstreams?
     * @throws \Exception
     */
    protected function setUpstream(string $upstream, UpstreamClientInterface|array|null $httpClient = null): UpstreamClientInterface
    {
        return parent::setUpstream($upstream, $httpClient);
    }
}
