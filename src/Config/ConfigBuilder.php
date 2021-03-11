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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;

/**
 * Plugin Config builder.
 *
 * @author hugh.li <hugh.li@foxmail.com>
 */
class ConfigBuilder
{
    /**
     * @var Config
     */
    protected static $config;

    /**
     * Build the config of plugin.
     *
     * @param Composer $composer The composer
     * @param null|IOInterface $io The composer input/output
     *
     * @return Config
     */
    public static function build(Composer $composer, $io = null)
    {
        if (!static::$config instanceof Config) {
            $config = self::getConfigBase($composer, $io);
            static::$config = new Config($config);
        }

        return static::$config;
    }

    /**
     * Get the base of data.
     *
     * @param Composer $composer The composer
     * @param null|IOInterface $io The composer input/output
     *
     * @return array
     */
    private static function getConfigBase(Composer $composer, $io = null)
    {
        $globalPackageConfig = self::getGlobalConfig($composer, 'composer', $io);
        $globalConfig = self::getGlobalConfig($composer, 'config', $io);
        $packageConfig = self::drawProxyConfig($composer->getPackage()->getConfig());

        return array_merge($globalPackageConfig, $globalConfig, $packageConfig);
    }

    /**
     * Get the data of the global config.
     *
     * @param Composer $composer The composer
     * @param string $filename The filename
     * @param null|IOInterface $io The composer input/output
     *
     * @return array
     */
    private static function getGlobalConfig(Composer $composer, $filename, $io = null)
    {
        $config = [];

        $home = self::getComposerHome($composer);
        if (false == $home) {
            return $config;
        }

        $file = new JsonFile($home . '/' . $filename . '.json');
        if (!$file->exists()) {
            return $config;
        }

        $config = self::drawProxyConfig($file->read());

        if (!empty($config) && $io instanceof IOInterface && $io->isDebug()) {
            $io->write('Loading proxies config in file ' . $file->getPath());
        }

        return $config;
    }

    /**
     * Get the home directory of composer.
     *
     * @param Composer $composer The composer
     *
     * @return string|null
     */
    private static function getComposerHome(Composer $composer)
    {
        if (null == $composer->getConfig()) {
            return null;
        }

        if (!$composer->getConfig()->has('home')) {
            return null;
        }

        return $composer->getConfig()->get('home');
    }

    /**
     * Draw the config of proxy.
     *
     * @param mixed $data
     *
     * @return array
     */
    private static function drawProxyConfig($data)
    {
        if (isset($data['config'], $data['config']['proxies']) && is_array($data['config']['proxies'])) {
            return $data['config']['proxies'];
        }

        return [];
    }
}
