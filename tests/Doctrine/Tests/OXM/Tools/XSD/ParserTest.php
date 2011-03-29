<?php

namespace Doctrine\Tests\OXM\Tools\XSD;

use Doctrine\OXM\Tools\XSD\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Tools\XSD\Parser
     */
    protected $_parser;

    protected function setUp()
    {
        $this->_parser = new Parser();
    }

    /**
     * @test
     */
    public function itShouldParseXsdFiles()
    {
        $schema = $this->_parser->parseFromUrl(__DIR__ . "/schema/basic.xsd");

        print_r($schema);
    }
}