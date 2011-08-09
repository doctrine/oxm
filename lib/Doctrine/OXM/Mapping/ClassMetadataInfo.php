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

use ReflectionClass;
use ReflectionProperty;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\OXM\Util\Inflector;
use Doctrine\OXM\Types\Type;

/**
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class ClassMetadataInfo implements BaseClassMetadata
{
    /* XML Type Constants */
    const XML_ELEMENT   = 'element';
    const XML_ATTRIBUTE = 'attribute';
    const XML_TEXT      = 'text';

    /** The map of supported xml node types. */
    private static $nodeTypes = array(
        self::XML_TEXT,
        self::XML_ATTRIBUTE,
        self::XML_ELEMENT,
    );

    /* The Id generator types. */
    /**
     * AUTO means the generator type will depend on what the used platform prefers.
     * Offers full portability.
     */
    const GENERATOR_TYPE_AUTO = 1;
    /**
     * INCREMENT means a separate collection is used for maintaining and incrementing id generation.
     * Offers full portability.
     */
    const GENERATOR_TYPE_INCREMENT = 2;
    /**
     * UUID means Doctrine will generate a uuid for us.
     */
    const GENERATOR_TYPE_UUID = 3;
    /**
     * NONE means the class does not have a generated id. That means the class
     * must have a natural, manually assigned id.
     */
    const GENERATOR_TYPE_NONE = 4;

    /**
     * DEFERRED_IMPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done for all entities that are in MANAGED state at commit-time.
     *
     * This is the default change tracking policy.
     */
    const CHANGETRACKING_DEFERRED_IMPLICIT = 1;
    /**
     * DEFERRED_EXPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done only for entities that were explicitly saved (through persist() or a cascade).
     */
    const CHANGETRACKING_DEFERRED_EXPLICIT = 2;
    /**
     * NOTIFY means that Doctrine relies on the entities sending out notifications
     * when their properties change. Such entity classes must implement
     * the <tt>NotifyPropertyChanged</tt> interface.
     */
    const CHANGETRACKING_NOTIFY = 3;

    /**
     * The name of the class
     *
     * @var string
     */
    public $name;

    /**
     * The xml node name to map this class to
     *
     * @var string
     */
    public $xmlName;

    /**
     * The xml namespaces defined by this class mapping
     *
     * The mapping definition array supports the following keys
     *
     * - <b>url</b> (required)
     * The url containing this namespace definition.  Only one URL can be present
     * within the class which does not have a prefix.  One can have any
     * number of prefixed URL's.
     *
     * - <b>prefix</b> (optional)
     * The prefix used by this namespace.  Prefixes must be unique.
     *
     * @var array
     */
    public $xmlNamespaces = array();

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var \ReflectionClass
     */
    public $reflClass;

    /**
     * READ-ONLY: The Id generator type used by the class.
     *
     * @var int
     */
    public $generatorType = self::GENERATOR_TYPE_NONE;
    
    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var \Doctrine\OXM\Id\AbstractIdGenerator
     */
    public $idGenerator;

    /**
     * READ-ONLY: The association mappings of this class.
     *
     * The mapping definition array supports the following keys:
     *
     * - <b>fieldName</b> (string)
     * The name of the field in the entity this mapping is associated with
     *
     * - <b>id</b> (boolean, optional)
     * Marks this field as the identifier for this class.  Used for references
     *
     * - <b>type</b> (string)
     * The type of the field being mapped by this field mapping.  Can by any of the allowed
     * /Doctrine/OXM/Types or a specific Class name.  If used with collection=true, this will
     * assume that each collection object is of the specified type.
     *
     * - <b>xmlName</b> (string, optional)
     * The name of the xml node this class definition will be mapped to.
     *
     * - <b>xmlNode</b> (string)
     * The type of xml object to map this field.  Can be one of ('element', 'attribute', or 'text')
     *
     * - <b>required</b> (boolean, optional)
     * Defines if this field is required or not.  Checked during marshalling and unmarshalling.
     *
     * - <b>nullable</b> (boolean, optional)
     * Defines if this field is required to be marshalled/unmarshalled if null.
     *
     * - <b>getMethod</b> (string, optional)
     * Defines an optional get method name to use while getting and setting this field on the
     * owning object.
     *
     * - <b>setMethod</b> (string, optional)
     * Defines an optional set method name to use while getting and setting this field on the
     * owning object.
     *
     * - <b>collection</b> (boolean, optional)
     * Define the field as a "collection".  This allows for the field to be an array of the above
     * specified type.
     *
     * - <b>direct</b> (boolean, optional, defaults to true)
     * Allow Doctrine OXM to access this field on the class with direct access.
     *
     * @var array
     */
    public $fieldMappings = array();

    /**
     * READ-ONLY: The registered lifecycle callbacks for entities of this class.
     *
     * @var array
     */
    public $lifecycleCallbacks = array();

    /**
     * Mapping xml node names back to class fields
     * keys are xml names
     *
     * @var array
     */
    public $xmlFieldMap = array();

    /**
     * READ-ONLY: The policy used for change-tracking on entities of this class.
     *
     * @var integer
     */
    public $changeTrackingPolicy = self::CHANGETRACKING_DEFERRED_IMPLICIT;

    /**
     * READ-ONLY: The field name of the class identifier.
     */
    public $identifier;

    /**
     * The name of the custom repository class used for the document class.
     * (Optional).
     *
     * @var string
     */
    public $customRepositoryClassName;


    /**
     * READ-ONLY: Whether this class describes the mapping xml root element.
     *
     * @var boolean
     */
    public $isRoot = false;

    /**
     * READ-ONLY: The name of the entity class that is at the root of the mapped entity inheritance
     * hierarchy. If the entity is not part of a mapped inheritance hierarchy this is the same
     * as {@link $entityName}.
     *
     * @var string
     */
    public $rootXmlEntityName;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var boolean
     */
    public $isMappedSuperclass = false;
    
    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @var array
     */
    public $parentClasses = array();

    /**
     * Initializes a new ClassMetadata instance that will hold the object-xml mapping
     * metadata of the class with the given name.
     *
     * @param string $entityName The name of the entity class the new instance is used for.
     */
    public function __construct($entityName)
    {
        $this->name = $entityName;
        $this->rootXmlEntityName = $entityName;
    }

    /**
     * @param string $entityName
     */
    public function setName($entityName)
    {
        $this->name = $entityName;
    }

    /**
     * A numerically indexed list of field names of this persistent class.
     *
     * This array includes identifier fields if present on this class.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * A numerically indexed list of association names of this persistent class.
     *
     * This array includes identifier associations if present on this class.
     *
     * @return array
     */
    public function getAssociationNames()
    {
        // not implemented
        return array();
    }

    /**
     * Returns the target class name of the given association.
     *
     * @param string $assocName
     * @return string
     */
    public function getAssociationTargetClass($assocName)
    {
        // Not implemented
        return '';
    }


    /**
     * Gets the xml node name used to map this class to an xml node
     *
     * @return string
     */
    public function getXmlName()
    {
        return $this->xmlName;
    }

    /**
     * Set the xml node name to be used by this class mapping
     *
     * @return string
     */
    public function setXmlName($xmlName)
    {
        $this->xmlName = $xmlName;
    }

    /**
     * Gets the ReflectionClass instance of the mapped class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass()
    {
        if ( ! $this->reflClass) {
            $this->reflClass = new \ReflectionClass($this->name);
        }
        return $this->reflClass;
    }

        /**
     * Sets the change tracking policy used by this class.
     *
     * @param integer $policy
     */
    public function setChangeTrackingPolicy($policy)
    {
        $this->changeTrackingPolicy = $policy;
    }

    /**
     * Whether the change tracking policy of this class is "deferred explicit".
     *
     * @return boolean
     */
    public function isChangeTrackingDeferredExplicit()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_DEFERRED_EXPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "deferred implicit".
     *
     * @return boolean
     */
    public function isChangeTrackingDeferredImplicit()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_DEFERRED_IMPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "notify".
     *
     * @return boolean
     */
    public function isChangeTrackingNotify()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_NOTIFY;
    }

    /**
     * Adds a lifecycle callback for entities of this class.
     *
     * Note: If the same callback is registered more than once, the old one
     * will be overridden.
     *
     * @param string $callback
     * @param string $event
     */
    public function addLifecycleCallback($callback, $event)
    {
        $this->lifecycleCallbacks[$event][] = $callback;
    }


    /**
     * Whether the class has any attached lifecycle listeners or callbacks for a lifecycle event.
     *
     * @param string $lifecycleEvent
     * @return boolean
     */
    public function hasLifecycleCallbacks($lifecycleEvent)
    {
        return isset($this->lifecycleCallbacks[$lifecycleEvent]);
    }

    /**
     * Sets the lifecycle callbacks for entities of this class.
     * Any previously registered callbacks are overwritten.
     *
     * @param array $callbacks
     */
    public function setLifecycleCallbacks(array $callbacks)
    {
        $this->lifecycleCallbacks = $callbacks;
    }


    /**
     * Dispatches the lifecycle event of the given entity to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @param string $event The lifecycle event.
     * @param XmlEntity $entity The XmlEntity on which the event occured.
     */
    public function invokeLifecycleCallbacks($lifecycleEvent, $entity, array $arguments = null)
    {
        foreach ($this->lifecycleCallbacks[$lifecycleEvent] as $callback) {
            if ($arguments !== null) {
                call_user_func_array(array($entity, $callback), $arguments);
            } else {
                $entity->$callback();
            }
        }
    }

    /**
     * Checks whether a field is part of the identifier/primary key field(s).
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is part of the table identifier/primary key field(s),
     *                  FALSE otherwise.
     */
    public function isIdentifier($fieldName)
    {
        return $this->identifier === $fieldName ? true : false;
    }

    /**
     * INTERNAL:
     * Sets the mapped identifier field of this class.
     *
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Gets the mapped identifier field of this class.
     *
     * @return string $identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the type of Id generator to use for the mapped class.
     */
    public function setIdGeneratorType($generatorType)
    {
        $this->generatorType = $generatorType;
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     *
     * @param AbstractIdGenerator $generator
     */
    public function setIdGenerator($generator)
    {
        $this->idGenerator = $generator;
    }

    /**
     * Registers a custom repository class for the document class.
     *
     * @param string $mapperClassName  The class name of the custom mapper.
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Sets the parent class names.
     * Assumes that the class names in the passed array are in the order:
     * directParent -> directParentParent -> directParentParentParent ... -> root.
     */
    public function setParentClasses(array $classNames)
    {
        $this->parentClasses = $classNames;
        if (count($classNames) > 0) {
            $this->rootXmlEntityName = array_pop($classNames);
        }
    }

    /**
     * @return array
     */
    public function getParentClasses()
    {
        return $this->parentClasses;
    }

    /**
     * @param array
     * @return void
     */
    public function mapField(array $mapping)
    {
        // Check mandatory fields
        if (!isset($mapping['fieldName']) || strlen($mapping['fieldName']) == 0) {
            throw MappingException::missingFieldName($this->name);
        }
        
        if (isset($this->fieldMappings[$mapping['fieldName']])) {
            $existingMapping = $this->fieldMappings[$mapping['fieldName']];

            // only complain if one exists for this class, and not any parents
            if ( ! isset($existingMapping['declared'])) {
                throw MappingException::duplicateFieldMapping($this->name, $mapping['fieldName']);
            } elseif ($existingMapping['declared'] == $this->rootXmlEntityName) {
                throw MappingException::duplicateFieldMapping($this->name, $mapping['fieldName']);
            }
        }
        if (!isset($mapping['type']) || strlen($mapping['type']) == 0) {
            throw MappingException::missingFieldType($this->name, $mapping['fieldName']);
        }

        if (!isset($mapping['name'])) {
            $mapping['name'] = Inflector::xmlize($mapping['fieldName']);
        } else {
            if ($mapping['name'][0] == '`') {
                $mapping['name'] = trim($mapping['name'], '`');
                $mapping['quoted'] = true;
            }
        }

        if (isset($this->xmlFieldMap[$mapping['name']])) {
            $existingMapping = $this->fieldMappings[$this->xmlFieldMap[$mapping['name']]];

            if ( ! isset($existingMapping['declared'])) {
                throw MappingException::duplicateXmlFieldName($this->name, $mapping['name']);
            } elseif ($existingMapping['declared'] == $this->rootXmlEntityName) {
                throw MappingException::duplicateXmlFieldName($this->name, $mapping['name']);
            }
        }

        if (!isset($mapping['node'])) {
            if (Type::hasType($mapping['type'])) {
                // Map object and array to "text", everything else to "attribute"
                if (in_array($mapping['type'], array(Type::OBJECT, Type::TARRAY))) {
                    $mapping['node'] = self::XML_TEXT;
                } else {
                    $mapping['node'] = self::XML_ATTRIBUTE;
                }
            } else {
                $mapping['node'] = self::XML_ELEMENT;
            }
        }
        if (!in_array($mapping['node'], self::getXmlNodeTypes())) {
            throw MappingException::xmlBindingTypeUnknown($mapping['fieldName'], $mapping['node']);
        }

        if (!isset($mapping['direct'])) {
            $mapping['direct'] = true;
        }

        if (!isset($mapping['nullable'])) {
            $mapping['nullable'] = false;
        }

        if (!isset($mapping['required'])) {
            $mapping['required'] = false;
        }

        if (!isset($mapping['container'])) {
            $mapping['container'] = false;
        }
        
        if (!isset($mapping['collection'])) {
            $mapping['collection'] = false;
        }

        if (!isset($mapping['getMethod'])) {
            $mapping['getMethod'] = $this->inferGetter($mapping['fieldName']);
        }

        if (!isset($mapping['setMethod'])) {
            $mapping['setMethod'] = $this->inferSetter($mapping['fieldName']);
        }

        if (!isset($mapping['id'])) {
            $mapping['id'] = false;
        } else {
            $this->identifier = $mapping['fieldName'];
        }

        $this->xmlFieldMap[$mapping['name']] = $mapping['fieldName'];
        $this->fieldMappings[$mapping['fieldName']] = $mapping;
        return $mapping;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function inferGetter($fieldName)
    {
        return 'get' . ucfirst(Inflector::camelize($fieldName));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function inferSetter($fieldName)
    {
        return 'set' . ucfirst(Inflector::camelize($fieldName));
    }


    /**
     * INTERNAL:
     * Adds a field mapping without completing/validating it.
     * This is mainly used to add inherited field mappings to derived classes.
     *
     * @param array $mapping
     */
    public function addInheritedFieldMapping(array $fieldMapping)
    {
        if (!isset($this->fieldMappings[$fieldMapping['fieldName']])) {
            $this->fieldMappings[$fieldMapping['fieldName']] = $fieldMapping;
            $this->xmlFieldMap[$fieldMapping['name']] = $fieldMapping['fieldName'];
        }
    }

    /**
     * Checks whether the class has a (mapped) field with a certain name.
     *
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * @return boolean
     */
    public function hasFieldWrapping($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['wrapper']);
    }

    /**
     * @return boolean
     */
    public function hasXmlField($xmlName)
    {
        return isset($this->xmlFieldMap[$xmlName]);
    }

    /**
     * Checks whether the class has a mapped association (embed or reference) with the given field name.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasAssociation($fieldName)
    {
        return $this->hasReference($fieldName);
    }

    /**
     * Checks whether the class has a mapped association with the given field name.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasReference($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['reference']);
    }

    /**
     * Checks whether the class has a mapped reference or embed for the specified field and
     * is a single valued association.
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is single-valued, FALSE otherwise.
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return false;
    }
    
    /**
     * Checks whether the class has a mapped reference or embed for the specified field and
     * is a single valued association.
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is single-valued, FALSE otherwise.
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return false;
    }

    /**
     * The name of this XmlEntity class.
     *
     * @return string $name The XmlEntity class name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns an array of all field mappings
     *
     * @return array
     */
    public function getFieldMappings()
    {
        return $this->fieldMappings;
    }


    /**
     * Gets the type of a field.
     *
     * @param string $fieldName
     * @return string
     */
    public function getTypeOfField($fieldName)
    {
        if (!isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }

        return isset($this->fieldMappings[$fieldName]) ?
                $this->fieldMappings[$fieldName]['type'] : null;
    }

    /**
     * Gets the type of an xml name.
     *
     * @return string one of ("node", "attribute", "text")
     */
    public function getFieldXmlNode($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]) ?
                $this->fieldMappings[$fieldName]['node'] : null;
    }

    /**
     * 
     */
    public function getFieldXmlName($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return isset($this->fieldMappings[$fieldName]) ?
                $this->fieldMappings[$fieldName]['name'] : null;
    }


    /**
     * Gets the field name for a xml name.
     * If no field name can be found the xml name is returned.
     *
     * @param string $xmlName    xml name
     * @return string            field name
     */
    public function getFieldName($xmlName)
    {
        return isset($this->xmlFieldMap[$xmlName]) ?
                $this->xmlFieldMap[$xmlName] : null;
    }

    /**
     * Gets the mapping of a (regular) field that holds some data but not a
     * reference to another object.
     *
     * @param string $fieldName  The field name.
     * @return array  The field mapping.
     */
    public function getFieldMapping($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName];
    }

    /**
     * @return boolean
     */
    public function isRequired($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName]['required'] ? true : false;
    }

    /**
     * @return boolean
     */
    public function isDirect($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName]['direct'] ? true : false;
    }

    /**
     * Checks whether a mapped field is inherited from an entity superclass.
     *
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['inherited']);
    }

    /**
     * @return boolean
     */
    public function isCollection($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName]['collection'] ? true : false;
    }

    /**
     * @param string $fieldName
     * @return boolean
     *
     */
    public function isNullable($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName]['nullable'] ? true : false;
    }

    /**
     * Checks whether the class will generate a new \XmlId instance for us.
     *
     * @return boolean TRUE if the class uses the AUTO generator, FALSE otherwise.
     */
    public function isIdGeneratorAuto()
    {
        return $this->generatorType == self::GENERATOR_TYPE_AUTO;
    }

    /**
     * Checks whether the class will use a collection to generate incremented identifiers.
     *
     * @return boolean TRUE if the class uses the INCREMENT generator, FALSE otherwise.
     */
    public function isIdGeneratorIncrement()
    {
        return $this->generatorType == self::GENERATOR_TYPE_INCREMENT;
    }

    /**
     * Checks whether the class will generate a uuid id.
     *
     * @return boolean TRUE if the class uses the UUID generator, FALSE otherwise.
     */
    public function isIdGeneratorUuid()
    {
        return $this->generatorType == self::GENERATOR_TYPE_UUID;
    }

    /**
     * Checks whether the class uses no id generator.
     *
     * @return boolean TRUE if the class does not use any id generator, FALSE otherwise.
     */
    public function isIdGeneratorNone()
    {
        return $this->generatorType == self::GENERATOR_TYPE_NONE;
    }

    /**
     * Returns all bind xml node types
     *
     * @static
     * @return array
     */
    public static function getXmlNodeTypes()
    {
        return self::$nodeTypes;
    }

    /**
     * @param array $xmlNamespaces
     * @return void
     */
    public function setXmlNamespaces(array $xmlNamespaces)
    {
        $this->xmlNamespaces = $xmlNamespaces;
    }

    /**
     * @return array
     */
    public function getXmlNamespaces()
    {
        return $this->xmlNamespaces;
    }

}
