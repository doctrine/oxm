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

use Doctrine\Tests\OxmTestCase;
use Doctrine\OXM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;


class ClassMetadataInfoTest extends OxmTestCase
{
    /**
     * @var ClassMetadataInfo
     */
    protected $class;

    public function setUp()
    {
        $this->class = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\Entity');
        $reflService = new RuntimeReflectionService();
        $this->class->initializeReflection($reflService);
        $this->class->wakeupReflection($reflService);
    }

    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnRequiredFieldName()
    {
        $this->class->mapField(array());
    }
    
    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnRequiredType()
    {
        $this->class->mapField(array('fieldName' => 'squibble'));
    }

    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnDuplicateFieldNames()
    {
        $this->class->mapField(array('fieldName' => 'squibble', 'type' => 'string'));
        $this->class->mapField(array('fieldName' => 'squibble', 'type' => 'string'));
    }
    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnUnknownNodeType()
    {
        $this->class->mapField(array('fieldName' => 'squibble', 'type' => 'string', 'node' => 'fleet'));
    }

    /**
     * @test
     * @expectedException Doctrine\OXM\Mapping\MappingException
     */
    public function itShouldThrowExceptionsOnDuplicateXmlFieldNames()
    {
        $this->class->mapField(array('fieldName' => 'squibbleMe', 'type' => 'string', 'name' => 'this'));
        $this->class->mapField(array('fieldName' => 'squibbleYou', 'type' => 'string', 'name' => 'this'));
    }

    public function itShouldInferGettersProperly()
    {
        $this->class->mapField(array('fieldName' => 'squibble', 'type' => 'string'));

        $mapping = $this->class->getFieldMapping('squibble'); // todo - don't like relying on internal implementation
        $this->assertEquals('getSquibble', $mapping['getMethod']);
        $this->assertEquals('setSquibble', $mapping['setMethod']);
    }

    /**
     * @test
     */
    public function itShouldIdentifyIdentifiers()
    {
        $this->class->mapField(array(
            'fieldName' => 'squibble',
            'type' => 'string',
            'id' => true,
        ));

        $this->assertEquals('squibble', $this->class->identifier);
        $this->assertEquals('squibble', $this->class->getIdentifier());
        $this->assertTrue($this->class->isIdentifier('squibble'));
        $this->assertFalse($this->class->isIdentifier('not_squibble'));
    }

    /**
     * @test
     */
    public function itShouldMapProperly()
    {
        $this->assertEquals('Doctrine\Tests\OXM\Mapping\Entity', $this->class->getName());

        $this->class->mapField(array(
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

        $this->assertTrue($this->class->hasField('squibble'));
        $this->assertEquals('string', $this->class->getTypeOfField('squibble'));
        $this->assertTrue($this->class->isRequired('squibble'));
        $this->assertTrue($this->class->isDirect('squibble'));
        $this->assertFalse($this->class->isCollection('squibble'));
        $this->assertFalse($this->class->isNullable('squibble'));
    }
}
