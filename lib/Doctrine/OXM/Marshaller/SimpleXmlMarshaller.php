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
    \Doctrine\OXM\Mapping\Mapping,
    \Doctrine\OXM\Mapping\MappingFactory,
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
class SimpleXmlMarshaller implements Marshaller
{
    /**
     *
     */
    public function marshal(MappingFactory $mappingFactory, $mappedObject)
    {
        // Since the SimpleXML API is silly, we'll wrap the object in a parent "dummy"
        // element to make adding children more happy with recursion
        $dummyParent = new \SimpleXMLElement('<dummy-parent/>');
        $this->marshalImpl($mappingFactory, $mappedObject, $dummyParent);


        // first child should be our mapped object
        $children = $dummyParent->children();

        // TODO - don't hardcode the encoding
        return '<?xml version="1.0" encoding="UTF-8"?>' . $children[0]->asXML();
    }

    /**
     * @param Mapping $mapping
     * @param SimpleXMLElement $parent
     * @return void
     */
    private function marshalImpl(MappingFactory $mappingFactory, $mappedObject, \SimpleXMLElement &$parent)
    {
        $className = get_class($mappedObject);
        $classMapping = $mappingFactory->getMappingForClass($className);
//        print_r($classMapping);
        
        if (!$mappingFactory->hasMappingForClass($className)) {
            throw new MarshallerException("A mapping does not exist for class '$className'");
        }

        // PreMarshall Hook
        if ($classMapping->hasLifecycleCallbacks(Events::preMarshal)) {
            $classMapping->invokeLifecycleCallbacks(Events::preMarshal, $mappedObject);
        }


        $refClass = new \ReflectionClass($mappedObject);
        
        // Add to the parent element
        $xml = $parent->addChild($classMapping->getXmlName());
        
        foreach ($classMapping->getReflectionProperties() as $property) {
            $fieldName = $property->getName();
            
            if (!$refClass->hasProperty($fieldName)) {
                continue;
            }
                        
            $fieldValue = $classMapping->getFieldValue($mappedObject, $fieldName);

            if ($classMapping->isFieldRequired($fieldName) && $fieldValue == null) {
                throw MarshallerException::fieldRequired($className, $fieldName);
            }

            $fieldXmlType = $classMapping->getFieldXmlNode($fieldName);
            $fieldXmlName = $classMapping->getFieldXmlName($fieldName);

            $fieldType = $classMapping->getTypeOfField($fieldName);
//            print_r($fieldType);
            if (!Type::hasType($fieldType) && $fieldXmlType === Mapping::XML_ELEMENT) {
                // check for native type
                if ($mappingFactory->hasMappingForClass($fieldType)) {
                    if ($classMapping->isFieldCollection($fieldName) && is_array($fieldValue)) {
                        foreach ($fieldValue as $value) {
                            $this->marshalImpl($mappingFactory, $value, $xml);
                        }
                    } else {
                        $this->marshalImpl($mappingFactory, $fieldValue, $xml);
                    }
                }
            } else {
                $type = Type::getType($fieldType);

                if ($fieldValue != null || $classMapping->isFieldNillable($fieldName)) {
                    switch ($fieldXmlType) {
                        case Mapping::XML_ATTRIBUTE:
                            $xml->addAttribute($fieldXmlName, $type->convertToXmlValue($fieldValue));
                            break;

                        case Mapping::XML_TEXT:
                            $xml->addChild($fieldXmlName, $type->convertToXmlValue($fieldValue));
                            break;
                    }
                }
            }
        }

        // PostMarshal hook
        if ($classMapping->hasLifecycleCallbacks(Events::postMarshal)) {
            $classMapping->invokeLifecycleCallbacks(Events::postMarshal, $mappedObject);
        }
    }

    /**
     * @param string $xml
     * @return object
     */
    public function unmarshal(MappingFactory $mappingFactory, $xml)
    {
        return $this->unmarshalImpl($mappingFactory, new \SimpleXMLElement($xml));
    }

    /**
     * @throws \Doctrine\OXM\Mapping\MappingException
     * @param \Doctrine\OXM\Mapping\MappingFactory $mappingFactory
     * @param \SimpleXmlElement $xml
     * @return object
     */
    private function unmarshalImpl(MappingFactory $mappingFactory, \SimpleXMLElement $xml)
    {

        $elementName = $xml->getName();
        $allMappedXmlNodes = $mappingFactory->getAllXmlNodes();
        $knownMappedNodes = array_keys($allMappedXmlNodes);


        if (!in_array($elementName, $knownMappedNodes)) {
            throw MappingException::invalidMapping($elementName);
        }


        $mapping = $mappingFactory->getMappingForClass($allMappedXmlNodes[$elementName]);
//        print_r($mapping);

        $mappedObject = $mapping->newInstance();

        // Pre Unmarshal hook
        if ($mapping->hasLifecycleCallbacks(Events::preUnmarshal)) {
            $mapping->invokeLifecycleCallbacks(Events::preUnmarshal, $mappedObject);
        }

        // Handle attributes first
        $attributes = $xml->attributes();
        foreach ($attributes as $attributeKey => $attributeValue) {
            if ($mapping->hasXmlField($attributeKey)) {
                $fieldName = $mapping->getFieldName($attributeKey);
                $fieldMapping = $mapping->getFieldMapping($fieldName);
                $type = Type::getType($fieldMapping['type']);

                // todo ensure this is an attribute mapping

                if ($mapping->isFieldRequired($fieldName) && $attributeValue === null) {
                    throw MappingException::fieldRequired($mapping->className, $fieldName);
                }

                // simplexml cast to string for value, TODO - should type convert result
                $fieldValue = (string) $attributeValue;

                $mapping->setFieldValue($mappedObject, $fieldName, $type->convertToPHPValue($fieldValue));
            }
        }

        // Handle children
        $children = $xml->children();


        if (count($children) > 0) {
            $collectionElements = array();

            foreach ($children as $child) {
                $childNodeName = $child->getName();
//                print_r($childNodeName);
//                print_r("\n");
//
//                print_r($mapping);
//                print_r("\n");
                if ($mapping->hasXmlField($childNodeName)) {

                    $fieldName = $mapping->getFieldName($childNodeName);



                    // todo - check for collection
                    // todo - add support for collection wrapper element

                    // Check for mapped entity as child, add recursively
                    $fieldMapping = $mapping->getFieldMapping($fieldName);
                    if ($mappingFactory->hasMappingForClass($fieldMapping['type'])) {



                        // todo ensure this is an element node


                        $fieldValue = $this->unmarshalImpl($mappingFactory, $child);

                        if ($mapping->isFieldCollection($fieldName)) {
                            $collectionElements[$fieldName][] = $fieldValue;
                        } else {                            
                            $mapping->setFieldValue($mappedObject, $fieldName, $fieldValue);
                        }

                    } else {
                        $type = Type::getType($fieldMapping['type']);

                        // todo ensure this is a text node

                        // Check for text node of current object
                        // simplexml cast via string, 
                        $textNode = (string) $xml->$childNodeName;

                        $mapping->setFieldValue($mappedObject, $fieldName, $type->convertToPHPValue($textNode));

                    }
                }
            }

            if (!empty($collectionElements)) {
                foreach ($collectionElements as $fieldName => $elements) {
                    $mapping->setFieldValue($mappedObject, $fieldName, $elements);
                }
            }


        }

        // PostUnmarshall hook
        if ($mapping->hasLifecycleCallbacks(Events::postUnmarshal)) {
            $mapping->invokeLifecycleCallbacks(Events::postUnmarshal, $mappedObject);
        }


        return $mappedObject;
    }
}
