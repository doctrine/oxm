<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/25/11
 * Time: 5:24 PM
 * To change this template use File | Settings | File Templates.
 */


namespace Doctrine\Tests\OXM\Entities;

/**
 * @XmlEntity
 */
class Address
{
    /**
     * @var string
     *
     * @XmlField(type="string")
     */
    private $street;

    /**
     * @var string
     *
     * @XmlField(type="string")
     * @XmlBinding(node="text")
     */
    private $city;

    /**
     * @var string
     *
     * @XmlField(type="string")
     */
    private $state;


    /**
     * @param  $street
     * @param  $city
     * @param  $state
     */
    public function __construct($street, $city, $state)
    {
        $this->city = $city;
        $this->street = $street;
        $this->state = $state;
    }

    public function setCity($city)
    {
        $this->city = $city;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setStreet($street)
    {
        $this->street = $street;
    }

    public function getStreet()
    {
        return $this->street;
    }


}
