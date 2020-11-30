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
use HughCube\Composer\ProxyPlugin\Config\Config;
use HughCube\Composer\ProxyPlugin\Config\ConfigBuilder;

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
    protected $originProxy = [];

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
    protected function getServerProxyNames()
    {
        return [
            'http'  => ['HTTP_PROXY', 'CGI_HTTP_PROXY'],
            'https' => ['HTTPS_PROXY', 'CGI_HTTPS_PROXY'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->config = ConfigBuilder::build($composer, $io);
        $this->recordOriginProxies();
    }

    /**
     * Handling events for downloading files.
     *
     * @param PreFileDownloadEvent $event
     */
    public function onPluginPreFileDownload(PreFileDownloadEvent $event)
    {
        $this->reductionOriginProxies();
        $this->setConfigProxies($event->getProcessedUrl());
    }

    /**
     * Set up proxies according to configuration.
     *
     * @param string $url
     */
    protected function setConfigProxies($url)
    {
        foreach ($this->getServerProxyNames() as $protocol => $names) {
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
     * Record origin proxies.
     */
    protected function recordOriginProxies()
    {
        foreach ($this->getServerProxyNames() as $protocol => $names) {
            foreach ($names as $name) {
                if (array_key_exists($name, $_SERVER)) {
                    $this->originProxy[$protocol][$name] = $_SERVER[$name];
                }
            }
        }
    }

    /**
     * Reduction origin proxies.
     */
    protected function reductionOriginProxies()
    {
        foreach ($this->getServerProxyNames() as $protocol => $names) {
            foreach ($names as $name) {
                if (array_key_exists($name, $_SERVER)) {
                    unset($_SERVER[$name]);
                }

                if (isset($this->originProxy[$protocol]) && array_key_exists($name, $this->originProxy[$protocol])) {
                    $_SERVER[$name] = $this->originProxy[$protocol][$name];
                }
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
