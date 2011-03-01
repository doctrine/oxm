<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/27/11
 * Time: 2:39 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Mapping;

use Doctrine\OXM\Mapping\ClassMetadataInfo;

class MappingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\OXM\Mapping\Mapping
     */
    private $mapping;

    public function setUp()
    {
        $this->mapping = new ClassMetadataInfo('Doctrine\Tests\OXM\Mapping\User');
    }


    public function testMappingInitialization()
    {
//        print_r($this->mapping);
        $this->assertEquals('User', $this->mapping->getReflectionClass()->getShortName());
    }
}



class User
{
    private $id;
}


