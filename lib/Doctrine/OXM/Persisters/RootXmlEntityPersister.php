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

class RootXmlEntityPersister
{


    public function __construct(XmlEntityManager $xem, ClassMetadata $metadata)
    {

    }
}
