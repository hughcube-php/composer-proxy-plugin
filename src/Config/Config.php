<?php

/*
 * This file is part of the Composer Proxy Plugin package.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HughCube\Composer\ProxyPlugin\Config;

use HughCube\PUrl\Url;

/**
 * Helper of package config.
 *
 * @author hugh.li <hugh.li@foxmail.com>
 */
final class Config
{
    /**
     * @var array proxies
     */
    private $proxies;

    /**
     * Constructor.
     *
     * @param array $proxies The config
     */
    public function __construct(array $proxies)
    {
        $this->proxies = $proxies;
    }

    /**
     * Get the http proxy of url.
     *
     * @param string $url
     * @param bool   $isSsl
     *
     * @return null|string
     */
    public function getHttpProxy(string $url, bool $isSsl = false)
    {
        $protocol = $isSsl ? 'https' : 'http';

        $proxy = $this->getProxy($url, $protocol);
        if (!is_array($proxy)) {
            return null;
        }

        return Url::instance()
            ->withScheme(($proxy['protocol'] ?? null))
            ->withHost(($proxy['host'] ?? null))
            ->withPort(($proxy['port'] ?? null))
            ->withUserInfo(($proxy['username'] ?? null), ($proxy['password'] ?? null))
            ->toString();
    }

    /**
     * Get the https proxy of url.
     *
     * @param string $url
     *
     * @return null|string
     */
    public function getHttpsProxy(string $url)
    {
        return $this->getHttpProxy($url, true);
    }

    /**
     * Get the proxy of url and protocol.
     *
     * @param string $url
     * @param string $protocol
     *
     * @return null|array
     */
    private function getProxy(string $url, string $protocol)
    {
        $url = Url::instance($url);

        foreach ($this->proxies as $proxy) {
            if (isset($proxy['active']) && !$proxy['active']) {
                continue;
            }

            if ($protocol !== $proxy['protocol']) {
                continue;
            }

            $nonProxyHosts = $proxy['nonProxyHosts'] ?? null;
            if (null != $nonProxyHosts && $url->matchHost($proxy['nonProxyHosts'])) {
                continue;
            }

            $proxyHosts = $proxy['proxyHosts'] ?? null;
            if (null != $proxyHosts && !$url->matchHost($proxy['proxyHosts'])) {
                continue;
            }

            return $proxy;
        }

        return null;
    }
}
