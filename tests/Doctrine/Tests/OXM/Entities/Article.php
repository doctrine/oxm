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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\OXM\Entities;

/**
 *
 * 
 *
 * @XmlRootEntity
 */
class Article
{
    /**
     * @var integer
     *
     * @XmlAttribute(type="integer", direct=false)
     */
    private $id;

    /**
     * @var string
     *
     * @XmlText(type="string", direct=false)
     */
    private $content;

    /**
     * @var Address
     *
     * @XmlElement(type="Doctrine\Tests\OXM\Entities\User", direct=false)
     * @XmlReferences(entityName="Doctrine\Tests\OXM\Entities\User")
     */
    private $author;

    /**
     * @var string
     * @XmlText(type="string", direct=false)
     */
    private $url;

    /**
     * @param int $id
     * @return \Doctrine\Tests\OXM\Entities\Article
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $content
     * @return \Doctrine\Tests\OXM\Entities\Article
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param \Doctrine\Tests\OXM\Entities\User $author
     * @return \Doctrine\Tests\OXM\Entities\Article
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
        $author->addArticle($this);
        return $this;
    }

    /**
     * @return \Doctrine\Tests\OXM\Entities\Address
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $url
     *
     * @return \Doctrine\Tests\OXM\Entities\Address
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
 
