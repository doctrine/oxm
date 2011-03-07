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

namespace Doctrine\OXM\Marshaller;

use \SimpleXmlElement,
    \Doctrine\Common\Util\Debug,
    \Doctrine\OXM\Mapping\ClassMetadataInfo,
    \Doctrine\OXM\Mapping\ClassMetadataFactory,
    \Doctrine\OXM\Mapping\MappingException,
    \Doctrine\OXM\Types\Type,
    \Doctrine\OXM\Events;

/**
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class SimpleXmlMarshaller extends AbstractMarshaller
{



    /**
     *
     */
    public function marshal($mappedObject)
    {
        // Since the SimpleXML API is silly, we'll wrap the object in a parent "dummy"
        // element to make adding children more happy with recursion
        $dummyParent = new SimpleXMLElement('<dummy-parent/>');
        $this->doMarshal($mappedObject, $dummyParent);


        // first child should be our mapped object
        $children = $dummyParent->children();

        // TODO - don't hardcode the encoding
        return '<?xml version="1.0" encoding="UTF-8"?>' . $children[0]->asXML();
    }

    /**
     * @param object $mappedObject
     * @param SimpleXMLElement $parent
     * @return void
     */
    private function doMarshal($mappedObject, SimpleXMLElement &$parent)
    {
        $className = get_class($mappedObject);
        $classMetadata = $this->classMetadataFactory->getMetadataFor($className);
//        print_r($classMapping);
        
        if (!$this->classMetadataFactory->hasMetadataFor($className)) {
            throw new MarshallerException("A mapping does not exist for class '$className'");
        }

        // PreMarshall Hook
        if ($classMetadata->hasLifecycleCallbacks(Events::preMarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::preMarshal, $mappedObject);
        }


        $refClass = new \ReflectionClass($mappedObject);

        $nsUrl = $classMetadata->getXmlNamespaceUrl();
        $nsPrefix = $classMetadata->getXmlNamespacePrefix();
        
        // SimpleXML api sucks... maybe doesn't support namespaces properly?
        // There's some stupid SimpleXML append scripts on the internets, but it'll kill performance

//        $namespacedXml = new \SimpleXMLElement('<' . . '/ >', null, false, $nsPrefix, true);
        $xml = $parent->addChild($classMetadata->getXmlName(), null, $nsUrl);
        
        foreach ($classMetadata->getReflectionProperties() as $property) {
            $fieldName = $property->getName();
            
            if (!$refClass->hasProperty($fieldName)) {
                continue;
            }
                        
            $fieldValue = $classMetadata->getFieldValue($mappedObject, $fieldName);

            if ($classMetadata->isRequired($fieldName) && $fieldValue === null) {
                throw MarshallerException::fieldRequired($className, $fieldName);
            }

            $fieldXmlType = $classMetadata->getFieldXmlNode($fieldName);
            $fieldXmlName = $classMetadata->getFieldXmlName($fieldName);

            $fieldType = $classMetadata->getTypeOfField($fieldName);

            if ($fieldValue !== null || $classMetadata->isNillable($fieldName)) {
                if (!Type::hasType($fieldType) && $fieldXmlType === ClassMetadataInfo::XML_ELEMENT) {
                    // check for native type
                    if ($this->classMetadataFactory->hasMetadataFor($fieldType)) {
                        if ($classMetadata->isCollection($fieldName)) {
                            foreach ($fieldValue as $value) {
                                $this->doMarshal($value, $xml);
                            }
                        } else {
                            $this->doMarshal($fieldValue, $xml);
                        }
                    }
                } else {
                    $type = Type::getType($fieldType);

                    switch ($fieldXmlType) {
                        case ClassMetadataInfo::XML_ATTRIBUTE:
                            $xml->addAttribute($fieldXmlName, $type->convertToXmlValue($fieldValue));
                            break;

                        case ClassMetadataInfo::XML_TEXT:
                            $xml->addChild($fieldXmlName, $type->convertToXmlValue($fieldValue));
                            break;
                    }
                }
            }
        }

        // PostMarshal hook
        if ($classMetadata->hasLifecycleCallbacks(Events::postMarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::postMarshal, $mappedObject);
        }
    }

    function __clone()
    {
        // TODO: Implement __clone() method.
    }

    /**
     * @param string $xml
     * @return object
     */
    public function unmarshal($xml)
    {
        return $this->doUnmarshal(new \SimpleXMLElement($xml));
    }

    /**
     * @throws \Doctrine\OXM\Mapping\MappingException
     * @param \SimpleXmlElement $xml
     * @return object
     */
    private function doUnmarshal(\SimpleXMLElement $xml)
    {
        $elementName = $xml->getName();
        $allMappedXmlNodes = $this->classMetadataFactory->getAllXmlNodes();
        $knownMappedNodes = array_keys($allMappedXmlNodes);

        if (!in_array($elementName, $knownMappedNodes)) {
            throw MappingException::invalidMapping($elementName);
        }

        $classMetadata = $this->classMetadataFactory->getMetadataFor($allMappedXmlNodes[$elementName]);

        $mappedObject = $classMetadata->newInstance();

        // Pre Unmarshal hook
        if ($classMetadata->hasLifecycleCallbacks(Events::preUnmarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::preUnmarshal, $mappedObject);
        }

        // Handle attributes first
        $attributes = $xml->attributes();
        foreach ($attributes as $attributeKey => $attributeValue) {
            if ($classMetadata->hasXmlField($attributeKey)) {
                $fieldName = $classMetadata->getFieldName($attributeKey);
                $fieldMapping = $classMetadata->getFieldMapping($fieldName);
                $type = Type::getType($fieldMapping['type']);

                // todo ensure this is an attribute mapping

                if ($classMetadata->isRequired($fieldName) && $attributeValue === null) {
                    throw MappingException::fieldRequired($classMetadata->name, $fieldName);
                }

                // simplexml cast to string for value, TODO - should type convert result
                $fieldValue = (string) $attributeValue;

                $classMetadata->setFieldValue($mappedObject, $fieldName, $type->convertToPHPValue($fieldValue));
            }
        }

        // Handle children
        $children = $xml->children();

        if (count($children) > 0) {
            $collectionElements = array();

            foreach ($children as $child) {
                $childNodeName = $child->getName();

                if ($classMetadata->hasXmlField($childNodeName)) {

                    $fieldName = $classMetadata->getFieldName($childNodeName);

                    // todo - check for collection
                    // todo - add support for collection wrapper element

                    // Check for mapped entity as child, add recursively
                    $fieldMapping = $classMetadata->getFieldMapping($fieldName);
                    if ($this->classMetadataFactory->hasMetadataFor($fieldMapping['type'])) {
                        // todo ensure this is an element node


                        $fieldValue = $this->doUnmarshal($child);

                        if ($classMetadata->isCollection($fieldName)) {
                            $collectionElements[$fieldName][] = $fieldValue;
                        } else {                            
                            $classMetadata->setFieldValue($mappedObject, $fieldName, $fieldValue);
                        }
                    } else {
                        $type = Type::getType($fieldMapping['type']);
                        // todo ensure this is a text node

                        // Check for text node of current object
                        // simplexml cast via string, 
                        $textNode = (string) $xml->$childNodeName;

                        $classMetadata->setFieldValue($mappedObject, $fieldName, $type->convertToPHPValue($textNode));
                    }
                }
            }

            if (!empty($collectionElements)) {
                foreach ($collectionElements as $fieldName => $elements) {
                    $classMetadata->setFieldValue($mappedObject, $fieldName, $elements);
                }
            }
        }

        // PostUnmarshall hook
        if ($classMetadata->hasLifecycleCallbacks(Events::postUnmarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::postUnmarshal, $mappedObject);
        }


        return $mappedObject;
    }
}
