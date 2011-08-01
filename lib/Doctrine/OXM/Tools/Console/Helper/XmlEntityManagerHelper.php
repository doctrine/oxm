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

namespace Doctrine\OXM\Tools\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Doctrine\OXM\XmlEntityManager;

/**
* @author Igor Golovanov <igor.golovanov@gmail.com>
*/
class XmlEntityManagerHelper extends Helper
{
    /**
     *
     * @var XmlEntityManager
     */
    protected $xem;
    
    /**
     * @param XmlEntityManager $xem 
     */
    public function __construct(XmlEntityManager $xem)
    {
        $this->xem = $xem;
    }
    
    /**
     * @return XmlEntityManager
     */
    public function getXmlEntityManager()
    {
        return $this->xem;
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return 'xmlEntityManager';
    }
}

