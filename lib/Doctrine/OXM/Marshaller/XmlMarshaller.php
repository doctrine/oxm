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
    
use \XMLReader, \XMLWriter;
    
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
class XmlMarshaller implements Marshaller
{

    /**
     * @var \Doctrine\OXM\Mapping\ClassMetadataFactory
     */
    protected $classMetadataFactory;

    /**
     * @param ClassMetadataFactory
     */
    public function __construct(ClassMetadataFactory $classMetadataFactory)
    {
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * @param Doctrine\OXM\Mapping\ClassMetadataFactory
     */
    public function setClassMetadataFactory(ClassMetadataFactory $classMetadataFactory)
    {
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * @return Doctrine\OXM\Mapping\ClassMetadataFactory
     */
    public function getClassMetadataFactory()
    {
        return $this->classMetadataFactory;
    }


    /**
     * @param string $path
     * @return object
     */
    public function unmarshalFromStream($path)
    {
        if (!is_file($path)) {
            throw MarshallerException::fileNotFound($path);
        }

        $reader = new XMLReader();
        $reader->open($path);

        // Position at first detected element
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                break;
            }
        }

        $mappedObject = $this->doUnmarshal($reader);
        $reader->close();

        return $mappedObject;
    }




    /**
     * @param string $xml
     * @return object
     */
    function unmarshalFromString($xml)
    {
        $reader = new XMLReader();
        $reader->XML(trim($xml));

        // Position at first detected element
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                break;
            }
        }

        $mappedObject = $this->doUnmarshal($reader);
        $reader->close();

        return $mappedObject;
    }

    /**
     *
     * INTERNAL: Performance sensitive method
     *
     * @throws \Doctrine\OXM\Mapping\MappingException
     * @param \XMLReader $cursor
     * @return object
     */
    private function doUnmarshal(XMLReader $cursor)
    {
        $allMappedXmlNodes = $this->classMetadataFactory->getAllXmlNodes();
        $knownMappedNodes = array_keys($allMappedXmlNodes);

        if ($cursor->nodeType !== XMLReader::ELEMENT) {
            throw new MarshallerException("unknown mapping state... terrible terrible damage");
        }

        $elementName = $cursor->localName;

        if (!in_array($elementName, $knownMappedNodes)) {
            throw MappingException::invalidMapping($elementName);
        }
        $classMetadata = $this->classMetadataFactory->getMetadataFor($allMappedXmlNodes[$elementName]);
        $mappedObject = $classMetadata->newInstance();

        // Pre Unmarshal hook
        if ($classMetadata->hasLifecycleCallbacks(Events::preUnmarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::preUnmarshal, $mappedObject);
        }

        if ($cursor->hasAttributes) {
            while ($cursor->moveToNextAttribute()) {
                if ($classMetadata->hasXmlField($cursor->name)) {
                    $fieldName = $classMetadata->getFieldName($cursor->name);
                    $fieldMapping = $classMetadata->getFieldMapping($fieldName);
                    $type = Type::getType($fieldMapping['type']);

                    if ($classMetadata->isRequired($fieldName) && $cursor->value === null) {
                        throw MappingException::fieldRequired($classMetadata->name, $fieldName);
                    }

                    if ($classMetadata->isCollection($fieldName)) {
                        $convertedValues = array();
                        foreach (explode(" ", $cursor->value) as $value) {
                            $convertedValues[] = $type->convertToPHPValue($value);
                        }
                        $classMetadata->setFieldValue($mappedObject, $fieldName, $convertedValues);
                    } else {
                        $classMetadata->setFieldValue($mappedObject, $fieldName, $type->convertToPHPValue($cursor->value));
                    }

                }
            }
            $cursor->moveToElement();
        }

        if (!$cursor->isEmptyElement) {
            $collectionElements = array();
            while ($cursor->read()) {
                if ($cursor->nodeType === XMLReader::END_ELEMENT && $cursor->name === $elementName) {
                    // we're at the original element closing node, bug out
                    break;
                }

                if ($cursor->nodeType == XMLReader::NONE ||
    //                $reader->nodeType == XMLReader::ELEMENT ||
                    $cursor->nodeType == XMLReader::ATTRIBUTE ||
                    $cursor->nodeType == XMLReader::TEXT ||
                    $cursor->nodeType == XMLReader::CDATA ||
                    $cursor->nodeType == XMLReader::ENTITY_REF ||
                    $cursor->nodeType == XMLReader::ENTITY ||
                    $cursor->nodeType == XMLReader::PI ||
                    $cursor->nodeType == XMLReader::COMMENT ||
                    $cursor->nodeType == XMLReader::DOC ||
                    $cursor->nodeType == XMLReader::DOC_TYPE ||
                    $cursor->nodeType == XMLReader::DOC_FRAGMENT ||
                    $cursor->nodeType == XMLReader::NOTATION ||
                    $cursor->nodeType == XMLReader::WHITESPACE ||
                    $cursor->nodeType == XMLReader::SIGNIFICANT_WHITESPACE ||
                    $cursor->nodeType == XMLReader::END_ELEMENT ||
                    $cursor->nodeType == XMLReader::END_ENTITY ||
                    $cursor->nodeType == XMLReader::XML_DECLARATION) {

                    // skip insignificant elements
                    continue;
                }


                if ($cursor->nodeType !== XMLReader::ELEMENT) {
                    throw new MarshallerException("unknown mapping state... terrible terrible damage");
                }

                if ($classMetadata->hasXmlField($cursor->localName)) {
                    $fieldName = $classMetadata->getFieldName($cursor->localName);

                    // Check for mapped entity as child, add recursively
                    $fieldMapping = $classMetadata->getFieldMapping($fieldName);

                    if ($this->classMetadataFactory->hasMetadataFor($fieldMapping['type'])) {

                        if ($classMetadata->isCollection($fieldName)) {
                            $collectionElements[$fieldName][] = $this->doUnmarshal($cursor);
                        } else {
                            $classMetadata->setFieldValue($mappedObject, $fieldName, $this->doUnmarshal($cursor));
                        }
                    } else {

                        $type = Type::getType($fieldMapping['type']);

                        $cursor->read();
                        if ($cursor->nodeType !== XMLReader::TEXT) {
                            throw new MarshallerException("unknown mapping state... terrible terrible damage");
                        }

                        if ($classMetadata->isCollection($fieldName)) {
                            $collectionElements[$fieldName][] = $type->convertToPHPValue($cursor->value);
                        } else {
                            $classMetadata->setFieldValue($mappedObject, $fieldName, $type->convertToPHPValue($cursor->value));
                        }
                        
                        $cursor->read();
                    }
                } elseif (in_array($cursor->name, $knownMappedNodes)) { // @todo - this isn't very efficient
                    $class = $this->classMetadataFactory->getMetadataFor($allMappedXmlNodes[$cursor->name]);

                    $fieldName = null;
                    foreach ($classMetadata->getFieldMappings() as $fieldMapping) {
                        if ($fieldMapping['type'] == $allMappedXmlNodes[$cursor->name]) {
                            $fieldName = $fieldMapping['fieldName'];
                        } else {
                            // Walk parent tree
                            foreach ($class->getParentClasses() as $parentClass) {
                                if ($fieldMapping['type'] == $parentClass) {
                                    $fieldName = $fieldMapping['fieldName'];
                                }
                            }
                        }
                    }

                    if ($fieldName !== null) {
                        if ($classMetadata->isCollection($fieldName)) {
                            $collectionElements[$fieldName][] = $this->doUnmarshal($cursor);
                        } else {
                            $classMetadata->setFieldValue($mappedObject, $fieldName, $this->doUnmarshal($cursor));
                        }
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

    /**
     * @param object $mappedObject
     * @return string
     */
    function marshalToString($mappedObject)
    {
        $writer = new XmlWriter();

        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(4);

        $this->doMarshal($mappedObject, $writer);

        $writer->endDocument();

        return $writer->flush();
    }


    /**
     * @param object $mappedObject
     * @param string $path
     * @return bool|int
     */
    public function marshalToStream($mappedObject, $path)
    {
        $writer = new XmlWriter();

        $writer->openUri($path);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(4);

        $this->doMarshal($mappedObject, $writer);

        $writer->endDocument();

        return $writer->flush();
    }

    /**
     *
     *
     * INTERNAL: Performance sensitive method
     *
     *
     *
     * @throws MarshallerException
     * @param  $mappedObject
     * @param \XMLWriter $writer
     * @return void
     */
    private function doMarshal($mappedObject, XmlWriter $writer)
    {
        $className = get_class($mappedObject);
        $classMetadata = $this->classMetadataFactory->getMetadataFor($className);

        if (!$this->classMetadataFactory->hasMetadataFor($className)) {
            throw new MarshallerException("A mapping does not exist for class '$className'");
        }

        // PreMarshall Hook
        if ($classMetadata->hasLifecycleCallbacks(Events::preMarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::preMarshal, $mappedObject);
        }

        $writer->startElement($classMetadata->getXmlName());

        $namespaces = $classMetadata->getXmlNamespaces();
        if (!empty($namespaces)) {
            foreach ($namespaces as $namespace) {
                if ($namespace['prefix'] !== null) {
                    $writer->writeAttribute('xmlns:' . $namespace['prefix'], $namespace['url']);
                } else {
                    $writer->writeAttribute('xmlns', $namespace['url']);
                }
            }
        }


        $fieldMappings = $classMetadata->getFieldMappings();
        $orderedMap = array();
        if (!empty($fieldMappings)) {
            foreach ($fieldMappings as $fieldMapping) {
                $orderedMap[$fieldMapping['node']][] = $fieldMapping;
            }
        }

        // do attributes
        if (array_key_exists(ClassMetadataInfo::XML_ATTRIBUTE, $orderedMap)) {
            foreach ($orderedMap[ClassMetadataInfo::XML_ATTRIBUTE] as $fieldMapping) {

                $fieldName = $fieldMapping['fieldName'];
                $fieldValue = $classMetadata->getFieldValue($mappedObject, $fieldName);

                if ($classMetadata->isRequired($fieldName) && $fieldValue === null) {
                    throw MarshallerException::fieldRequired($className, $fieldName);
                }
                
                if ($fieldValue !== null || $classMetadata->isNillable($fieldName)) {
                    $fieldXmlName = $classMetadata->getFieldXmlName($fieldName);
                    $fieldType = $classMetadata->getTypeOfField($fieldName);

                    if ($classMetadata->isCollection($fieldName)) {
                        $convertedValues = array();
                        foreach ($fieldValue as $value) {
                            $convertedValues[] = Type::getType($fieldType)->convertToXmlValue($value);
                        }

                        if (isset($fieldMapping['prefix'])) {
                            $writer->writeAttributeNs($fieldMapping['prefix'], $fieldXmlName, null, implode(" ", $convertedValues));
                        } else {
                            $writer->writeAttribute($fieldXmlName, implode(" ", $convertedValues));
                        }
                    } else {
                        if (isset($fieldMapping['prefix'])) {
                            $writer->writeAttributeNs($fieldMapping['prefix'], $fieldXmlName, null, Type::getType($fieldType)->convertToXmlValue($fieldValue));
                        } else {
                            $writer->writeAttribute($fieldXmlName, Type::getType($fieldType)->convertToXmlValue($fieldValue));
                        }
                    }                    
                }
            }
        }

        // do text
        if (array_key_exists(ClassMetadataInfo::XML_TEXT, $orderedMap)) {
            foreach ($orderedMap[ClassMetadataInfo::XML_TEXT] as $fieldMapping) {

                $fieldName = $fieldMapping['fieldName'];
                $fieldValue = $classMetadata->getFieldValue($mappedObject, $fieldName);

                if ($classMetadata->isRequired($fieldName) && $fieldValue === null) {
                    throw MarshallerException::fieldRequired($className, $fieldName);
                }

                if ($fieldValue !== null || $classMetadata->isNillable($fieldName)) {
                    $fieldXmlName = $classMetadata->getFieldXmlName($fieldName);
                    $fieldType = $classMetadata->getTypeOfField($fieldName);

                    if (isset($fieldMapping['prefix'])) {
                        if ($classMetadata->isCollection($fieldName)) {
                            foreach ($fieldValue as $value) {
                                $writer->writeElementNs($fieldMapping['prefix'], $fieldXmlName, null, Type::getType($fieldType)->convertToXmlValue($value));
                            }
                        } else {
                            $writer->writeElementNs($fieldMapping['prefix'], $fieldXmlName, null, Type::getType($fieldType)->convertToXmlValue($fieldValue));
                        }
                    } else {
                        if ($classMetadata->isCollection($fieldName)) {
                            foreach ($fieldValue as $value) {
                                $writer->writeElement($fieldXmlName, Type::getType($fieldType)->convertToXmlValue($value));
                            }
                        } else {
                            $writer->writeElement($fieldXmlName, Type::getType($fieldType)->convertToXmlValue($fieldValue));
                        }
                    }
                }
            }
        }

        // do elements
        if (array_key_exists(ClassMetadataInfo::XML_ELEMENT, $orderedMap)) {
            foreach ($orderedMap[ClassMetadataInfo::XML_ELEMENT] as $fieldMapping) {

                $fieldName = $fieldMapping['fieldName'];
                $fieldValue = $classMetadata->getFieldValue($mappedObject, $fieldName);

                if ($classMetadata->isRequired($fieldName) && $fieldValue === null) {
                    throw MarshallerException::fieldRequired($className, $fieldName);
                }

                if ($fieldValue !== null || $classMetadata->isNillable($fieldName)) {
                    $fieldType = $classMetadata->getTypeOfField($fieldName);

                    if ($this->classMetadataFactory->hasMetadataFor($fieldType)) {
                        if ($classMetadata->isCollection($fieldName)) {
                            foreach ($fieldValue as $value) {
                                $this->doMarshal($value, $writer);
                            }
                        } else {
                            $this->doMarshal($fieldValue, $writer);
                        }
                    }
                }
            }
        }

        // PostMarshal hook
        if ($classMetadata->hasLifecycleCallbacks(Events::postMarshal)) {
            $classMetadata->invokeLifecycleCallbacks(Events::postMarshal, $mappedObject);
        }

        $writer->endElement();
    }
}
