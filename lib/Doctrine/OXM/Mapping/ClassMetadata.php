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

use Doctrine\OXM\Util\Inflector;

/**
 * A <tt>ClassMetadata</tt> instance holds all the object-document mapping metadata
 * of a document and it's references.
 *
 * Once populated, ClassMetadata instances are usually cached in a serialized form.
 *
 * <b>IMPORTANT NOTE:</b>
 *
 * The fields of this class are only public for 2 reasons:
 * 1) To allow fast READ access.
 * 2) To drastically reduce the size of a serialized instance (private/protected members
 *    get the whole class name, namespace inclusive, prepended to every property in
 *    the serialized representation).
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class ClassMetadata extends ClassMetadataInfo
{
    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var array
     */
    public $reflFields = array();

    /**
     * The prototype from which new instances of the mapped class are created.
     *
     * @var object
     */
    private $prototype;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-document mapping
     * metadata of the class with the given name.
     *
     * @param string $entityName The name of the document class the new instance is used for.
     */
    public function __construct($entityName)
    {
        parent::__construct($entityName);
        $this->reflClass = new \ReflectionClass($entityName);
        $this->namespace = $this->reflClass->getNamespaceName();
        $this->xmlName = Inflector::xmlize($this->reflClass->getShortName());
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
            if (!array_key_exists('setMethod', $this->fieldMappings[$fieldName])) {
                $this->fieldMappings[$fieldName]['setMethod'] = $this->inferSetter($fieldName);
            }
            $setter = $this->fieldMappings[$fieldName]['setMethod'];

            if ($this->reflClass->hasMethod($setter)) {
                return call_user_func(array($entity, $setter), $value);
            } else {
                throw MappingException::fieldSetMethodDoesNotExist($this->name, $fieldName, $setter);
            }
        }
    }

    /**
     * Populates the entity identifier of an entity.
     *
     * @param object $xmlEntity
     * @param mixed $id
     * @todo Rename to assignIdentifier()
     */
    public function setIdentifierValue($xmlEntity, array $id)
    {
        foreach ($id as $idField => $idValue) {
            $this->reflFields[$idField]->setValue($xmlEntity, $idValue);
        }
    }

    /**
     * Gets the document identifier.
     *
     * @param object $xmlEntity
     * @return string $id
     */
    public function getIdentifierValue($xmlEntity)
    {
        return (string) $this->reflFields[$this->identifier]->getValue($xmlEntity);
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
            if (!array_key_exists('getMethod', $this->fieldMappings[$fieldName])) {
                $this->fieldMappings[$fieldName]['getMethod'] = $this->inferGetter($fieldName);
            }
            $getter = $this->fieldMappings[$fieldName]['getMethod'];

            if ($this->reflClass->hasMethod($getter)) {
                return call_user_func(array($entity, $getter));
            } else {
                throw MappingException::fieldGetMethodDoesNotExist($this->name, $fieldName, $getter);
            }
        }
    }

    /**
     * Map a field.
     *
     * @param array $mapping The mapping information.
     */
    public function mapField(array $mapping)
    {
        $mapping = parent::mapField($mapping);

        if ($this->reflClass->hasProperty($mapping['fieldName'])) {
            $reflProp = $this->reflClass->getProperty($mapping['fieldName']);
            $reflProp->setAccessible(true);
            $this->reflFields[$mapping['fieldName']] = $reflProp;
        }
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
            'fieldMappings',
            'xmlFieldMap', //TODO: Not really needed. Can use fieldMappings[$fieldName]['name']
            'identifier',
            'name',
            'namespace',
            'isRoot',
            'xmlName',
            'generatorType',
            'idGenerator'
        );

        // The rest of the metadata is only serialized if necessary.
        if ($this->lifecycleCallbacks) {
            $serialized[] = 'lifecycleCallbacks';
        }
        if ($this->changeTrackingPolicy != self::CHANGETRACKING_DEFERRED_IMPLICIT) {
            $serialized[] = 'changeTrackingPolicy';
        }

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }
        if ($this->isMappedSuperclass) {
            $serialized[] = 'isMappedSuperclass';
        }
        if ($this->xmlNamespaces) {
            $serialized[] = 'xmlNamespaces';
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
        $this->reflClass = new \ReflectionClass($this->name);

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
        if ($this->prototype === null) {
            $this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
        }
        return clone $this->prototype;
    }
}
