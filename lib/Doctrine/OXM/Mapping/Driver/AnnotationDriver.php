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

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\OXM\Util\Inflector;
use Doctrine\OXM\Mapping\ClassMetadataInfo;
use Doctrine\OXM\Mapping\MappingException;
use Doctrine\OXM\Mapping\Driver\Driver as DriverInterface;

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
    private $reader;

    /**
     * The paths where to look for mapping files.
     *
     * @var array
     */
    protected $paths = array();

    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $fileExtension = '.php';

    /**
     * @param array
     */
    protected $classNames;
    
    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     * 
     * @param AnnotationReader $reader The AnnotationReader to use.
     * @param string|array $paths One or multiple paths where mapping classes can be found. 
     */
    public function __construct($reader, $paths = null)
    {
        $this->reader = $reader;
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
        $this->paths = array_unique(array_merge($this->paths, $paths));
    }

    /**
     * Retrieve the defined metadata lookup paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Get the file extension used to look for mapping files under
     *
     * @return void
     */
    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    /**
     * Set the file extension used to look for mapping files under
     *
     * @param string $fileExtension The file extension to set
     * @return void
     */
    public function setFileExtension($fileExtension)
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadataInfo $metadata)
    {
        $reflClass = $metadata->getReflectionClass();

        $classAnnotations = $this->reader->getClassAnnotations($reflClass);

        // Compatibility with Doctrine Common 3.x
        if ($classAnnotations && is_int(key($classAnnotations))) {
            foreach ($classAnnotations as $annot) {
                $classAnnotations[get_class($annot)] = $annot;
            }
        }

        // Evaluate XmlEntity Annotations
        if (isset($classAnnotations['Doctrine\OXM\Mapping\XmlEntity'])) {
            $entityAnnot = $classAnnotations['Doctrine\OXM\Mapping\XmlEntity'];
        } elseif (isset($classAnnotations['Doctrine\OXM\Mapping\XmlRootEntity'])) {
            $entityAnnot = $classAnnotations['Doctrine\OXM\Mapping\XmlRootEntity'];
            $metadata->isRoot = true;
        } elseif (isset($classAnnotations['Doctrine\OXM\Mapping\XmlMappedSuperclass'])) {
            $entityAnnot = $classAnnotations['Doctrine\OXM\Mapping\XmlMappedSuperclass'];
            $metadata->isMappedSuperclass = true;
        } else {
            throw MappingException::classIsNotAValidXmlEntity($className);
        }

        $metadata->setName($reflClass->getName());
        
        if (isset($entityAnnot->xml)) {
            $metadata->setXmlName($entityAnnot->xml);
        } else {
            $metadata->setXmlName(Inflector::xmlize($reflClass->getShortName()));
        }

        if (isset($entityAnnot->repositoryClass)) {
            $metadata->setCustomRepositoryClass($entityAnnot->repositoryClass);
        }

        // Evaluate XmlChangeTrackingPolicy annotation
        if (isset($classAnnotations['Doctrine\OXM\Mapping\XmlChangeTrackingPolicy'])) {
            $changeTrackingAnnot = $classAnnotations['Doctrine\OXM\Mapping\XmlChangeTrackingPolicy'];
            $metadata->setChangeTrackingPolicy(constant('Doctrine\OXM\Mapping\ClassMetadata::CHANGETRACKING_' . $changeTrackingAnnot->value));
        }

        // Check for XmlNamespace/XmlNamespaces annotations
        $xmlNamespaces = array();

        if (isset($classAnnotations['Doctrine\OXM\Mapping\XmlNamespace'])) {
            $xmlNamespaceAnnot = $classAnnotations['Doctrine\OXM\Mapping\XmlNamespace'];
            $xmlNamespaces[] = array(
                'url' => $xmlNamespaceAnnot->url,
                'prefix' => $xmlNamespaceAnnot->prefix
            );
        } else if (isset($classAnnotations['Doctrine\OXM\Mapping\XmlNamespaces'])) {
            $xmlNamespaceAnnot = $classAnnotations['Doctrine\OXM\Mapping\XmlNamespaces'];
            foreach ($xmlNamespaceAnnot->value as $xmlNamespace) {
                $xmlNamespaces[] = array(
                    'url' => $xmlNamespace->url,
                    'prefix' => $xmlNamespace->prefix
                );
            }
        }
        $metadata->setXmlNamespaces($xmlNamespaces);

        foreach ($reflClass->getProperties() as $property) {
            if ($metadata->isMappedSuperclass && ! $property->isPrivate()
                || $metadata->isInheritedField($property->name)) {
                continue;
            }

            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            if ($idAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\OXM\Mapping\XmlId')) {
                $mapping['id']  = true;
            }
            
            if ($generatedValueAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\OXM\Mapping\XmlGeneratedValue')) {
                $metadata->setIdGeneratorType(constant('Doctrine\OXM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAnnot->strategy));
            }
            
            
            $referenceAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\OXM\Mapping\XmlReferences');
            if (isset($referenceAnnot->entityName)) {
                $mapping['references']  = $referenceAnnot->entityName;
            }

            // todo add Id Generator strategy support

            foreach ($this->reader->getPropertyAnnotations($property) as $fieldAnnot) {
                if ($fieldAnnot instanceof \Doctrine\OXM\Mapping\XmlField) {
                    if ($fieldAnnot->type == null) {
                        throw MappingException::propertyTypeIsRequired($className, $property->getName());
                    }                    

                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $metadata->mapField($mapping);
                }
            }
        }

        // Evaluate @HasLifecycleCallbacks annotations
        if (isset($classAnnotations['Doctrine\OXM\Mapping\HasLifecycleCallbacks'])) {
            foreach ($reflClass->getMethods() as $method) {
                // filter for the declaring class only, callbacks from parents will already be registered.
                if ($method->isPublic() && $method->getDeclaringClass()->getName() == $reflClass->name) {
                    $annotations = $this->reader->getMethodAnnotations($method);
                    
                    // Compatibility with Doctrine Common 3.x
                    if ($annotations && is_int(key($annotations))) {
                        foreach ($annotations as $annot) {
                            $annotations[get_class($annot)] = $annot;
                        }
                    }
                    
                    if (isset($annotations['Doctrine\OXM\Mapping\PreMarshal'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::preMarshal);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PostMarshal'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::postMarshal);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PreUnmarshal'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::preUnmarshal);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PostUnmarshal'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::postUnmarshal);
                    }
                    
                    if (isset($annotations['Doctrine\OXM\Mapping\PrePersist'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::prePersist);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PostPersist'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::postPersist);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PreUpdate'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::preUpdate);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PostUpdate'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::postUpdate);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PreRemove'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::preRemove);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PostRemove'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::postRemove);
                    }

                    if (isset($annotations['Doctrine\OXM\Mapping\PreLoad'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::preLoad);
                    }
                    
                    if (isset($annotations['Doctrine\OXM\Mapping\PostLoad'])) {
                        $metadata->addLifecycleCallback($method->getName(), \Doctrine\OXM\Events::postLoad);
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
        if ($this->classNames !== null) {
            return $this->classNames;
        }

        if (!$this->paths) {
            throw MappingException::pathRequired();
        }

        $classes = array();
        $includedFiles = array();

        foreach ($this->paths as $path) {
            if ( ! is_dir($path)) {
                throw MappingException::fileMappingDriversRequiresConfiguredDirectoryPath($path);
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (($fileName = $file->getBasename($this->fileExtension)) == $file->getBasename()) {
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
            if (in_array($sourceFile, $includedFiles) && !$this->isTransient($className)) {
                $classes[] = $className;
            }
        }
        $this->classNames = $classes;
        
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
        $classAnnotations = $this->reader->getClassAnnotations(new \ReflectionClass($className));
        
        // Compatibility with Doctrine Common 3.x
        if ($classAnnotations && is_int(key($classAnnotations))) {
            foreach ($classAnnotations as $annot) {
                $classAnnotations[get_class($annot)] = $annot;
            }
        }
        
        return ! isset($classAnnotations['Doctrine\OXM\Mapping\XmlEntity']) &&
               ! isset($classAnnotations['Doctrine\OXM\Mapping\XmlRootEntity']) &&
               ! isset($classAnnotations['Doctrine\OXM\Mapping\XmlMappedSuperclass']);
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
