<?php
declare(strict_types=1);

namespace TanoWAF\YaDSP\Firewall;

use TanoWAF\YaDSP\Matcher\Request\MatcherFactory as RequestMatcherFactory;
use TanoWAF\YaDSP\Matcher\Response\MatcherFactory as ResponseMatcherFactory;
use TanoWAF\WAFCore\Firewall\RuleFactory as BaseRuleFactory;
use TanoWAF\WAFCore\Matcher\ChainFactory;
use TanoWAF\WAFCore\Matcher\Logic\MatcherFactory as LogicMatcherFactory;
use TanoWAF\WAFCore\Matcher\MatcherFactoryInterface;

class RuleFactory extends BaseRuleFactory
{
    /**
     * Same as parent, but return our own type of Factory.
     * @param array $config
     * @return MatcherFactoryInterface
     * @throws \Exception
     */
    protected function getRequestMatcherFactory(array $config): MatcherFactoryInterface
    {
        if ($this->requestMatcherFactory === null) {
            $logicMatcherFactory = new LogicMatcherFactory($this->logger);
            $this->requestMatcherFactory = new ChainFactory([new RequestMatcherFactory($this->headerParserFactory, $this->logger), $logicMatcherFactory]);
            // inception! ;-)
            $logicMatcherFactory->setMatcherFactory($this->requestMatcherFactory);
        }
        return $this->requestMatcherFactory;
    }

    /**
     * Same as parent, but return our own type of Factory.
     * @param array $config
     * @return MatcherFactoryInterface
     * @throws \Exception
     */
    protected function getResponseMatcherFactory(array $config): MatcherFactoryInterface
    {
        if ($this->responseMatcherFactory === null) {
            $logicMatcherFactory = new LogicMatcherFactory($this->logger);
            $this->responseMatcherFactory = new ChainFactory([new ResponseMatcherFactory($this->headerParserFactory, $this->logger), $logicMatcherFactory]);
            // inception! ;-)
            $logicMatcherFactory->setMatcherFactory($this->responseMatcherFactory);
        }
        return $this->responseMatcherFactory;
    }
}
