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

namespace Doctrine\OXM\Mapping;

use ReflectionException;

use Doctrine\OXM\OXMException;
use Doctrine\OXM\Configuration;
use Doctrine\OXM\Events;
use Doctrine\OXM\Event\LoadClassMetadataEventArgs;
use Doctrine\OXM\Types\Type;

use Doctrine\Common\Util\Debug;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as CommonClassMetadata;
use Doctrine\Common\Persistence\Mapping\ReflectionService;

/**
 * The ClassMetadataFactory is used to create Mapping objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a xml node.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /**
     * @var \Doctrine\OXM\Configuration
     */
    private $configuration;
    
    /**
     * @var \Doctrine\OXM\Mapping\Driver\Driver
     */
    private $driver;

    /**
     * @var \Doctrine\Common\EventManager
     */
    private $evm;

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cacheDriver;

    /**
     * Keys are mapped xml node names
     *
     * @var array
     */
    private $xmlToClassMap = array();

    /**
     * @param Configuration $configuration
     * @param EventManager|null $evm
     * @return null
     */
    public function __construct(Configuration $configuration, EventManager $evm = null)
    {
        $this->configuration = $configuration;
        $this->evm           = $evm;
    }

    /**
     * Preloads all metadata and returns an array of all known mapped node types
     *
     * @return array
     */
    public function getAllXmlNodes()
    {
        $this->getAllMetadata();

        return $this->xmlToClassMap;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize()
    {
        $this->cacheDriver = $this->configuration->getMetadataCacheImpl();
        $this->driver = $this->configuration->getMetadataDriverImpl();

        if (null === $this->evm) {
            $this->evm = new EventManager();
        }
        
        $this->initialized = true;
    }

    /**
     * Complete and validate type mappings
     *
     * @param string $className
     * @param ClassMetadataInfo $class
     */
    private function completeMappingTypeValidation($className, ClassMetadataInfo $class)
    {
        foreach ($class->fieldMappings as $fieldName => $mapping) {
            if (Type::hasType($mapping['type'])) {
                continue;
            }

            // Support type as a mapped class?
            if (!$this->hasMetadataFor($mapping['type']) && !$this->getMetadataFor($mapping['type'])) {
                throw MappingException::fieldTypeNotFound($className, $fieldName, $mapping['type']);
            }

            // Mapped classes must have binding node type XML_ELEMENT
            if ($mapping['node'] !== ClassMetadataInfo::XML_ELEMENT) {
                throw MappingException::customTypeWithoutNodeElement($className, $fieldName);
            }
        }
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->fieldMappings as $fieldName => $mapping) {
            if ( ! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }
            if ( ! isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->addInheritedFieldMapping($mapping);
        }
        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }

    /**
     * Completes the ID generator mapping. If "auto" is specified we choose the generator
     * most appropriate.
     *
     * @param \Doctrine\OXM\Mapping\ClassMetadataInfo $class
     */
    private function completeIdGeneratorMapping(ClassMetadataInfo $class)
    {
        $idGenType = $class->generatorType;
        if ($idGenType == ClassMetadata::GENERATOR_TYPE_AUTO) {
            $class->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
        }

        // Create & assign an appropriate ID generator instance
        switch ($class->generatorType) {
            case ClassMetadataInfo::GENERATOR_TYPE_INCREMENT:
                throw new OXMException("Increment generator type not implemented yet");
                break;
            case ClassMetadataInfo::GENERATOR_TYPE_NONE:
                $class->setIdGenerator(new \Doctrine\OXM\Id\AssignedGenerator());
                break;
            case ClassMetadataInfo::GENERATOR_TYPE_UUID:
                $class->setIdGenerator(new \Doctrine\OXM\Id\UuidGenerator());
                break;
            default:
                throw new OXMException("Unknown generator type: " . $class->generatorType);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        return $this->configuration->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    protected function wakeupReflection(CommonClassMetadata $class, ReflectionService $reflService)
    {
        /* @var $class ClassMetadata */
        $class->wakeupReflection($reflService);
        $this->completeMappingTypeValidation($class->getName(), $class);

        if ( ! $class->isMappedSuperclass) {
            $this->xmlToClassMap[$class->getXmlName()] = $class->getName();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeReflection(CommonClassMetadata $class, ReflectionService $reflService)
    {
        /* @var $class ClassMetadata */
        $class->initializeReflection($reflService);
    }

    /**
     * {@inheritDoc}
     */
    protected function isEntity(CommonClassMetadata $class)
    {
        /* @var $class ClassMetadata */
        return isset($class->isMappedSuperclass) && $class->isMappedSuperclass === false;
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
    {
        /* @var $class \Doctrine\OXM\Mapping\ClassMetadata */
        /* @var $parent \Doctrine\OXM\Mapping\ClassMetadata */
        if ($parent) {
            $class->setIdGeneratorType($parent->generatorType);
            $this->addInheritedFields($class, $parent);

            $class->setXmlNamespaces($parent->xmlNamespaces);
            $class->setIdentifier($parent->identifier);
            $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
            $class->setChangeTrackingPolicy($parent->changeTrackingPolicy);
        }

        // Invoke driver
        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getName(), $e);
        }

        if ( ! $class->isMappedSuperclass && in_array($class->getXmlName(), array_keys($this->xmlToClassMap))) {
            throw MappingException::duplicateXmlNameBinding($class->getName(), $class->getXmlName());
        }

        if ($parent && ! $parent->isMappedSuperclass) {
            if ($parent->generatorType) {
                $class->setIdGeneratorType($parent->generatorType);
            }
            if ($parent->idGenerator) {
                $class->setIdGenerator($parent->idGenerator);
            }
        } else {
            $this->completeIdGeneratorMapping($class);
        }

        $class->setParentClasses($nonSuperclassParents);

        // Todo - ensure that root elements have an ID mapped

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($class, $this);
            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }
    }
}
