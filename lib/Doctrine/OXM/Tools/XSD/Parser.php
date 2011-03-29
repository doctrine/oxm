<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/28/11
 * Time: 9:26 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\OXM\Tools\XSD;

class Parser
{
    public function __construct()
    {

    }

    public function parseFromUrl($path)
    {
        return $this->parseSchema(file_get_contents($path));
    }

    private function parseSchema($xsd)
    {
        print_r($xsd);
        $dom = new \DOMDocument();
        $dom->load($xsd);
        print_r($dom);


        $schema = new Schema\Schema();

        foreach ($xml->attributes() as $attribute) {
            switch ($attribute->getName()) {
                case 'id':
                    $schema->id = (string) $attribute;
                    break;
                case 'attributeFormDefault':
                    $schema->attributeFormDefault = (string) $attribute;
                    break;
                case 'elementFormDefault':
                    $schema->elementFormDefault = (string) $attribute;
                    break;
                case 'blockDefault':
                    $schema->blockDefault = (string) $attribute;
                    break;
                case 'finalDefault':
                    $schema->finalDefault = (string) $attribute;
                    break;
                case 'targetNamespace':
                    $schema->targetNamespace = (string) $attribute;
                    break;
                case 'version':
                    $schema->version = (string) $attribute;
                    break;
                case 'xmlns':
                    $schema->xmlns = (string) $attribute;
                    break;
                default:
                    break;
            }
        }

        foreach ($xml->element as $node) {
            $schema->addElement($this->parseElement($node));
        }

        foreach ($xml->children() as $node) {
            print_r($node->getName());
            switch ($node->getName()) {
                case 'include':
                    $schema->addInclude($this->parseInclude($node));
                    break;
                case 'import':
                    $schema->addImport($this->parseImport($node));
                    break;
                case 'redefine':
                    $schema->addRedefine($this->parseRedefine($node));
                    break;
                case 'annotation':
                    $schema->addAnnotation($this->parseAnnotation($node));
                    break;

                case 'simpleType':
                    $schema->addSimpleType($this->parseSimpleType($node));
                    break;
                case 'complexType':
                    $schema->addComplexType($this->parseComplexType($node));
                    break;
                case 'group':
                    $schema->addGroup($this->parseGroup($node));
                    break;
                case 'attributeGroup':
                    $schema->addAttributeGroup($this->parseAttributeGroup($node));
                    break;
                case 'element':
                    $schema->addElement($this->parseElement($node));
                    break;
                case 'attribute':
                    $schema->addAttribute($this->parseAttribute($node));
                    break;
                case 'notation':
                    $schema->addNotation($this->parseNotation($node));
                    break;
            }
        }

        return $schema;
    }
}
