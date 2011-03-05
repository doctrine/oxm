<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/26/11
 * Time: 10:40 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Entities;

use \DateTime;

/**
 * @XmlRootEntity(nsUrl="http://example.com", nsPrefix="expl")
 * @XmlChangeTrackingPolicy(value="DEFERRED_EXPLICIT")
 */
class Order
{
    /**
     * @XmlField(type="integer")
     * @XmlId
     */
    private $id;

    /**
     * @XmlField(type="string")
     */
    private $product_type;

    /**
     * @var \DateTime
     * @XmlField(type="datetime")
     */
    private $timestamp;

    public function __construct($id, $productType, DateTime $timestamp)
    {
        $this->id = $id;
        $this->product_type = $productType;
        $this->timestamp = $timestamp;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setProductType($product_type)
    {
        $this->product_type = $product_type;
    }

    public function getProductType()
    {
        return $this->product_type;
    }

    /**
     * @param DateTime $datetime
     * @return void
     */
    public function setTimestamp(\DateTime $datetime)
    {
        $this->timestamp = $datetime;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
