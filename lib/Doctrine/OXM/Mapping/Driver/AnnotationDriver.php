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

namespace Doctrine\OXM\Mapping\Driver;

use \Doctrine\Common\Cache\ArrayCache,
    \Doctrine\Common\Annotations\AnnotationReader,
    \Doctrine\OXM\Mapping\Mapping,
    \Doctrine\OXM\Mapping\MappingException,
    \Doctrine\OXM\Mapping\Driver\Driver as DriverInterface;

require __DIR__ . '/DoctrineAnnotations.php';

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class AnnotationDriver implements DriverInterface
{
    /**
     * The AnnotationReader.
     *
     * @var AnnotationReader
     */
    private $_reader;

    /**
     * The paths where to look for mapping files.
     *
     * @var array
     */
    protected $_paths = array();

    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $_fileExtension = '.php';

    /**
     * @param array
     */
    protected $_classNames;
    
    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     * 
     * @param $reader The AnnotationReader to use.
     * @param string|array $paths One or multiple paths where mapping classes can be found. 
     */
    public function __construct(AnnotationReader $reader, $paths = null)
    {
        $this->_reader = $reader;
        if ($paths) {
            $this->addPaths((array) $paths);
        }
    }
    
    /**
     * Append lookup paths to metadata driver.
     *
     * @param array $paths
     */
    public function addPaths(array $paths)
    {
        $this->_paths = array_unique(array_merge($this->_paths, $paths));
    }

    /**
     * Retrieve the defined metadata lookup paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * Get the file extension used to look for mapping files under
     *
     * @return void
     */
    public function getFileExtension()
    {
        return $this->_fileExtension;
    }

    /**
     * Set the file extension used to look for mapping files under
     *
     * @param string $fileExtension The file extension to set
     * @return void
     */
    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMappingForClass($className, Mapping $classMapping)
    {
        $class = $classMapping->getReflectionClass();

        $classAnnotations = $this->_reader->getClassAnnotations($class);

        // Evaluate XmlEntity annotation
        if (isset($classAnnotations['Doctrine\OXM\Mapping\XmlEntity'])) {
            $entityAnnot = $classAnnotations['Doctrine\OXM\Mapping\XmlEntity'];

            $classMapping->setClassName($class->getName());
            

//            if ($entityAnnot->autoComplete) {
//                $classMapping->setAutoComplete($entityAnnot->autoComplete);
//            }

//            // todo support extends field
//            if ($entityAnnot->extends) {
//                $classMapping->setExtends($entityAnnot->extends);
//            }
            
        } else {
            throw MappingException::classIsNotAValidXmlEntity($className);
        }


        $xmlMapTo = array();

        if (isset($classAnnotations['Doctrine\OXM\Mapping\XmlMapTo'])) {
            $mapToAnnot = $classAnnotations['Doctrine\OXM\Mapping\XmlMapTo'];

            if ($mapToAnnot->xml) {
                $xmlMapTo['xml'] = $mapToAnnot->xml;
            }

            if ($mapToAnnot->nsUrl) {
                $xmlMapTo['nsUrl'] = $mapToAnnot->nsUrl;
            }

            if ($mapToAnnot->nsPrefix) {
                $xmlMapTo['nsPrefix'] = $mapToAnnot->nsPrefix;
            }
        }

        $classMapping->mapTo($xmlMapTo);

        // Evaluate annotations on properties/fields
        foreach ($class->getProperties() as $property) {

            $fieldBinding = array();
            $mapping = array();
            $mapping['name'] = $property->getName();

            // Field can only be annotated with one of:
            // @XmlField
            if ($fieldAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\OXM\Mapping\XmlField')) {
                

                $mapping['type'] = $fieldAnnot->type;
                $mapping['required'] = $fieldAnnot->required;
                $mapping['direct'] = $fieldAnnot->direct;
//                $mapping['lazy'] = $fieldAnnot->lazy;
                $mapping['transient'] = $fieldAnnot->transient;
                $mapping['nillable'] = $fieldAnnot->nillable;
                $mapping['container'] = $fieldAnnot->container;
                $mapping['collection'] = $fieldAnnot->collection;  // todo support Doctrine ArrayCollection?

                if ($fieldAnnot->handler) {
                    $mapping['handler'] = $fieldAnnot->handler;
                }

                if ($fieldAnnot->getMethod) {
                    $mapping['getMethod'] = $fieldAnnot->getMethod;
                }

                if ($fieldAnnot->setMethod) {
                    $mapping['setMethod'] = $fieldAnnot->setMethod;
                }
//                if ($fieldAnnot->createMethod) {
//                    $mapping['createMethod'] = $fieldAnnot->creatMethod;
//                }

                $classMapping->mapField($mapping);

                // Fields with @OxmField also can have a @XmlBinding definition
                if ($bindXmlAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\OXM\Mapping\XmlBinding')) {

                    if ($bindXmlAnnot->name) {
                        $fieldBinding['name'] = $bindXmlAnnot->name;
                    }

                    if ($bindXmlAnnot->node) {
                        $fieldBinding['node'] = $bindXmlAnnot->node;
                    }

                    // todo support reference and referenceable
                    if ($bindXmlAnnot->reference) {
                        $fieldBinding['reference'] = $bindXmlAnnot->reference;
                    }
                }

                $classMapping->mapBindingToField($property->getName(), $fieldBinding);

            }
        }

        // Evaluate @HasLifecycleCallbacks annotation
        if (isset($classAnnotations['Doctrine\OXM\Mapping\HasLifecycleCallbacks'])) {
            foreach ($class->getMethods() as $method) {
                if ($method->isPublic()) {
                    $annotations = $this->_reader->getMethodAnnotations($method);

                    if (isset($annotations['Doctrine\OXM\Mapping\PreMarshal'])) {
                        $classMapping->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::preMarshal);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PostMarshal'])) {
                        $classMapping->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::postMarshal);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PreUnmarshal'])) {
                        $classMapping->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::preUnmarshal);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PostUnmarshal'])) {
                        $classMapping->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::postUnmarshal);
                    }
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        if ($this->_classNames !== null) {
            return $this->_classNames;
        }

        if (!$this->_paths) {
            throw MappingException::pathRequired();
        }

        $classes = array();
        $includedFiles = array();

        foreach ($this->_paths as $path) {
            if ( ! is_dir($path)) {
                throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (($fileName = $file->getBasename($this->_fileExtension)) == $file->getBasename()) {
                    continue;
                }

                $sourceFile = realpath($file->getPathName());
                require_once $sourceFile;
                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles) && ! $this->isTransient($className)) {
                $classes[] = $className;
            }
        }

        $this->_classNames = $classes;

        return $classes;
    }

    /**
     * Whether the class with the specified name is transient. Only non-transient
     * classes, that is entities and mapped superclasses, should have their metadata loaded.
     * A class is non-transient if it is annotated with either @XmlEntity or
     * @MappedSuperclass in the class doc block.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        $classAnnotations = $this->_reader->getClassAnnotations(new \ReflectionClass($className));

        return !isset($classAnnotations['Doctrine\OXM\Mapping\OxmEntity']);
    }

    /**
     * Factory method for the Annotation Driver
     * 
     * @param array|string $paths
     * @param AnnotationReader $reader
     * @return AnnotationDriver
     */
    static public function create($paths = array(), AnnotationReader $reader = null)
    {
        if ($reader == null) {
            $reader = new AnnotationReader();
            $reader->setDefaultAnnotationNamespace('Doctrine\OXM\Mapping\\');
        }
        return new self($reader, $paths);
    }
}
