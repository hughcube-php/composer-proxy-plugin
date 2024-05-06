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
     * @param  array  $proxies  The config
     */
    public function __construct(array $proxies)
    {
        $this->proxies = $proxies;
    }

    /**
     * Get the http proxy of url.
     *
     * @param  string  $url
     * @param  bool  $isSsl
     *
     * @return null|string
     */
    public function getHttpProxy($url, $isSsl = false)
    {
        $protocol = $isSsl ? 'https' : 'http';

        $proxy = $this->getProxy($url, $protocol);
        if (!is_array($proxy)) {
            return null;
        }

        return Url::instance()
            ->withScheme((isset($proxy['protocol']) ? $proxy['protocol'] : null))
            ->withHost((isset($proxy['host']) ? $proxy['host'] : null))
            ->withPort((isset($proxy['port']) ? $proxy['port'] : null))
            ->withUserInfo(
                (isset($proxy['username']) ? $proxy['username'] : null),
                (isset($proxy['password']) ? $proxy['password'] : null)
            )
            ->toString();
    }

    /**
     * Get the https proxy of url.
     *
     * @param  string  $url
     *
     * @return null|string
     */
    public function getHttpsProxy($url)
    {
        return $this->getHttpProxy($url, true);
    }

    /**
     * Get the proxy of url and protocol.
     *
     * @param  string  $url
     * @param  string  $protocol
     *
     * @return null|array
     */
    private function getProxy($url, $protocol)
    {
        $url = Url::instance($url);

        if (empty($this->proxies)) {
            $proxyUrl = Url::parse(isset($_SERVER['COMPOSER_PROXY']) ? $_SERVER['COMPOSER_PROXY'] : null);
            if ($proxyUrl instanceof Url) {
                return array(
                    'protocol' => $proxyUrl->getScheme(),
                    'host' => $proxyUrl->getHost(),
                    'port' => $proxyUrl->getPort(),
                    'username' => $proxyUrl->getUser(),
                    'password' => $proxyUrl->getPass()
                );
            }
        }

        foreach ($this->proxies as $proxy) {
            if (isset($proxy['active']) && !$proxy['active']) {
                continue;
            }

            if ($protocol !== $proxy['protocol']) {
                //continue;
            }

            $nonProxyHosts = isset($proxy['nonProxyHosts']) ? $proxy['nonProxyHosts'] : null;
            if (null != $nonProxyHosts && $url->matchHost($proxy['nonProxyHosts'])) {
                continue;
            }

            $proxyHosts = isset($proxy['proxyHosts']) ? $proxy['proxyHosts'] : null;
            if (null != $proxyHosts && !$url->matchHost($proxy['proxyHosts'])) {
                continue;
            }

            return $proxy;
        }

        return null;
    }
}
