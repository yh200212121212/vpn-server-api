<?php

require_once 'vendor/autoload.php';

use fkooman\VPN\Server\ZeroTier\ZeroTier;

$zt = new ZeroTier('http://localhost:9993', 'id', 'token');
var_dump($zt->addNetwork('foo', 'xyzfoo'));
