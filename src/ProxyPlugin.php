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
use ReflectionException;

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
     * @var array
     */
    protected $reflectionCache = [];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PRE_FILE_DOWNLOAD => [
                ['onPluginPreFileDownload', PHP_INT_MIN],
            ],
        ];
    }

    /**
     * Get the server proxy name of $_SERVER.
     *
     * @return array
     */
    protected function getProxyEnvNames(): array
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
    protected function getProxyProtocol(): array
    {
        return [
            'http'  => ['http_proxy', 'HTTP_PROXY', 'CGI_HTTP_PROXY'],
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
     *
     * @throws ReflectionException
     */
    public function onPluginPreFileDownload(PreFileDownloadEvent $event)
    {
        $this->clearProxyEnv();
        $this->reductionProxyEnv();
        $this->setConfigProxies($event->getProcessedUrl());

        /** composer2.* */
        if (class_exists(ProxyManager::class, false)) {
            $this->resetProxyManager($event);
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function resetProxyManager(PreFileDownloadEvent $event)
    {
        $httpDownloader = $event->getHttpDownloader();

        $reflection = new ReflectionClass($httpDownloader);
        $curlProperty = $reflection->getProperty('curl');
        $curlProperty->setAccessible(true);
        $curlDownloader = $curlProperty->getValue($httpDownloader);

        $reflection = new ReflectionClass($curlDownloader);
        $proxyManagerProperty = $reflection->getProperty('proxyManager');
        $proxyManagerProperty->setAccessible(true);
        $proxyManager = $proxyManagerProperty->getValue($curlDownloader);

        $reflection = new ReflectionClass($proxyManager);

        $fullProxyProperty = $reflection->getProperty('fullProxy');
        $fullProxyProperty->setAccessible(true);
        $fullProxyProperty->setValue($proxyManager, ['http' => null, 'https' => null]);

        $safeProxyProperty = $reflection->getProperty('safeProxy');
        $safeProxyProperty->setAccessible(true);
        $safeProxyProperty->setValue($proxyManager, ['http' => null, 'https' => null]);

        $streamsProperty = $reflection->getProperty('streams');
        $streamsProperty->setAccessible(true);
        $streamsProperty->setValue($proxyManager, ['http' => ['options' => null], 'https' => ['options' => null]]);

        $hasProxyProperty = $reflection->getProperty('hasProxy');
        $hasProxyProperty->setAccessible(true);
        $hasProxyProperty->setValue($proxyManager, false);

        $initProxyDataMethod = $reflection->getMethod('initProxyData');
        $initProxyDataMethod->setAccessible(true);
        $initProxyDataMethod->invoke($proxyManager);
    }

    /**
     * Set up proxies according to configuration.
     *
     * @param string $url
     */
    protected function setConfigProxies(string $url)
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
    }

    /**
     * @inheritDoc
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
