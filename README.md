<h1 align="center">composer download file proxy plugin</h1>


<p>
    <a href="https://github.com/hughcube-php/composer-proxy-plugin/actions?query=workflow%3ATest">
        <img src="https://github.com/hughcube-php/composer-proxy-plugin/workflows/Test/badge.svg" alt="Test Actions status">
    </a>
    <a href="https://github.com/hughcube-php/composer-proxy-plugin/actions?query=workflow%3ALint">
        <img src="https://github.com/hughcube-php/composer-proxy-plugin/workflows/Lint/badge.svg" alt="Lint Actions status">
    </a>
    <a href="https://github.styleci.io/repos/317102477">
        <img src="https://github.styleci.io/repos/317102477/shield?branch=master" alt="StyleCI">
    </a>
    <a href="https://scrutinizer-ci.com/g/hughcube-php/composer-proxy-plugin/?branch=master">
        <img src="https://scrutinizer-ci.com/g/hughcube-php/composer-proxy-plugin/badges/coverage.png?b=master" alt="Code Coverage">
    </a>
    <a href="https://scrutinizer-ci.com/g/hughcube-php/composer-proxy-plugin/?branch=master">
        <img src="https://scrutinizer-ci.com/g/hughcube-php/composer-proxy-plugin/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality">
    </a> 
    <a href="https://scrutinizer-ci.com/g/hughcube-php/composer-proxy-plugin/?branch=master">
        <img src="https://scrutinizer-ci.com/g/hughcube-php/composer-proxy-plugin/badges/code-intelligence.svg?b=master" alt="Code Intelligence Status">
    </a>        
    <a href="https://github.com/hughcube-php/composer-proxy-plugin">
        <img src="https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg" alt="PHP Versions Supported">
    </a>
    <a href="https://packagist.org/packages/hughcube/composer-proxy-plugin">
        <img src="https://poser.pugx.org/hughcube/composer-proxy-plugin/version" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/hughcube/composer-proxy-plugin">
        <img src="https://poser.pugx.org/hughcube/composer-proxy-plugin/downloads" alt="Total Downloads">
    </a>
    <a href="https://github.com/hughcube-php/composer-proxy-plugin/blob/master/LICENSE">
        <img src="https://img.shields.io/badge/license-MIT-428f7e.svg" alt="License">
    </a>
    <a href="https://packagist.org/packages/hughcube/composer-proxy-plugin">
        <img src="https://poser.pugx.org/hughcube/composer-proxy-plugin/v/unstable" alt="Latest Unstable Version">
    </a>
    <a href="https://packagist.org/packages/hughcube/composer-proxy-plugin">
        <img src="https://poser.pugx.org/hughcube/composer-proxy-plugin/composerlock" alt="composer.lock available">
    </a>
</p>

## Installing

```shell
$ composer global require hughcube/composer-proxy-plugin -vvv
```

## Set config in composer.json   or   ~/.composer/config.json
```json
{
  "config": {
    "proxies": [
      {
        "active": true,
        "protocol": "http",
        "nonProxyHosts": null,
        "proxyHosts": "packagist.org|*.packagist.org",
        "host": "127.0.0.1",
        "port": "1087",
        "username": null,
        "password": null
      }
    ]
  }
}

```
