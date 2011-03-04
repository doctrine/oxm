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
    \Doctrine\OXM\Mapping\ClassMetadata;

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

    /**
     * @var \Doctrine\OXM\Storage\XmlStorage
     */
    private $storage;

    /**
     * @param \Doctrine\OXM\XmlEntityManager $xem
     * @param \Doctrine\OXM\Mapping\ClassMetadata
     */
    public function __construct(XmlEntityManager $xem, ClassMetadata $metadata)
    {
        $this->xem = $xem;
        $this->marshaller = $xem->getMarshaller();
        $this->storage = $xem->getStorage();
    }

    /**
     * Inserts this xml entity into the storage system
     *
     * @param  $xmlEntity
     * @return bool|int
     */
    public function insert($xmlEntity)
    {
        $classMetadata = $this->xem->getClassMetadata(get_class($xmlEntity));
        $identifier = $classMetadata->getIdentifierValue($xmlEntity);

        $xml = $this->marshaller->marshal($xmlEntity);

        // this should probably be a marshaller option... formatOutput = true
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);

        return $this->storage->insert($classMetadata, $identifier, $dom->saveXML());
    }

    /**
     * @param object $xmlEntity
     * @return boolean
     */
    public function exists($xmlEntity)
    {
        $classMetadata = $this->xem->getClassMetadata(get_class($xmlEntity));
        return $this->storage->exists($classMetadata, $classMetadata->getIdentifierValue($xmlEntity));
    }

}
