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

use Doctrine\OXM\OXMException;

/**
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class MappingException extends OXMException
{
    public static function pathRequired()
    {
        return new self("Specifying the paths to your entities is required ".
            "in the AnnotationDriver to retrieve all class names.");
    }

    public static function missingFieldForBinding($fieldName)
    {
        return new self("An xml binding was detected for field '$fieldName' but this field is not properly mapped");
    }

    public static function duplicateXmlFieldName($className, $bindingName)
    {
        return new self("A mapping for this xml binding '$bindingName' already exists for the class '$className'");
    }

    public static function duplicateXmlNameBinding($className, $xmlName)
    {
        return new self("An xml mapping '$xmlName' already exists for the class '$className'");
    }

    public static function fieldTypeNotFound($className, $fieldName, $type)
    {
        return new self("The type '$type' on field '$fieldName' could not be found");
    }

    public static function customTypeWithoutNodeElement($className, $fieldName)
    {
        return new self("Custom types on field '$fieldName' of class '$className' can only be used with xml binding type '" . ClassMetadataInfo::XML_ELEMENT . "'");
    }

    public static function xmlBindingTypeUnknown($fieldName, $bindingType)
    {
        return new self("The xml node binding type for '$fieldName' is unknown:  '$bindingType'");
    }

    public static function fieldGetMethodDoesNotExist($className, $fieldName, $getMethod)
    {
        return new self("The get method '$getMethod' on class '$className' for field '$fieldName' does not exist");
    }

    public static function fieldSetMethodDoesNotExist($className, $fieldName, $setMethod)
    {
        return new self("The set method '$setMethod' on class '$className' for field '$fieldName' does not exist");
    }

    public static function missingFieldType($className, $fieldName)
    {
        return new self("The type of field '$fieldName' on class '$className' is a required field");
    }

    public static function fieldRequired($entityName, $fieldName)
    {
        return new self("The '$fieldName' field of Entity '$entityName' is a required field");
    }

    public static function couldNotInferGetterMethod($entityName, $fieldName)
    {
        return new self("Could not infer getter for '$fieldName' of Entity '$entityName'");
    }

    public static function couldNotInferSetterMethod($entityName, $fieldName)
    {
        return new self("Could not infer getter for '$fieldName' of Entity '$entityName'");
    }

    public static function invalidInheritanceType($entityName, $type)
    {
        return new self("The inheritance type '$type' specified for '$entityName' does not exist.");
    }

    public static function missingFieldName($entity)
    {
        return new self("The field or association mapping misses the 'fieldName' attribute in entity '$entity'.");
    }


    public static function mappingFileNotFound($entityName, $fileName)
    {
        return new self("No mapping file found named '$fileName' for class '$entityName'.");
    }

    public static function mappingNotFound($className, $fieldName)
    {
        return new self("No mapping found for field '$fieldName' on class '$className'.");
    }

    public static function fileMappingDriversRequiresConfiguredDirectoryPath($path = null)
    {
        if ( ! empty($path)) {
            $path = '[' . $path . ']';
        }

        return new self(
            'File mapping drivers must have a valid directory path, ' .
            'however the given path ' . $path . ' seems to be incorrect!'
        );
    }

    /**
     * Generic exception for invalid mappings.
     *
     * @param string $fieldName
     */
    public static function invalidMapping($fieldName)
    {
        return new self("The mapping of field '$fieldName' is invalid.");
    }

    /**
     * Exception for reflection exceptions - adds the entity name,
     * because there might be long classnames that will be shortened
     * within the stacktrace
     *
     * @param string $entity The entity's name
     * @param \ReflectionException $previousException
     */
    public static function reflectionFailure($entity, \ReflectionException $previousException)
    {
        return new self('An error occurred in ' . $entity, 0, $previousException);
    }

    public static function classIsNotAValidXmlEntity($className)
    {
        return new self('Class '.$className.' is not a valid xml entity.');
    }
    public static function classIsNotAValidXmlEntityOrXmlMappedSuperClass($className)
    {
        return new self('Class '.$className.' is not a valid xml entity or xml mapped super class.');
    }

    public static function propertyTypeIsRequired($className, $propertyName)
    {
        return new self("The attribute 'type' is required for the column description of property ".$className."::\$".$propertyName.".");
    }

    /**
     *
     * @param string $entity The entity's name
     * @param string $fieldName The name of the field that was already declared
     */
    public static function duplicateFieldMapping($entity, $fieldName) {
        return new self('Property "'.$fieldName.'" in "'.$entity.'" was already declared, but it must be declared only once');
    }

    public static function noFieldNameFoundForXmlName($className, $column)
    {
        return new self("Cannot find a field on '$className' that is mapped to column '$column'. Either the ".
            "field does not exist or an association exists but it has multiple join columns.");
    }

}
