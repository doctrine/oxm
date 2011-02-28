<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/25/11
 * Time: 6:10 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Entities;

/**
 * @XmlEntity
 */
class CustomerContact
{
    /**
     * @var string
     *
     * @XmlField(direct=true, type="string")
     * @XmlBinding(node="attribute", name="emailAddress")
     */
    private $emailAddress;

    /**
     * @var string
     *
     * @XmlField(direct=true, type="string")
     */
    private $phoneNumber;

    public function __construct($email)
    {
        $this->emailAddress = $email;
    }
}
