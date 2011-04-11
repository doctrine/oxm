<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type;

class IntegerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\IntegerType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('integer');
    }

    public function testName()
    {
        $this->assertEquals('integer', $this->_type->getName());
    }

    public function testIntegerConvertsToPHPValue()
    {
        $this->assertInternalType('integer', $this->_type->convertToPHPValue('3.14'));
        $this->assertInternalType('integer', $this->_type->convertToPHPValue('1'));
        $this->assertInternalType('integer', $this->_type->convertToPHPValue('0'));
    }

    public function testIntegerNullConvertsToPHPValue()
    {
        $this->assertNull($this->_type->convertToPHPValue(null));
    }
}