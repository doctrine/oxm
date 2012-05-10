<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
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
            'nullable' => false,
            'getMethod' => 'getSquibble',
            'setMethod' => 'setSquibble',
        ));

        $this->assertTrue($class->hasField('squibble'));
        $this->assertEquals('string', $class->getTypeOfField('squibble'));
        $this->assertTrue($class->isRequired('squibble'));
        $this->assertTrue($class->isDirect('squibble'));
        $this->assertFalse($class->isCollection('squibble'));
        $this->assertFalse($class->isNullable('squibble'));
    }
}
