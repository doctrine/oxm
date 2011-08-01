<?php

namespace Doctrine\OXM\Tests\Tools;

use Doctrine\OXM\Tools\SchemaTool,
    Doctrine\OXM\Tools\XmlEntityGenerator,
    Doctrine\OXM\Mapping\ClassMetadataInfo,
    Doctrine\Tests\OxmTestCase;

class XmlEntityGeneratorTest extends OxmTestCase
{
    private $generator;
    private $tmpDir;
    private $namespace;

    public function setUp()
    {
        $this->namespace = uniqid("doctrine_");
        $this->tmpDir = \sys_get_temp_dir();
        \mkdir($this->tmpDir . \DIRECTORY_SEPARATOR . $this->namespace);
        $this->generator = new XmlEntityGenerator();
        $this->generator->setGenerateAnnotations(true);
        $this->generator->setGenerateStubMethods(true);
        $this->generator->setRegenerateXmlEntityIfExists(false);
        $this->generator->setUpdateXmlEntityIfExists(true);
    }

    public function tearDown()
    {
        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->tmpDir . '/' . $this->namespace));
        foreach ($ri AS $file) {
            /* @var $file \SplFileInfo */
            if ($file->isFile()) {
                \unlink($file->getPathname());
            }
        }
        rmdir($this->tmpDir . '/' . $this->namespace);
    }

    public function generateBookXmlEntityFixture()
    {
        $metadata = new ClassMetadataInfo($this->namespace . '\XmlEntityGeneratorBook');

        $metadata->customRepositoryClassName = $this->namespace  . '\XmlEntityGeneratorBookRepository';
        $metadata->isRoot = true;
        $metadata->mapField(array('fieldName' => 'name', 'type' => 'string'));
        $metadata->mapField(array('fieldName' => 'status', 'type' => 'string'));
        $metadata->mapField(array('fieldName' => 'id', 'type' => 'integer', 'id' => true));       
        $metadata->addLifecycleCallback('loading', 'postLoad');
        $metadata->addLifecycleCallback('willBeRemoved', 'preRemove');
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);

        $this->generator->writeXmlEntityClass($metadata, $this->tmpDir);

        return $metadata;
    }

    /**
     * @param  ClassMetadataInfo $metadata
     * @return XmlEntityGeneratorBook
     */
    public function newInstance($metadata)
    {
        $path = $this->tmpDir . '/'. $this->namespace . '/XmlEntityGeneratorBook.php'; 
        $this->assertFileExists($path);
        require_once $path;

        return new $metadata->name;
    }

    public function testGeneratedXmlEntityClass()
    {
        $metadata = $this->generateBookXmlEntityFixture();

        $book = $this->newInstance($metadata);

        $this->assertTrue(class_exists($metadata->name), "Class does not exist.");
        //$this->assertTrue(method_exists($this->namespace . '\XmlEntityGeneratorBook', '__construct'), "XmlEntityGeneratorBook::__construct() missing.");
        $this->assertTrue(method_exists($this->namespace . '\XmlEntityGeneratorBook', 'getId'), "XmlEntityGeneratorBook::getId() missing.");
        $this->assertTrue(method_exists($this->namespace . '\XmlEntityGeneratorBook', 'setName'), "XmlEntityGeneratorBook::setName() missing.");
        $this->assertTrue(method_exists($this->namespace . '\XmlEntityGeneratorBook', 'getName'), "XmlEntityGeneratorBook::getName() missing.");

        $book->setName('Jonathan H. Wage');
        $this->assertEquals('Jonathan H. Wage', $book->getName());

    }

    public function testXmlEntityUpdatingWorks()
    {
        $metadata = $this->generateBookXmlEntityFixture();
        $metadata->mapField(array('fieldName' => 'test', 'type' => 'string'));
        
        $this->generator->writeXmlEntityClass($metadata, $this->tmpDir);

        $this->assertFileExists($this->tmpDir . "/" . $this->namespace . "/~XmlEntityGeneratorBook.php");

        $book = $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasProperty('name'), "Regenerating keeps property 'name'.");
        $this->assertTrue($reflClass->hasProperty('status'), "Regenerating keeps property 'status'.");
        $this->assertTrue($reflClass->hasProperty('id'), "Regenerating keeps property 'id'.");

        $this->assertTrue($reflClass->hasProperty('test'), "Check for property test failed.");
        $this->assertTrue($reflClass->getProperty('test')->isPrivate(), "Check for private property test failed.");
        $this->assertTrue($reflClass->hasMethod('getTest'), "Check for method 'getTest' failed.");
        $this->assertTrue($reflClass->getMethod('getTest')->isPublic(), "Check for public visibility of method 'getTest' failed.");
        $this->assertTrue($reflClass->hasMethod('setTest'), "Check for method 'setTest' failed.");
        $this->assertTrue($reflClass->getMethod('setTest')->isPublic(), "Check for public visibility of method 'setTest' failed.");
    }

    public function testXmlEntityExtendsStdClass()
    {
        $this->generator->setClassToExtend('stdClass');
        $metadata = $this->generateBookXmlEntityFixture();

        $book = $this->newInstance($metadata);
        $this->assertInstanceOf('stdClass', $book);
    }

    public function testLifecycleCallbacks()
    {
        $metadata = $this->generateBookXmlEntityFixture();

        $book = $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasMethod('loading'), "Check for postLoad lifecycle callback.");
        $this->assertTrue($reflClass->hasMethod('willBeRemoved'), "Check for preRemove lifecycle callback.");
    }

    public function testLoadMetadata()
    {
        $metadata = $this->generateBookXmlEntityFixture();

        $book = $this->newInstance($metadata);

        $cm = new \Doctrine\OXM\Mapping\ClassMetadataInfo($metadata->name);
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $driver = new \Doctrine\OXM\Mapping\Driver\AnnotationDriver($reader);
        $driver->loadMetadataForClass($cm->name, $cm);
        

        //$this->assertEquals($cm->getCollection(), $metadata->getCollection());
        $this->assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        $this->assertEquals($cm->identifier, $metadata->identifier);
        $this->assertEquals($cm->idGenerator, $metadata->idGenerator);
        $this->assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);
    }
}

class XmlEntityGeneratorAuthor {}
class XmlEntityGeneratorComment {}