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

use ReflectionClass,
    ReflectionProperty,
    Doctrine\OXM\Types\Type;

/**
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class Mapping
{
    // XML Type Constants
    const XML_ELEMENT   = 'element';
    const XML_ATTRIBUTE = 'attribute';
    const XML_TEXT      = 'text';

    /** The map of supported xml node types. */
    private static $_nodeTypes = array(
        self::XML_TEXT,
        self::XML_ATTRIBUTE,
        self::XML_ELEMENT,
    );

    /**
     * The name of th class
     *
     * @var string
     */
    public $className;
    
    /**
     * The namespace of this class
     *
     * @var string
     */
    public $classNamespace;

    /**
     * The xml node name to map this class to
     *
     * @var string
     */
    public $xmlName;

//    /**
//     * Try to auto-complete class introspection
//     *
//     * @var boolean (defaults to false)
//     */
//    public $autoComplete = false;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var \ReflectionClass
     */
    public $reflClass;

    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var array
     */
    public $reflFields = array();


    /**
     * READ-ONLY: The registered lifecycle callbacks for entities of this class.
     *
     * @var array
     */
    public $lifecycleCallbacks = array();


    /**
     * READ-ONLY: The association mappings of this class.
     *
     * The mapping definition array supports the following keys:
     *
     * - <b>fieldName</b> (string)
     * The name of the field in the entity this mapping is associated with
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
     * Defines if this field is required or not.  Checked during marshalling and unmarshalling.     *
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
    public $fieldMappings;

    /**
     * @var array
     */
    public $fieldBindings;

    /**
     * Mapping xml node names back to class fields
     * keys are xml names
     *
     * @var array
     */
    public $xmlFieldMap;

    /**
     * @var array
     */
    public $mapTo;

    /**
     * The prototype from which new instances of the mapped class are created.
     *
     * @var object
     */
    private $_prototype;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-xml mapping
     * metadata of the class with the given name.
     *
     * @param string $entityName The name of the entity class the new instance is used for.
     */
    public function __construct($entityName)
    {
        $this->className = $entityName;
        $this->reflClass = new \ReflectionClass($entityName);
        $this->classNamespace = $this->reflClass->getNamespaceName();
//        $this->reflFields = $this->reflClass->getProperties();
    }

    public function setClassName($className)
    {
        $this->className = $className;
    }


    /**
     * Gets the xml node name used to map this class to an xml node
     *
     * @return string
     */
    public function getXmlName()
    {
        return $this->mapTo['xml'];
    }

//    /**
//     * @param boolean $autoComplete
//     * @return void
//     */
//    public function setAutoComplete($autoComplete)
//    {
//        $this->autoComplete = $autoComplete;
//
//        if ($autoComplete) {
//            $properties = $this->reflClass->getProperties();
//            foreach ($properties as $property) {
//                if (!$this->hasField($property->getName())) {
//                    // use defaults
//                    $this->mapField(array('name' => $property->getName()));
//                    $this->mapBindingToField($property->getName(), array());
//                }
//            }
//        }
//    }
//
//    /**
//     * @return boolean
//     */
//    public function getAutoComplete()
//    {
//        return $this->autoComplete;
//    }

    /**
     * Gets the ReflectionClass instance of the mapped class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass()
    {
        if ( ! $this->reflClass) {
            $this->reflClass = new \ReflectionClass($this->className);
        }
        return $this->reflClass;
    }


    /**
     * Gets the ReflectionPropertys of the mapped class.
     *
     * @return \ReflectionProperty[] An array of ReflectionProperty instances.
     */
    public function getReflectionProperties()
    {
        return $this->reflFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $name
     * @return ReflectionProperty
     */
    public function getReflectionProperty($name)
    {
        return $this->reflFields[$name];
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
     * Dispatches the lifecycle event of the given entity to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @param string $event The lifecycle event.
     * @param XmlEntity $entity The XmlEntity on which the event occured.
     */
    public function invokeLifecycleCallbacks($lifecycleEvent, $entity)
    {
        foreach ($this->lifecycleCallbacks[$lifecycleEvent] as $callback) {
            $entity->$callback();
        }
    }

    /**
     * @param array $xmlMapTo
     * @return void
     */
    public function mapTo(array $xmlMapTo)
    {
        $this->_validateAndCompleteMapTo($xmlMapTo);
        $this->mapTo = $xmlMapTo;
    }

    /**
     * @throws
     * @param  $fieldName
     * @param array $binding
     * @return void
     */
    public function mapBindingToField($fieldName, array $binding)
    {
        // Only set bindings on field names that exist
        if (!isset($this->fieldMappings[$fieldName])) {
            throw MappingException::missingFieldForBinding($fieldName);
        }
        $this->_validateAndCompleteBindingToField($fieldName, $binding);
        $this->fieldBindings[$fieldName] = $binding;
        $this->xmlFieldMap[$binding['name']] = $fieldName;
    }

    /**
     * @param array
     * @return void
     */
    public function mapField(array $mapping)
    {
        $this->_validateAndCompleteFieldMapping($mapping);
        if (isset($this->fieldMappings[$mapping['name']])) {
            throw MappingException::duplicateFieldMapping($this->className, $mapping['name']);
        }
        $this->fieldMappings[$mapping['name']] = $mapping;
    }

    /**
     * Validate and complete the mapTo
     *
     * @param array $mapTo
     * @return void
     */
    protected function _validateAndCompleteMapTo(array &$mapTo)
    {
        if (!isset($mapTo['xml']) || strlen($mapTo['xml']) == 0) {
            $mapTo['xml'] = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $this->reflClass->getShortName()));
            // todo check factory to see if this is already mapped?  maybe do later...
        }

        // nsUrl
        if (!isset($mapTo['nsUrl'])) {
            $mapTo['nsUrl'] = null;
        }

        // nsPrefix
        if (!isset($mapTo['nsPrefix'])) {
            $mapTo['nsPrefix'] = null;
        }
    }

    /**
     * Validates and completes an xml binding to a field
     *
     * @throws MappingException
     * @param  $fieldName
     * @param array $binding
     * @return void
     */
    protected function _validateAndCompleteBindingToField($fieldName, array &$binding)
    {
        // Complete binding name
        if ( ! isset($binding['name'])) {
            $binding['name'] = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $fieldName));
        } else {
            if ($binding['name'][0] == '`') {
                $binding['name'] = trim($binding['name'], '`');
                $binding['quoted'] = true;
            }
        }
        if (isset($this->fieldBindings[$binding['name']])) {
            throw MappingException::duplicateXmlFieldName($this->className, $binding['name']);
        }

        // Valid node types
        if (!isset($binding['node'])) {
            if (Type::hasType($this->fieldMappings[$fieldName]['type'])) {

                // Map object and array to "text", everything else to "attribute"
                if (in_array($this->fieldMappings[$fieldName]['type'], array(Type::OBJECT, Type::TARRAY))) {
                    $binding['node'] = self::XML_TEXT;
                } else {
                    $binding['node'] = self::XML_ATTRIBUTE;
                }
            } else {
                $binding['node'] = self::XML_ELEMENT;
            }
        }
        
        if (!in_array($binding['node'], self::getXmlNodeTypes())) {
            throw MappingException::xmlBindingTypeUnknown($fieldName, $binding['node']);
        }

        // todo - support references
        if (!isset($binding['reference'])) {
            $binding['reference'] = false;
        }
    }


    /**
     * Validates & completes the given field mapping.
     *
     * @param array $mapping  The field mapping to validated & complete.
     * @return array  The validated and completed field mapping.
     */
    protected function _validateAndCompleteFieldMapping(array &$mapping)
    {
        // Check mandatory fields
        if (!isset($mapping['name']) || strlen($mapping['name']) == 0) {
            throw MappingException::missingFieldName($this->className);
        }
        if (!isset($mapping['type']) || strlen($mapping['type']) == 0) {
            throw MappingException::missingFieldType($this->className, $mapping['name']);
        }

//        // validate type
//        if (!Type::hasType($mapping['type'])) {
//            // delay validation until factory, have to check if type is mapped class or not
//            $this->_fieldTypeValidations[] = $mapping['name'];
//        }

        // todo - handler field (what's this for?)
        if (!isset($mapping['handler'])) {
            $mapping['handler'] = null;
        }

        // Field is required?  default false
        if (!isset($mapping['required'])) {
            $mapping['required'] = false;
        }


        // Direct field access (default to false unless autocomplete)
        if (!isset($mapping['direct'])) {
//        if (!isset($mapping['direct']) && $this->autoComplete == false) {
            $mapping['direct'] = false;
        } else {
            $mapping['direct'] = true;
        }

        // Field is transient?  default false
        if (!isset($mapping['transient'])) {
            $mapping['transient'] = false;
        }

        // Field is nillable?  defaults to false (means the field won't be handled if its empty)
        if (!isset($mapping['nillable'])) {
            $mapping['nillable'] = false;
        }

        // Field is container field?  defaults to false
        if (!isset($mapping['container'])) {
            $mapping['container'] = false;
            // requires xml bind type of 'element'
        }

        if (!$mapping['direct']) {
            // get Method handling
            if (!isset($mapping['getMethod'])) {  // run through loop, and check a few
                $proposedSetter = $this->inferGetter($mapping['name']);
                if ($this->reflClass->hasMethod($proposedSetter)) {
                    $mapping['getMethod'] = $proposedSetter;
                } else {
                    throw MappingException::couldNotInferGetterMethod($this->className, $mapping['name']);
                }
            }
            if (!$this->reflClass->hasMethod($mapping['getMethod'])) {
                throw MappingException::fieldGetMethodDoesNotExist($this->className, $mapping['name'], $mapping['getMethod']);
            }

            // set Method handling
            if (!isset($mapping['setMethod'])) {  // run through loop, and check a few
                $proposedSetter = $this->inferSetter($mapping['name']);
                if ($this->reflClass->hasMethod($proposedSetter)) {
                    $mapping['setMethod'] = $proposedSetter;
                } else {
                    throw MappingException::couldNotInferSetterMethod($this->className, $mapping['name']);
                }
            }
            if (!$this->reflClass->hasMethod($mapping['setMethod'])) {
                throw MappingException::fieldSetMethodDoesNotExist($this->className, $mapping['name'], $mapping['setMethod']);
            }
        }

//        // createMethod
//        if (!isset($mapping['createMethod'])) {
//            $mapping['createMethod'] = null;
//        }

        // Field is collection? default false
        if (!isset($mapping['collection'])) {
            $mapping['collection'] = false;
        }


        // Store ReflectionProperty of mapped field for easy get/set
        $refProp = $this->reflClass->getProperty($mapping['name']);
        $refProp->setAccessible(true);
        $this->reflFields[$mapping['name']] = $refProp;
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

    public function hasXmlField($xmlName)
    {
        return isset($this->xmlFieldMap[$xmlName]);
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

    public function getFieldBinding($fieldName)
    {
        return isset($this->fieldBindings[$fieldName]) ?
                $this->fieldBindings[$fieldName] : null;
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
            throw MappingException::mappingNotFound($this->className, $fieldName);
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
        return isset($this->fieldBindings[$fieldName]) ?
                $this->fieldBindings[$fieldName]['node'] : null;
    }

    /**
     * Gets the type of an xml name.
     *
     * @return string
     */
    public function getFieldXmlName($fieldName)
    {
        return isset($this->fieldBindings[$fieldName]) ?
                $this->fieldBindings[$fieldName]['name'] : null;
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
     * Sets the specified field to the specified value on the given entity.
     *
     * @param object $entity
     * @param string $fieldName
     * @param mixed $value
     */
    public function setFieldValue($entity, $fieldName, $value)
    {
        if ($this->fieldMappings[$fieldName]['direct']) {
            $this->reflFields[$fieldName]->setValue($entity, $value);
        } else {
            $setter = $this->fieldMappings[$fieldName]['setMethod'];

            if ($this->reflClass->hasMethod($setter)) {
                return call_user_func(array($entity, $setter), $value);
            } else {
                throw MappingException::fieldSetMethodDoesNotExist($this->className, $fieldName, $setter);
            }
        }
    }

    /**
     * Gets the specified field's value off the given entity.
     *
     * @param object $entity
     * @param string $fieldName
     */
    public function getFieldValue($entity, $fieldName)
    {
        if ($this->fieldMappings[$fieldName]['direct']) {
            return $this->reflFields[$fieldName]->getValue($entity);
        } else {
            $getter = $this->fieldMappings[$fieldName]['getMethod'];

            if ($this->reflClass->hasMethod($getter)) {
                return call_user_func(array($entity, $getter));
            } else {
                throw MappingException::fieldGetMethodDoesNotExist($this->className, $fieldName, $getter);
            }
        }

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
            throw MappingException::mappingNotFound($this->className, $fieldName);
        }
        return $this->fieldMappings[$fieldName];
    }

    /**
     * @return boolean
     */
    public function isFieldRequired($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->className, $fieldName);
        }
        return $this->fieldMappings[$fieldName]['required'] ? true : false;
    }

    /**
     * @return boolean
     */
    public function isFieldDirectAccess($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->className, $fieldName);
        }
        return $this->fieldMappings[$fieldName]['direct'] ? true : false;
    }

    /**
     * @return boolean
     */
    public function isFieldCollection($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->className, $fieldName);
        }
        return $this->fieldMappings[$fieldName]['collection'] ? true : false;
    }

    public function isFieldNillable($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->className, $fieldName);
        }
        return $this->fieldMappings[$fieldName]['nillable'] ? true : false;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    private function inferGetter($fieldName)
    {
        return 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldName)));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    private function inferSetter($fieldName)
    {
        return 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldName)));
    }

        /**
     * Determines which fields get serialized.
     *
     * It is only serialized what is necessary for best unserialization performance.
     * That means any metadata properties that are not set or empty or simply have
     * their default value are NOT serialized.
     *
     * Parts that are also NOT serialized because they can not be properly unserialized:
     *      - reflClass (ReflectionClass)
     *      - reflFields (ReflectionProperty array)
     *
     * @return array The names of all the fields that should be serialized.
     */
    public function __sleep()
    {
        // This metadata is always serialized/cached.
        $serialized = array(
            'xmlFieldMap', //TODO: Not really needed. Can use fieldMappings[$fieldName]['columnName']
            'fieldMappings',
            'fieldNames',
            'className',
            'classNamespace',
            'xmlName',
//            'autoComplete'
        );

        if ($this->lifecycleCallbacks) {
            $serialized[] = 'lifecycleCallbacks';
        }

        return $serialized;
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     *
     * @return void
     */
    public function __wakeup()
    {
        // Restore ReflectionClass and properties
        $this->reflClass = new ReflectionClass($this->className);

        foreach ($this->fieldMappings as $field => $mapping) {
            $reflField = $this->reflClass->getProperty($field);
            $reflField->setAccessible(true);
            $this->reflFields[$field] = $reflField;
        }
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance()
    {
        if ($this->_prototype === null) {
            $this->_prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->className), $this->className));
        }
        return clone $this->_prototype;
    }

    /**
     * Returns all bind xml node types
     *
     * @static
     * @return array
     */
    public static function getXmlNodeTypes()
    {
        return self::$_nodeTypes;
    }
}
