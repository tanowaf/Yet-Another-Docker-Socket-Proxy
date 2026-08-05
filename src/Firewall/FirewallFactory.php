<?php

namespace TanoWAF\YaDSPFirewall;

use TanoWAF\WAFCore\Firewall\FirewallFactory as BaseFirewallFactory;

class FirewallFactory extends BaseFirewallFactory
{
    protected array|null $fallbackConfiguration = null;

    /**
     * @param array $config
     * @return Firewall
     * @throws \Exception
     */
    public function fromConfiguration(array $config): Firewall
    {
        if (!$config) {
            $this->warning("Empty configuration passed in. The firewall will only let trough 'ping' API calls");
        }

        foreach($config as $ruleName => $ruleConfig) {
            if (!is_array($ruleConfig)) {
                throw new \Exception("Bad configuration: the value for firewall rule '$ruleName' should be an array");
            }
        }

        if (array_key_exists('*', $config)) {
            // add the fallback rules
            $fallbackConfig = $config['*'] + $this->getFallbackConfiguration();
            // make sure that this is the last rule
            unset($config['*']);
            $config['*'] = $fallbackConfig;

        } else {
            $config['*'] = $this->getFallbackConfiguration();
        }

        $ruleFactory = new RuleFactory($this->logger);
        $rules = [];
        foreach($config as $ruleName => $ruleSpec) {
            try {
                $rule = $ruleFactory->fromConfiguration($ruleSpec);
                $rules[$ruleName] = $rule;
            } catch (\Exception $e) {
                throw new \Exception("Error parsing firewall rule '$ruleName': " . $e->getMessage());
            }
        }

        return new Firewall($rules, $this->logger);
    }

    /**
     * Returns the default filter applied to all clients - let ping and version requests through
     * @return array
     */
    public function getFallbackConfiguration(): array
    {
        return is_array($this->fallbackConfiguration) ? $this->fallbackConfiguration : Firewall::DefaultFallbackConfiguration;
    }

    public function setFallbackConfiguration(array $config): void
    {
        /// @todo validate the config now instead of relying on static::fromConfiguration
        $this->fallbackConfiguration = $config;
    }
}
