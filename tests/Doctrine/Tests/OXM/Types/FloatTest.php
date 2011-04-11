<?php

namespace Doctrine\Tests\OXM\Types;

use Doctrine\OXM\Types\Type;

class FloatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Types\IntegerType
     */
    protected $_type;

    protected function setUp()
    {
        $this->_type = Type::getType('float');
    }

    public function testName()
    {
        $this->assertEquals('float', $this->_type->getName());
    }

    public function testIntegerConvertsToPHPValue()
    {
        $this->assertInternalType('float', $this->_type->convertToPHPValue('1'));
        $this->assertInternalType('float', $this->_type->convertToPHPValue('1.1'));
        $this->assertInternalType('float', $this->_type->convertToPHPValue('0'));
    }

    public function testIntegerNullConvertsToPHPValue()
    {
        $this->assertNull($this->_type->convertToPHPValue(null));
    }
}