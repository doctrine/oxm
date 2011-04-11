<?php

namespace Doctrine\Tests\OXM\Util;

use Doctrine\OXM\Util\Inflector;

class InflectorTest extends \PHPUnit_Framework_TestCase
{
    public function testXmlize()
    {
        $this->assertEquals('xxx-yyy', Inflector::xmlize('XxxYYY'));
        $this->assertEquals('with-many-many-words', Inflector::xmlize('withManyManyWords'));
    }
}