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

use Doctrine\OXM\Mapping\ClassMetadata;
use Doctrine\OXM\Mapping\ClassMetadataInfo;
use Doctrine\OXM\Mapping\Driver\Driver;

abstract class AbstractMappingDriverTest extends \Doctrine\Tests\OxmTestCase
{
    /**
     * @return \Doctrine\OXM\Mapping\Driver\Driver
     */
    abstract protected function _loadDriver();

    public function createClassMetadata($entityClassName)
    {
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($entityClassName);
        $mappingDriver->loadMetadataForClass($entityClassName, $class);

        return $class;
    }

    public function testLoadMapping()
    {
        $entityClassName = 'Doctrine\Tests\OXM\Mapping\User';
        return $this->createClassMetadata($entityClassName);
    }

    /**
     * @depends testLoadMapping
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testEntityXmlName($class)
    {
        $this->assertEquals('cms-user', $class->getXmlName());

        return $class;
    }

    /**
     * @depends testLoadMapping
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testRootEntityXmlName($class)
    {
        $this->assertTrue($class->isRoot);

        return $class;
    }

    /**
     * @depends testEntityXmlName
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testNamespaces($class)
    {
        $this->assertEquals(2, count($class->getXmlNamespaces()));
        $this->assertContains(array('url' => 'http://www.schema.com/foo', 'prefix' => 'foo'), $class->getXmlNamespaces());
        $this->assertContains(array('url' => 'http://www.schema.com/bar', 'prefix' => 'bar'), $class->getXmlNamespaces());

        return $class;
    }

    /**
     * @depends testNamespaces
     * @param \Doctrine\OXM\Mapping\ClassMetadata  $class
     */
    public function testLifecycleCallbacks($class)
    {
        $this->assertEquals(count($class->lifecycleCallbacks), 3);
        $this->assertEquals($class->lifecycleCallbacks['prePersist'][0], 'doStuffOnPrePersist');
        $this->assertEquals($class->lifecycleCallbacks['postPersist'][0], 'doStuffOnPostPersist');
        $this->assertEquals($class->lifecycleCallbacks['preMarshal'][0], 'doStuffOnPreMarshal');

        return $class;
    }

    /**
     * @depends testLifecycleCallbacks
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testLifecycleCallbacksSupportMultipleMethodNames($class)
    {
        $this->assertEquals(count($class->lifecycleCallbacks['prePersist']), 2);
        $this->assertEquals($class->lifecycleCallbacks['prePersist'][1], 'doOtherStuffOnPrePersistToo');

        return $class;
    }

    /**
     * @depends testLifecycleCallbacksSupportMultipleMethodNames
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testFieldMappings($class)
    {
        $this->assertEquals(4, count($class->fieldMappings));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertTrue(isset($class->fieldMappings['comments']));
        $this->assertTrue(isset($class->fieldMappings['roles']));

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testStringFieldMappings($class)
    {
        $this->assertEquals('string', $class->fieldMappings['name']['type']);
        $this->assertEquals('text', $class->fieldMappings['name']['node']);

        return $class;
    }

    /**
     * @depends testStringFieldMappings
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testFieldMappingsFieldNames($class)
    {
        $this->assertEquals("id", $class->fieldMappings['id']['fieldName']);
        $this->assertEquals("name", $class->fieldMappings['name']['fieldName']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testFieldMappingsRequired($class)
    {
        $this->assertFalse($class->fieldMappings['id']['required']);
        $this->assertTrue($class->fieldMappings['name']['required']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testFieldMappingsCollection($class)
    {
        $this->assertFalse($class->fieldMappings['id']['collection']);
        $this->assertTrue($class->fieldMappings['comments']['collection']);
        $this->assertTrue($class->fieldMappings['roles']['collection']);

        $this->assertEquals('comments', $class->fieldMappings['comments']['wrapper']);
        $this->assertEquals('comment', $class->fieldMappings['comments']['name']);
        $this->assertEquals('comments', $class->fieldMappings['comments']['fieldName']);

        $this->assertEquals('role', $class->fieldMappings['roles']['name']);
        $this->assertEquals('roles', $class->fieldMappings['roles']['fieldName']);

        return $class;
    }


    /**
     * @depends testFieldMappings
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testFieldMappingsCustomSetsAndGets($class)
    {
        $this->assertEquals('setUsername', $class->fieldMappings['name']['setMethod']);
        $this->assertEquals('getUsername', $class->fieldMappings['name']['getMethod']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testIdentifier($class)
    {
        $this->assertEquals('id', $class->identifier);
//        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $class->generatorType, "ID-Generator is not ClassMetadata::GENERATOR_TYPE_AUTO");

        return $class;
    }

    public function testValueMapping()
    {
        $entityClassName = 'Doctrine\Tests\OXM\Mapping\Role';
        return $this->createClassMetadata($entityClassName);
    }

    /**
     * @depends testValueMapping
     * @param \Doctrine\OXM\Mapping\ClassMetadata $class
     */
    public function testValue($class)
    {
        $this->assertEquals('is-active', $class->fieldMappings['isActive']['name']);
        $this->assertEquals('isActive', $class->fieldMappings['isActive']['fieldName']);
        $this->assertEquals('boolean', $class->fieldMappings['isActive']['type']);
        $this->assertEquals('attribute', $class->fieldMappings['isActive']['node']);

        $this->assertEquals('name', $class->fieldMappings['name']['name']);
        $this->assertEquals('name', $class->fieldMappings['name']['fieldName']);
        $this->assertEquals('string', $class->fieldMappings['name']['type']);
        $this->assertEquals('value', $class->fieldMappings['name']['node']);

        return $class;
    }
}
