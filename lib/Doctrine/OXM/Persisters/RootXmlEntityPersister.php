<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/28/11
 * Time: 10:38 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\OXM\Persisters;

use \Doctrine\OXM\XmlEntityManager,
    \Doctrine\OXM\Mapping\ClassMetadata,
    \Doctrine\OXM\Mapping\ClassMetadataFactory;

class RootXmlEntityPersister extends AbstractPersister
{
    /**
     * @var \Doctrine\OXM\Marshaller\Marshaller
     */
    private $marshaller;

    /**
     * @var \Doctrine\OXM\XmlEntityManager
     */
    private $xem;

    private $fileExtension = 'xml';

    public function __construct(XmlEntityManager $xem, ClassMetadata $metadata)
    {
        $this->xem = $xem;
        $this->marshaller = $xem->getMarshaller();
    }

    /**
     * Inserts this xml entity into the filesystem
     *
     * @param  $xmlEntity
     * @return bool|int
     */
    public function insert($xmlEntity)
    {
        $classMetadata = $this->xem->getClassMetadata(get_class($xmlEntity));

//        print_r($classMetadata);

        // build filepath
        $basePath = $this->xem->getConfiguration()->getStoragePath();
//        print_r(__DIR__);
//        print_r($basePath);

        $filePath = $basePath . '/' . implode('/', explode("\\", $classMetadata->rootXmlEntityName));

        if (!file_exists($filePath)) {
            mkdir($filePath, 01777, true);
        }

        $identifier = $classMetadata->getIdentifierValue($xmlEntity);

        $filePath .= '/' . $identifier . "." . $this->fileExtension;

//        print_r($filePath);


        $xml = $this->marshaller->marshal($xmlEntity);
        

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
//        print_r($dom->saveXML());

        return file_put_contents($filePath, $dom->saveXML());
    }

}
