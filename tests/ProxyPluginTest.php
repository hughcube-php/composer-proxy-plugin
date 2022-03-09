<?php

namespace HughCube\Composer\ProxyPlugin\Tests;

use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginInterface;
use HughCube\Composer\ProxyPlugin\ProxyPlugin;
use PHPUnit\Framework\TestCase;

class ProxyPluginTest extends TestCase
{
    public function testInstance()
    {
        $plugin = new  ProxyPlugin();

        $this->assertInstanceOf(PluginInterface::class, $plugin);
        $this->assertInstanceOf(EventSubscriberInterface::class, $plugin);
    }
}
