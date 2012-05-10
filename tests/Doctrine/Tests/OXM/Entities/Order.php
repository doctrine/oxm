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

use \DateTime;

/**
 * @XmlRootEntity
 * @XmlNamespace(url="http://example.com", prefix="expl")
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
