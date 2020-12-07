<?php

/*
 * This file is part of the Composer Proxy Plugin package.
 *
 * (c) hugh.li <hugh.li@foxmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HughCube\Composer\ProxyPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\Util\Http\ProxyManager;
use HughCube\Composer\ProxyPlugin\Config\Config;
use HughCube\Composer\ProxyPlugin\Config\ConfigBuilder;
use ReflectionClass;

/**
 * Composer plugin.
 *
 * @author hugh.li <hugh.li@foxmail.com>
 */
class ProxyPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $originProxyEnv = [];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var ReflectionClass
     */
    protected $proxyManagerReflection;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PRE_FILE_DOWNLOAD => [
                ['onPluginPreFileDownload', 0],
            ],
        ];
    }

    /**
     * Get the server proxy name of $_SERVER.
     *
     * @return array
     */
    protected function getProxyEnvNames()
    {
        return [
            'http_proxy',
            'HTTP_PROXY',
            'CGI_HTTP_PROXY',

            'https_proxy',
            'HTTPS_PROXY',
            'CGI_HTTPS_PROXY',

            'no_proxy',
            'NO_PROXY',
        ];
    }

    /**
     * Get the proxy protocol.
     *
     * @return array
     */
    protected function getProxyProtocol()
    {
        return [
            'http' => ['http_proxy', 'HTTP_PROXY', 'CGI_HTTP_PROXY'],
            'https' => ['https_proxy', 'HTTPS_PROXY', 'CGI_HTTPS_PROXY'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->config = ConfigBuilder::build($composer, $io);
        $this->recordProxyEnv();
    }

    /**
     * Handling events for downloading files.
     *
     * @param PreFileDownloadEvent $event
     */
    public function onPluginPreFileDownload(PreFileDownloadEvent $event)
    {
        $this->clearProxyEnv();
        $this->reductionProxyEnv();
        $this->setConfigProxies($event->getProcessedUrl());

        if (class_exists(ProxyManager::class, false)) {
            $this->resetProxyManager();
        }
    }

    protected function resetProxyManager()
    {
        static $reflection,
        $fullProxyProperty,
        $safeProxyProperty,
        $streamsProperty,
        $hasProxyProperty,
        $initProxyDataMethod;

        if (null === $reflection) {
            $reflection = new ReflectionClass(ProxyManager::class);

            $fullProxyProperty = $reflection->getProperty('fullProxy');
            $fullProxyProperty->setAccessible(true);

            $safeProxyProperty = $reflection->getProperty('safeProxy');
            $safeProxyProperty->setAccessible(true);

            $streamsProperty = $reflection->getProperty('streams');
            $streamsProperty->setAccessible(true);

            $hasProxyProperty = $reflection->getProperty('hasProxy');
            $hasProxyProperty->setAccessible(true);

            $initProxyDataMethod = $reflection->getMethod('initProxyData');
            $initProxyDataMethod->setAccessible(true);
        }


        $proxyManager = ProxyManager::getInstance();
        $fullProxyProperty->setValue($proxyManager, ['http' => null, 'https' => null]);
        $safeProxyProperty->setValue($proxyManager, ['http' => null, 'https' => null]);
        $streamsProperty->setValue($proxyManager, ['http' => ['options' => null], 'https' => ['options' => null]]);
        $hasProxyProperty->setValue($proxyManager, false);
        $initProxyDataMethod->invoke($proxyManager);
    }

    /**
     * Set up proxies according to configuration.
     *
     * @param string $url
     */
    protected function setConfigProxies($url)
    {
        foreach ($this->getProxyProtocol() as $protocol => $names) {
            $method = "get{$protocol}Proxy";
            if (!method_exists($this->config, $method)) {
                continue;
            }

            $proxy = call_user_func([$this->config, $method], $url);
            if (null == $proxy) {
                continue;
            }

            foreach ($names as $name) {
                $_SERVER[$name] = $proxy;
            }
        }
    }

    /**
     * Record proxy env.
     */
    protected function recordProxyEnv()
    {
        foreach ($this->getProxyEnvNames() as $name) {
            if (array_key_exists($name, $_SERVER)) {
                $this->originProxyEnv[$name] = $_SERVER[$name];
            }
        }
    }

    /**
     * Reduction proxy env.
     */
    protected function reductionProxyEnv()
    {
        foreach ($this->getProxyEnvNames() as $name) {
            if (array_key_exists($name, $this->originProxyEnv)) {
                $_SERVER[$name] = $this->originProxyEnv[$name];
            }
        }
    }

    /**
     * clear proxy env.
     */
    protected function clearProxyEnv()
    {
        foreach ($this->getProxyEnvNames() as $name) {
            if (array_key_exists($name, $_SERVER)) {
                unset($_SERVER[$name]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        return true;
    }
}
