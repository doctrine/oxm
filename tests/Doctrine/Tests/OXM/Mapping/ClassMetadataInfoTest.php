<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/4/11
 * Time: 11:47 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Mapping;

use \Doctrine\Tests\OxmTestCase,
    \Doctrine\OXM\Mapping\ClassMetadataInfo;

class ClassMetadataInfoTest extends OxmTestCase
{
    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnRequiredFieldName()
    {
        $class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');
        $class->mapField(array());
    }
    
    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnRequiredType()
    {
        $class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');
        $class->mapField(array('fieldName' => 'squibble'));
    }

    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnDuplicateFieldNames()
    {
        $class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');
        $class->mapField(array('fieldName' => 'squibble', 'type' => 'string'));
        $class->mapField(array('fieldName' => 'squibble', 'type' => 'string'));
    }
    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnUnknownNodeType()
    {
        $class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');
        $class->mapField(array('fieldName' => 'squibble', 'type' => 'string', 'node' => 'fleet'));
    }

    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnDuplicateXmlFieldNames()
    {
        $class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');
        $class->mapField(array('fieldName' => 'squibbleMe', 'type' => 'string', 'name' => 'this'));
        $class->mapField(array('fieldName' => 'squibbleYou', 'type' => 'string', 'name' => 'this'));
    }

    public function itShouldInferGettersProperly()
    {
        $class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');
        $class->mapField(array('fieldName' => 'squibble', 'type' => 'string'));

        $mapping = $class->getFieldMapping('squibble'); // todo - don't like relying on internal implementation
        $this->assertEquals('getSquibble', $mapping['getMethod']);
        $this->assertEquals('setSquibble', $mapping['setMethod']);
    }

    /**
     * @test
     */
    public function itShouldIdentifyIdentifiers()
    {
        $class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');
        $class->mapField(array(
            'fieldName' => 'squibble',
            'type' => 'string',
            'id' => true,
        ));

        $this->assertEquals('squibble', $class->identifier);
        $this->assertEquals('squibble', $class->getIdentifier());
        $this->assertTrue($class->isIdentifier('squibble'));
        $this->assertFalse($class->isIdentifier('not_squibble'));
    }

    /**
     * @test
     */
    public function itShouldMapProperly()
    {

        $class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');

        $this->assertEquals('Doctrine\Tests\OXM\Mapping\Entity', $class->getName());

        $class->mapField(array(
            'fieldName' => 'squibble',
            'type' => 'string',
            'required' => true,
            'direct' => true,
            'id' => true,
            'collection' => false,
            'nillable' => false,
            'getMethod' => 'getSquibble',
            'setMethod' => 'setSquibble',
        ));

        $this->assertTrue($class->hasField('squibble'));
        $this->assertEquals('string', $class->getTypeOfField('squibble'));
        $this->assertTrue($class->isRequired('squibble'));
        $this->assertTrue($class->isDirect('squibble'));
        $this->assertFalse($class->isCollection('squibble'));
        $this->assertFalse($class->isNillable('squibble'));
    }
}
