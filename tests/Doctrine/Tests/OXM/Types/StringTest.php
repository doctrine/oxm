<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type;
 
class StringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\StringType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('string');
    }

    public function testName()
    {
        $this->assertEquals('string', $this->_type->getName());
    }

    public function testConvertToPHPValue()
    {
        $this->assertInternalType("string", $this->_type->convertToPHPValue("foo"));
        $this->assertInternalType("string", $this->_type->convertToPHPValue(""));
    }

    public function testNullConversion()
    {
        $this->assertNull($this->_type->convertToPHPValue(null));
    }
}
