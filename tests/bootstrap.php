<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/22/11
 * Time: 2:50 PM
 * To change this template use File | Settings | File Templates.
 */

// execution point is at doctrine-oxm/
require_once __DIR__.'/../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', realpath(__DIR__.'/../lib/vendor/doctrine-common/lib'));
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine\OXM', realpath(__DIR__.'/../lib'));
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine\Tests', realpath(__DIR__.'/../tests'));
$classLoader->register();