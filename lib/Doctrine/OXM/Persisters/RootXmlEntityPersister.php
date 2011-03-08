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

class RootXmlEntityPersister
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
     * @var \Doctrine\OXM\Storage\Storage
     */
    private $storage;

    /**
     * @var \Doctrine\OXM\Mapping\ClassMetadata
     */
    private $metadata;

    /**
     * @param \Doctrine\OXM\XmlEntityManager $xem
     * @param \Doctrine\OXM\Mapping\ClassMetadataInfo
     */
    public function __construct(XmlEntityManager $xem, ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
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
        $identifier = $this->metadata->getIdentifierValue($xmlEntity);

        $xml = $this->marshaller->marshal($xmlEntity);
        
        return $this->storage->insert($this->metadata, $identifier, $xml);
    }

    /**
     * @param object $xmlEntity
     * @return boolean
     */
    public function exists($xmlEntity)
    {
        return $this->storage->exists($this->metadata, $this->metadata->getIdentifierValue($xmlEntity));
    }


    public function load($id)
    {
        $xml = $this->storage->load($this->metadata, $id);

        return $this->marshaller->unmarshal($xml);
    }
}
