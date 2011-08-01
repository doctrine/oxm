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

namespace Doctrine\OXM;

use \Exception;

/**
 * Base exception class for all OXM exceptions.
 *
 */
class OXMException extends Exception
{
    public static function missingMappingDriverImpl()
    {
        return new self("It's a requirement to specify a Mapping Driver and pass it ".
            "to Doctrine\\OXM\\Configuration::setMappingDriverImpl().");
    }

    public static function entityMissingAssignedId($entity)
    {
        return new self("Entity of type " . get_class($entity) . " is missing an assigned ID.");
    }

    public static function unrecognizedField($field)
    {
        return new self("Unrecognized field: $field");
    }

    public static function invalidFindByCall($entityName, $fieldName, $method)
    {
        return new self(
            "Entity '".$entityName."' has no field '".$fieldName."'. ".
            "You can therefore not call '".$method."' on the entities' repository"
        );
    }

    public static function notSupported() {
        return new self("This behaviour is (currently) not supported by Doctrine 2");
    }

    public static function mappingCacheNotConfigured()
    {
        return new self('Class Mapping Cache is not configured.');
    }

    public static function unknownEntityNamespace($entityNamespaceAlias)
    {
        return new self(
            "Unknown Entity namespace alias '$entityNamespaceAlias'."
        );
    }

    public static function unknownType($name)
    {
        return new self("Type '$name' is unknown");
    }
    public static function typeExists($name)
    {
        return new self("Type '$name' already exists");
    }
    public static function typeNotFound($name)
    {
        return new self("Type '$name' was not found");
    }

    public static function cannotPersistMappedSuperclass($className)
    {
        return new self("OXM cannot persist XmlMappedSuperclass '$className'.  OXM can only persist root entities.");
    }

    public static function canOnlyPersistRootClasses($className)
    {
        return new self("OXM can only persist xml root mapped entities.  '$className' is not a root entity");
    }

    public static function entityManagerClosed()
    {
        return new self("The XmlEntityManager is closed");
    }
    
    public static function xmlEntityNotFound($className, $identifier)
    {
        return new self(sprintf('The "%s" XmlEntity with identifier "%s" could not be found.', $className, $identifier));
    }
    
    public static function fileMappingDriversRequireConfiguredDirectoryPath()
    {
        return new self('File mapping drivers must have a valid directory path, however the given path seems to be incorrect!');
    }
    
    public static function mappingNotFound($className, $fieldName)
    {
        return new self("No mapping found for field '$fieldName' in class '$className'.");
    }
}
