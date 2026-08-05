<?php
declare(strict_types=1);

namespace TanoWAF\YaDSP\Matcher\Request;

use TanoWAF\WAFCore\Matcher\Request\PathMatcher as BasePathMatcher;

/// @todo... sync with upstream
class PathMatcher extends BasePathMatcher
{
    /**
     * @param string|string[] $filter
     * @throws \Exception
     */
    public function __construct(string|array $filter)
    {
        parent::__construct($filter, '(/v[0-9.]+/)?');
    }
}
