<?php
use \Symfony\Component\ClassLoader\UniversalClassLoader;
 
define('__VENDOR_DIR__', __DIR__ . '/../../../../vendor');

$loader = require_once __VENDOR_DIR__ . '/autoload.php';
require_once __VENDOR_DIR__ . '/atoum/mageekguy.atoum.phar';

$loader->add('Quartz', __VENDOR_DIR__ . '/quartz/Quartz'); 
$loader->register();