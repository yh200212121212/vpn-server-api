<?php

require_once 'vendor/autoload.php';

use fkooman\VPN\Server\ZeroTier\ZeroTier;

$zt = new ZeroTier('http://localhost:9993', 'f50a89be2d', 'ypf7adpsl7e3ple3uhq4fciq');

var_dump($zt->getNetworks('foo'));

var_dump($zt->addNetwork('foo', 'xyzfoo'));
