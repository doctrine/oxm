<?php
/**
 * Created by JetBrains PhpStorm.
 * User: richardfullmer
 * Date: 2/23/11
 * Time: 5:56 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Doctrine\Tests\OXM\Entities;

/**
 *
 * 
 *
 * @XmlRootEntity(xml="User")
 * @HasLifecycleCallbacks
 */
class User
{
    /**
     * @var integer
     *
     * @XmlAttribute(type="integer", direct=true)
     */
    private $id;

    /**
     * @var string
     *
     * @XmlText(type="string")
     */
    private $firstNameNickname;


    /**
     * @var string
     *
     * @XmlAttribute(type="string", name="LastName")
     */
    private $lastName;

    /**
     * @var Address
     *
     * @XmlElement(type="Doctrine\Tests\OXM\Entities\Address")
     */
    private $address;


    /**
     * @var CustomerContact[]
     *
     * @XmlCollection(type="Doctrine\Tests\OXM\Entities\CustomerContact", direct=true, name="customer-contact")
     */
    private $contacts;

    /**
     * @return void
     * @PreMarshal
     */
    public function validate()
    {
        $this->firstNameNickname = '-' . $this->firstNameNickname;
    }

    /**
     * @return void
     * @PostUnmarshal
     */
    public function ensure()
    {
        $this->firstNameNickname = substr($this->firstNameNickname, 1);
    }


    public function setFirstNameNickname($first_name)
    {
        $this->firstNameNickname = $first_name;
    }

    public function getFirstNameNickName()
    {
        return $this->firstNameNickname;
    }

    public function setLastName($last_name)
    {
        $this->lastName = $last_name;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function addContact(CustomerContact $contact)
    {
        $this->contacts[] = $contact;
    }

//    public function setContacts(array $contacts)
//    {
//        foreach ($contacts as $contact) {
//            $this->addContact($contact);
//        }
//    }
//
    public function getContacts()
    {
        return $this->contacts;
    }

}
 
