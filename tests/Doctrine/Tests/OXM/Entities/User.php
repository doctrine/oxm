<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
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
     * @XmlElement(type="Doctrine\Tests\OXM\Entities\CustomerContact", collection=true, direct=true, name="customer-contact")
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
 
