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

use \ReflectionException,
    \Doctrine\OXM\OXMException,
    \Doctrine\OXM\XmlEntityManager,
    \Doctrine\Common\Util\Debug,
    \Doctrine\OXM\Events,
    \Doctrine\Common\Cache\Cache,
    \Doctrine\OXM\Types\Type;

/**
 * The MappingFactory is used to create Mapping objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a xml node.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class MappingFactory
{
    /**
     * @var \Doctrine\OXM\XmlEntityManager
     */
    private $xem;
    
    /**
     * @var Driver\Driver
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
     * @var Mapping[]
     */
    private $loadedMappings = array();

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
     * @param \Doctrine\OXM\XmlEntityManager $$em
     */
    public function setXmlEntityManager(XmlEntityManager $xem)
    {
        $this->xem = $xem;
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
     * @return \Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->xem->getEventManager();
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
    
    public function getLoadedMappings()
    {
        return $this->loadedMappings;
    }
    
    /**
     * Forces the factory to load the metadata of all classes known to the underlying
     * mapping driver.
     * 
     * @return array The ClassMetadata instances of all mapped classes.
     */
    public function getAllMappings()
    {
        if ( ! $this->initialized) {
            $this->initialize();
        }

        $mappings = array();
        foreach ($this->driver->getAllClassNames() as $className) {
            $mappings[] = $this->getMappingForClass($className);
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
        // todo:  there should be a better way to access the metadata about a mapped xml node than instantiating all of them
        $this->getAllMappings();

        return $this->xmlToClassMap;
    }

    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     */
    private function initialize()
    {
        $this->driver = $this->xem->getMappingDriverImpl();
        $this->evm = $this->xem->getEventManager();
        $this->initialized = true;
    }

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param string $className The name of the class.
     * @return Mapping
     */
    public function getMappingForClass($className)
    {
        if ( ! isset($this->loadedMappings[$className])) {
            $realClassName = $className;

            // Check for namespace alias
            if (strpos($className, ':') !== false) {
                list($namespaceAlias, $simpleClassName) = explode(':', $className);
                $realClassName = $this->xem->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;

                if (isset($this->loadedMappings[$realClassName])) {
                    // We do not have the alias name in the map, include it
                    $this->loadedMappings[$className] = $this->loadedMappings[$realClassName];

                    return $this->loadedMappings[$realClassName];
                }
            }

            if ($this->cacheDriver) {
                if (($cached = $this->cacheDriver->fetch("$realClassName\$CLASSMAPPING")) !== false) {
                    $this->loadedMappings[$realClassName] = $cached;
                } else {
                    foreach ($this->loadMapping($realClassName) as $loadedClassName) {
                        $this->cacheDriver->save(
                            "$loadedClassName\$CLASSMAPPING", $this->loadedMappings[$loadedClassName], null
                        );
                    }
                }
            } else {
                $this->loadMapping($realClassName);
            }

            if ($className != $realClassName) {
                // We do not have the alias name in the map, include it
                $this->loadedMappings[$className] = $this->loadedMappings[$realClassName];
            }
        }

        return $this->loadedMappings[$className];
    }

    /**
     * Checks whether the factory has the metadata for a class loaded already.
     * 
     * @param string $className
     * @return boolean TRUE if the metadata of the class in question is already loaded, FALSE otherwise.
     */
    public function hasMappingForClass($className)
    {
        return isset($this->loadedMappings[$className]);
    }

    /**
     * Sets the metadata descriptor for a specific class.
     * 
     * NOTE: This is only useful in very special cases, like when generating proxy classes.
     *
     * @param string $className
     * @param ClassMapping $class
     */
    public function setMappingForClass($className, $class)
    {
        $this->loadedMappings[$className] = $class;
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
            if ( ! $this->driver->isTransient($parentClass)) {
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
    protected function loadMapping($name)
    {
        if ( ! $this->initialized) {
            $this->initialize();
        }

        $loaded = array();

//        $parentClasses = $this->getParentClasses($name);
//        $parentClasses[] = $name;

        // Move down the hierarchy of parent classes, starting from the topmost class
        // TODO support parent classes and subclasses
        $parent = null;
        $visited = array();

        $className = $name;

        $class = $this->newMappingInstance($className);
        
        // Invoke driver
        try {
            $this->driver->loadMappingForClass($className, $class);

            // Post loading validation
            if (in_array($class->getXmlName(), array_keys($this->xmlToClassMap))) {
                throw MappingException::duplicateXmlNameBinding($className, $class->getXmlName());
            }
            // (somewhat expensive, does some duplicate work)
            $fieldMappings = $class->getFieldMappings();
            if (!empty($fieldMappings)) {

                foreach ($fieldMappings as $fieldName => $mapping) {
                    if (Type::hasType($mapping['type'])) {
                        continue;
                    }

                    // Support type as a mapped class?
                    if (!$this->hasMappingForClass($mapping['type']) && !$this->getMappingForClass($mapping['type'])) {
                        throw MappingException::fieldTypeNotFound($className, $fieldName, $mapping['type']);
                    }

                    // Mapped classes must have binding node type XML_ELEMENT
                    $fieldBinding = $class->getFieldBinding($fieldName);
                    if ($fieldBinding['node'] !== Mapping::XML_ELEMENT) {
                        throw MappingException::customTypeWithoutNodeElement($className, $fieldName);
                    }
                }
            }

        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($className, $e);
        }

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new \Doctrine\OXM\Event\LoadMappingEventArgs($class, $this->xem);
            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }

        $this->loadedMappings[$className] = $class;
        $this->xmlToClassMap[$class->getXmlName()] = $className;

        $loaded[] = $className;

        return $loaded;
    }

    /**
     * Creates a new Mapping instance for the given class name.
     *
     * @param string $className
     * @return Mapping
     */
    protected function newMappingInstance($className)
    {
        return new Mapping($className);
    }
}
