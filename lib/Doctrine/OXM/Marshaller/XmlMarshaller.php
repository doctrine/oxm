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

use \Doctrine\OXM\Mapping\ClassMetadataInfo,
    \Doctrine\OXM\Mapping\ClassMetadataFactory,
    \Doctrine\OXM\Mapping\MappingException,
    \Doctrine\OXM\Types\Type,
    \Doctrine\OXM\Events;
/**
 * A marshaller class which uses Xml Writer and Xml Reader php libraries.
 *
 * Requires --enable-xmlreader and --enable-xmlwriter (default in most PHP
 * installations)
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class XmlMarshaller extends AbstractMarshaller
{
    /**
     * @param string $xml
     * @return object
     */
    function unmarshal($xml)
    {

//        print_r($xml);
        $reader = new \XMLReader();
        $reader->XML($xml);


//        $reader->setParserProperty(\XMLReader::VALIDATE, true);
//        if (!$reader->isValid()) {
//            throw new MarshallerException("document is not valid");
//        }
        $reader->read();   //position at first detected element
        // TODO: Implement unmarshal() method.

        $mappedObject = $this->doUnmarshal($reader);
        $reader->close();

        return $mappedObject;
    }

    /**
     * @throws \Doctrine\OXM\Mapping\MappingException
     * @param \XMLReader $reader
     * @return object
     */
    private function doUnmarshal(\XMLReader &$reader)
    {

        $allMappedXmlNodes = $this->classMetadataFactory->getAllXmlNodes();
        $knownMappedNodes = array_keys($allMappedXmlNodes);

        if ($reader->nodeType !== \XMLReader::ELEMENT) {
            throw new MarshallerException("unknown mapping state... terrible terrible damage");
        }

        $elementName = $reader->localName;
//        print_r('start element ' . $elementName . "\n");
        
        if (!in_array($elementName, $knownMappedNodes)) {
            throw MappingException::invalidMapping($elementName);
        }
        $classMetadata = $this->classMetadataFactory->getMetadataFor($allMappedXmlNodes[$elementName]);
        $mappedObject = $classMetadata->newInstance();

        // Pre Unmarshal hook
        if ($classMetadata->hasLifecycleCallbacks(Events::preUnmarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::preUnmarshal, $mappedObject);
        }

        if($reader->hasAttributes) {
            while($reader->moveToNextAttribute()) {
                if ($classMetadata->hasXmlField($reader->name)) {

//                    print_r("attribute start node '" . $reader->name . "'");
//                    print_r("\n");
                    
                    $fieldName = $classMetadata->getFieldName($reader->name);
                    $fieldMapping = $classMetadata->getFieldMapping($fieldName);
                    $type = Type::getType($fieldMapping['type']);

                    // todo ensure this is an attribute mapping

                    if ($classMetadata->isRequired($fieldName) && $reader->value === null) {
                        throw MappingException::fieldRequired($classMetadata->name, $fieldName);
                    }
//                    print_r("attribute start value '" . $reader->value . "'");
//                    print_r("\n");

                    $classMetadata->setFieldValue($mappedObject, $fieldName, $type->convertToPHPValue($reader->value));

//                    print_r("attribute end node '" . $reader->name . "'");
//                    print_r("\n");
                }
            }
            $reader->moveToElement();
        }

        if ($reader->isEmptyElement) {
//            print_r('element is empty, skipping loop "' . $reader->name . "'\n");


            // PostUnmarshall hook
            if ($classMetadata->hasLifecycleCallbacks(Events::postUnmarshal)) {
                $classMetadata->invokeLifecycleCallbacks(Events::postUnmarshal, $mappedObject);
            }

            return $mappedObject;
        } else {
//            print_r('element is NOT empty "' . $reader->name . "' continuing...\n");

        }

        $collectionElements = array();
        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->name === $elementName) {
                // we're at the original element closing node, bug out
//                print_r('end element ' . $reader->name . "\n");
                break;
            }

            if ($reader->nodeType == \XMLReader::NONE ||
//                $reader->nodeType == \XMLReader::ELEMENT ||
                $reader->nodeType == \XMLReader::ATTRIBUTE ||
                $reader->nodeType == \XMLReader::TEXT ||
                $reader->nodeType == \XMLReader::CDATA ||
                $reader->nodeType == \XMLReader::ENTITY_REF ||
                $reader->nodeType == \XMLReader::ENTITY ||
                $reader->nodeType == \XMLReader::PI ||
                $reader->nodeType == \XMLReader::COMMENT ||
                $reader->nodeType == \XMLReader::DOC ||
                $reader->nodeType == \XMLReader::DOC_TYPE ||
                $reader->nodeType == \XMLReader::DOC_FRAGMENT ||
                $reader->nodeType == \XMLReader::NOTATION ||
                $reader->nodeType == \XMLReader::WHITESPACE ||
                $reader->nodeType == \XMLReader::SIGNIFICANT_WHITESPACE ||
                $reader->nodeType == \XMLReader::END_ELEMENT ||
                $reader->nodeType == \XMLReader::END_ENTITY ||
                $reader->nodeType == \XMLReader::XML_DECLARATION) {

                // skip insignificat element
//                print_r("skipping node type " . $reader->nodeType . "\n");
//                print_r("skipping node name " . $reader->name . "\n");
//                print_r("skipping node value " . $reader->value . "\n");
                continue;
            }


            if ($reader->nodeType !== \XMLReader::ELEMENT) {
//                print_r($reader->nodeType);
//                print_r("\n");
//                print_r("'" . $reader->name . "'");
//                print_r("\n");
//                print_r($reader->value);
//                print_r("\n");
                throw new MarshallerException("unknown mapping state... terrible terrible damage");
            }

            if ($classMetadata->hasXmlField($reader->name)) {
                $fieldName = $classMetadata->getFieldName($reader->name);

                // Check for mapped entity as child, add recursively
                $fieldMapping = $classMetadata->getFieldMapping($fieldName);
                if ($this->classMetadataFactory->hasMetadataFor($fieldMapping['type'])) {
                    // todo ensure this is an element node



                    if ($classMetadata->isCollection($fieldName)) {
                        $collectionElements[$fieldName][] = $this->doUnmarshal($reader);
                    } else {
                        $classMetadata->setFieldValue($mappedObject, $fieldName, $this->doUnmarshal($reader));
                    }
                } else {
                    $type = Type::getType($fieldMapping['type']);

//                    print_r("text start node '" . $reader->name . "'");
//                    print_r("\n");
                    $reader->read();
                    if ($reader->nodeType !== \XMLReader::TEXT) {
                        throw new MarshallerException("unknown mapping state... terrible terrible damage");
                    }
//                    print_r("text start value '" . $reader->value . "'");
//                    print_r("\n");

                    $classMetadata->setFieldValue($mappedObject, $fieldName, $type->convertToPHPValue($reader->value));
                    $reader->read();

//                    print_r("text end node '" . $reader->name . "'");
//                    print_r("\n");
                }
            }

        }

        if (!empty($collectionElements)) {
            foreach ($collectionElements as $fieldName => $elements) {
                $classMetadata->setFieldValue($mappedObject, $fieldName, $elements);
            }
        }
        
        // PostUnmarshall hook
        if ($classMetadata->hasLifecycleCallbacks(Events::postUnmarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::postUnmarshal, $mappedObject);
        }

        return $mappedObject;
    }

    /**
     * @param object $mappedObject
     * @return string
     */
    function marshal($mappedObject)
    {
        $writer = new \XmlWriter();

//        $writer->openUri('php:\\output');
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(4);

        $this->doMarshal($mappedObject, $writer);

        $writer->endDocument();
        $xml = $writer->flush();

//        print_r($xml);
        return $xml;
    }


    function doMarshal($mappedObject, \XmlWriter &$writer)
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

        if ($nsUrl !== null && $nsPrefix !== null) {
            $writer->startElementNs($nsPrefix, $classMetadata->getXmlName(), $nsUrl);
        } else {
            $writer->startElement($classMetadata->getXmlName());
        }


        $fieldMappings = $classMetadata->getFieldMappings();
        $orderedMap = array();
        if (!empty($fieldMappings)) {
            foreach ($fieldMappings as $fieldMapping) {
                $orderedMap[$fieldMapping['node']][] = $fieldMapping;
            }
        }

//        print_r($orderedMap);

        // do attributes
        if (array_key_exists(ClassMetadataInfo::XML_ATTRIBUTE, $orderedMap)) {
            foreach ($orderedMap[ClassMetadataInfo::XML_ATTRIBUTE] as $fieldMapping) {
//                print_r('processing Attributes on class ' . $className . "\n");
                $fieldName = $fieldMapping['fieldName'];

                if (!$refClass->hasProperty($fieldName)) {
                    continue;
                }
                $fieldValue = $classMetadata->getFieldValue($mappedObject, $fieldName);
                if ($classMetadata->isRequired($fieldName) && $fieldValue === null) {
                    throw MarshallerException::fieldRequired($className, $fieldName);
                }
                $fieldXmlName = $classMetadata->getFieldXmlName($fieldName);
    //            print_r($fieldXmlName . "\n");

                $fieldType = $classMetadata->getTypeOfField($fieldName);

                if ($fieldValue !== null || $classMetadata->isNillable($fieldName)) {
                    $type = Type::getType($fieldType);
//                    print_r('attribute' . "\n");
//                    print_r('attribute value ' . $type->convertToXmlValue($fieldValue) . " to name " . $fieldXmlName . "\n");
                    $writer->writeAttribute($fieldXmlName, $type->convertToXmlValue($fieldValue));
                }
            }
        }
        // do text
        if (array_key_exists(ClassMetadataInfo::XML_TEXT, $orderedMap)) {
            foreach ($orderedMap[ClassMetadataInfo::XML_TEXT] as $fieldMapping) {
//                print_r('processing TEXT on class ' . $className . "\n");
                $fieldName = $fieldMapping['fieldName'];

                if (!$refClass->hasProperty($fieldName)) {
                    continue;
                }
                $fieldValue = $classMetadata->getFieldValue($mappedObject, $fieldName);
                if ($classMetadata->isRequired($fieldName) && $fieldValue === null) {
                    throw MarshallerException::fieldRequired($className, $fieldName);
                }
                $fieldXmlName = $classMetadata->getFieldXmlName($fieldName);
    //            print_r($fieldXmlName . "\n");

                $fieldType = $classMetadata->getTypeOfField($fieldName);

                if ($fieldValue !== null || $classMetadata->isNillable($fieldName)) {
                    $type = Type::getType($fieldType);
//                    print_r('text' . "\n");
//                    print_r('text value ' . $type->convertToXmlValue($fieldValue) . " to name " . $fieldXmlName . "\n");
                    $writer->writeElement($fieldXmlName, $type->convertToXmlValue($fieldValue));
                }
            }
        }

        // do element
        if (array_key_exists(ClassMetadataInfo::XML_ELEMENT, $orderedMap)) {
            foreach ($orderedMap[ClassMetadataInfo::XML_ELEMENT] as $fieldMapping) {
//                print_r('processing ELEMENT on class ' . $className . "\n");
                $fieldName = $fieldMapping['fieldName'];

                if (!$refClass->hasProperty($fieldName)) {
                    continue;
                }
                $fieldValue = $classMetadata->getFieldValue($mappedObject, $fieldName);
                if ($classMetadata->isRequired($fieldName) && $fieldValue === null) {
                    throw MarshallerException::fieldRequired($className, $fieldName);
                }
                $fieldXmlName = $classMetadata->getFieldXmlName($fieldName);
//                print_r($fieldXmlName . "\n");
//                print_r($fieldName . "\n");


                if ($fieldValue !== null || $classMetadata->isNillable($fieldName)) {
                    $fieldType = $classMetadata->getTypeOfField($fieldName);
//                    print_r($fieldType . "\n");

                    if ($this->classMetadataFactory->hasMetadataFor($fieldType)) {
                        if ($classMetadata->isCollection($fieldName)) {
                            foreach ($fieldValue as $value) {
//                                print_r("recurse on collection ... \n");
                                $this->doMarshal($value, $writer);
                            }
                        } else {
//                            print_r("recurse on element ".$fieldName." \n");
                            $this->doMarshal($fieldValue, $writer);
//                            print_r("finish recurse on element ".$fieldName." \n");
                        }
                    }
                }
            }
        }

//        foreach ($classMetadata->getReflectionProperties() as $property) {
//            $fieldName = $property->getName();
//
//            if (!$refClass->hasProperty($fieldName)) {
//                continue;
//            }
//
//            $fieldValue = $classMetadata->getFieldValue($mappedObject, $fieldName);
//
//            if ($classMetadata->isRequired($fieldName) && $fieldValue === null) {
//                throw MarshallerException::fieldRequired($className, $fieldName);
//            }
//
//            $fieldXmlType = $classMetadata->getFieldXmlNode($fieldName);
//            $fieldXmlName = $classMetadata->getFieldXmlName($fieldName);
//            print_r($fieldXmlName . "\n");
//
//            $fieldType = $classMetadata->getTypeOfField($fieldName);
//            print_r($fieldType . "\n");
//
//            if ($fieldValue !== null || $classMetadata->isNillable($fieldName)) {
//                if (!Type::hasType($fieldType) && $fieldXmlType === ClassMetadataInfo::XML_ELEMENT) {
//                    // check for native type
//                    if ($this->classMetadataFactory->hasMetadataFor($fieldType)) {
//                        if ($classMetadata->isCollection($fieldName)) {
//                            foreach ($fieldValue as $value) {
//                                $this->doMarshal($value, $writer);
//                            }
//                        } else {
//                            $this->doMarshal($fieldValue, $writer);
//                        }
//                    }
//                } else {
//                    $type = Type::getType($fieldType);
//
//                    switch ($fieldXmlType) {
//                        case ClassMetadataInfo::XML_ATTRIBUTE:
//                            print_r('attribute' . "\n");
//                            print_r('attribute value ' . $type->convertToXmlValue($fieldValue) . " to name " . $fieldXmlName . "\n");
//                            $writer->writeAttribute($fieldXmlName, $type->convertToXmlValue($fieldValue));
//                            break;
//
//                        case ClassMetadataInfo::XML_TEXT:
//                            print_r('text' . "\n");
//                            $writer->writeElement($fieldXmlName, $type->convertToXmlValue($fieldValue));
//                            break;
//                    }
//                }
//            }
//        }

        // PostMarshal hook
        if ($classMetadata->hasLifecycleCallbacks(Events::postMarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::postMarshal, $mappedObject);
        }

        $writer->endElement();
    }
}
