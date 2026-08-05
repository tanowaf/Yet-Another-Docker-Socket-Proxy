<?php
declare(strict_types=1);

namespace TanoWAF\YaDSPFirewall;

use TanoWAF\WAFCore\Firewall\Firewall as baseFirewall;

/**
 * The class doing the actual filtering of Requests and Responses
 */
class Firewall extends baseFirewall
{
    public const DefaultFallbackConfiguration = [
        'req_match' => [
            'url_path' => '/_ping', // /version gets disabled out of the box - in case the version number might be useful to attackers...
            'http_method' => ['GET', 'HEAD'],
        ],
        'req_filters' => [],
        'req_action' => 'allow',
        'resp_match' => ['always' => true],
        'resp_action' => 'allow',
        'resp_filters' => [],
    ];
}
