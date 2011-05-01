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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\OXM\Mapping;

use ReflectionException;
use Doctrine\OXM\OXMException;
use Doctrine\OXM\Configuration;
use Doctrine\Common\Util\Debug;
use Doctrine\OXM\Events;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory as BaseClassMetadataFactory;
use Doctrine\OXM\Types\Type;

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
class ClassMetadataFactory implements BaseClassMetadataFactory
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
     * @var \Doctrine\OXM\Mapping\ClassMetadata[]
     */
    private $loadedMetadata = array();

    /**
     * Keys are mapped xml node names
     *
     * @var array
     */
    private $xmlToClassMap = array();

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @param Configuration $configuration
     * @param EventManager|null $evm
     * @return null
     */
    public function __construct(Configuration $configuration, EventManager $evm = null)
    {
        $this->configuration = $configuration;
        $this->evm = $evm;
    }


    /**
     * Sets the cache driver used by the factory to cache Mapping instances.
     *
     * @param \Doctrine\Common\Cache\Cache $cacheDriver
     */
    public function setCacheDriver(Cache $cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Gets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @return Doctrine\Common\Cache\Cache
     */
    public function getCacheDriver()
    {
        return $this->cacheDriver;
    }

    /**
     * @return array
     */
    public function getLoadedMetadata()
    {
        return $this->loadedMetadata;
    }
    
    /**
     * Forces the factory to load the metadata of all classes known to the underlying
     * mapping driver.
     * 
     * @return array The ClassMetadata instances of all mapped classes.
     */
    public function getAllMetadata()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $mappings = array();
        foreach ($this->driver->getAllClassNames() as $className) {
            $mappings[] = $this->getMetadataFor($className);
        }

        return $mappings;
    }

    /**
     * Preloads all metadata and returns an array of all known mapped node types
     *
     * @return array
     */
    public function getAllXmlNodes()
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        
        // Load all metadata
        if (empty($this->xmlToClassMap)) {
            // todo:  there should be a better way to access the metadata about a mapped xml node than instantiating all of them
            $this->getAllMetadata();
        }

        return $this->xmlToClassMap;
    }

    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     */
    private function initialize()
    {
        $this->cacheDriver = $this->configuration->getMetadataCacheImpl();
        $this->driver = $this->configuration->getMetadataDriverImpl();

        if (null === $this->evm) {
            $this->evm = new EventManager();
        }
        
        $this->initialized = true;
    }

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param string $className The name of the class.
     * @return \Doctrine\OXM\Mapping\ClassMetadata
     */
    public function getMetadataFor($className)
    {
        if ( ! isset($this->loadedMetadata[$className])) {
//            print_r('loading class ' . $className . "\n");
            $realClassName = $className;

            // Check for namespace alias
            if (strpos($className, ':') !== false) {
                list($namespaceAlias, $simpleClassName) = explode(':', $className);
                $realClassName = $this->configuration->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;

                if (isset($this->loadedMetadata[$realClassName])) {
                    // We do not have the alias name in the map, include it
                    $this->loadedMetadata[$className] = $this->loadedMetadata[$realClassName];

                    return $this->loadedMetadata[$realClassName];
                }
            }

            if ($this->cacheDriver) {
                if (($cached = $this->cacheDriver->fetch("$realClassName\$XMLCLASSMETADATA")) !== false) {
                    $this->loadedMetadata[$realClassName] = $cached;
                    if (!$cached->isMappedSuperclass) {
                        $this->xmlToClassMap[$cached->getXmlName()] = $realClassName;
                    }
                } else {
                    foreach ($this->loadMetadata($realClassName) as $loadedClassName) {
                        $this->cacheDriver->save(
                            "$loadedClassName\$XMLCLASSMETADATA", $this->loadedMetadata[$loadedClassName], null
                        );
                    }
                }
            } else {
                $this->loadMetadata($realClassName);
            }

            if ($className != $realClassName) {
                // We do not have the alias name in the map, include it
                $this->loadedMetadata[$className] = $this->loadedMetadata[$realClassName];
            }
        }

        return $this->loadedMetadata[$className];
    }

    /**
     * Checks whether the factory has the metadata for a class loaded already.
     * 
     * @param string $className
     * @return boolean TRUE if the metadata of the class in question is already loaded, FALSE otherwise.
     */
    public function hasMetadataFor($className)
    {
        return isset($this->loadedMetadata[$className]);
    }

    /**
     * Sets the metadata descriptor for a specific class.
     * 
     * NOTE: This is only useful in very special cases, like when generating proxy classes.
     *
     * @param string $className
     * @param ClassMapping $class
     */
    public function setMetadataFor($className, $class)
    {
        $this->loadedMetadata[$className] = $class;
    }

    /**
     * Get array of parent classes for the given entity class
     *
     * @param string $name
     * @return array $parentClasses
     */
    protected function getParentClasses($name)
    {
        // Collect parent classes, ignoring transient (not-mapped) classes.
        $parentClasses = array();
        foreach (array_reverse(class_parents($name)) as $parentClass) {
            if (!$this->driver->isTransient($parentClass)) {
                $parentClasses[] = $parentClass;
            }
        }
        return $parentClasses;
    }

    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * @param string $name The name of the class for which the metadata should get loaded.
     * @param array  $tables The metadata collection to which the loaded metadata is added.
     */
    protected function loadMetadata($name)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $loaded = array();

        $parentClasses = $this->getParentClasses($name);
        $parentClasses[] = $name;

        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent = null;
        $visited = array();
        foreach ($parentClasses as $className) {
            if (isset($this->loadedMetadata[$className])) {
                $parent = $this->loadedMetadata[$className];
                if ( $parent->isMappedSuperclass) {
                    array_unshift($visited, $className);
                }
                continue;
            }

            $class = $this->newClassMetadataInstance($className);

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
                $this->driver->loadMetadataForClass($className, $class);
            } catch (ReflectionException $e) {
                throw MappingException::reflectionFailure($className, $e);
            }

            if ( ! $class->isMappedSuperclass && in_array($class->getXmlName(), array_keys($this->xmlToClassMap))) {
                throw MappingException::duplicateXmlNameBinding($className, $class->getXmlName());
            }
            
            $this->completeMappingTypeValidation($className, $class);

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

            $class->setParentClasses($visited);

            // Todo - ensure that root elements have an ID mapped

            if ($this->evm->hasListeners(Events::loadClassMetadata)) {
                $eventArgs = new \Doctrine\OXM\Event\LoadClassMetadataEventArgs($class, $this);
                $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
            }

            $this->loadedMetadata[$className] = $class;

            if ( ! $class->isMappedSuperclass) {
                $this->xmlToClassMap[$class->getXmlName()] = $className;
            }

            $parent = $class;

            if ( $class->isMappedSuperclass) {
                array_unshift($visited, $className);
            }

            $loaded[] = $className;
        }

        return $loaded;
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
     * @param Doctrine\OXM\Mapping\ClassMetadataInfo $class
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
     * Creates a new Mapping instance for the given class name.
     *
     * @param string $className
     * @return ClassMetadata
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }
}
