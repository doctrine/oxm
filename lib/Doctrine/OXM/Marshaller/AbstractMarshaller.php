<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 3/1/11
 * Time: 12:45 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\OXM\Marshaller;

use \SimpleXmlElement,
    \Doctrine\Common\Util\Debug,
    \Doctrine\OXM\Mapping\ClassMetadataInfo,
    \Doctrine\OXM\Mapping\ClassMetadataFactory,
    \Doctrine\OXM\Mapping\MappingException,
    \Doctrine\OXM\Types\Type,
    \Doctrine\OXM\Events;

abstract class AbstractMarshaller implements Marshaller
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
     * @param string $filepath
     * @return object
     */
    function unmarshalFromFile($filepath)
    {
        if (!is_file($filepath)) {
            throw MarshallerException::fileNotFound($filepath);
        }
        $xml = file_get_contents($filepath);
        return $this->unmarshal($xml);
    }



    /**
     * @param object $mappedObject
     * @param string $filepath
     * @return bool|int
     */
    function marshalToFile($mappedObject, $filepath)
    {
        $xml = $this->marshal($mappedObject);

        return file_put_contents($filepath, $xml);
    }
}
