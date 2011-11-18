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

namespace Doctrine\OXM\Marshaller\Helper;

use XmlWriter;
use Doctrine\OXM\Marshaller\XmlMarshaller;

/**
 * Responsible for writing xml with an XmlWriter and ClassMetadata information.
 *
 * This class helps simplify some of the logic behind writing xml with the
 * eccentricities of the XmlWriter interface.  It's calls inherit the same
 * stateful considerations that are found while working directly with
 * XmlWriter.
 */
class WriterHelper
{
    /**
     * @var \Doctrine\OXM\Marshaller\XmlMarshaller
     */
    protected $marshaller;

    /**
     * @var \XmlWriter
     */
    protected $cursor;

    /**
     * The stream to work with.  Null assumes in-memory processing.
     *
     * @var string|null
     */
    protected $streamUri = null;

    public function __construct(XmlMarshaller $marshaller, $streamUri = null)
    {
        $this->marshaller = $marshaller;
        $this->streamUri = $streamUri;
        $this->cursor = new XmlWriter();

        $this->initialize();
    }

    private function initialize()
    {
        if ($this->streamUri !== null) {
            $this->cursor->openUri($this->streamUri);
        } else {
            $this->cursor->openMemory();
        }

        $this->cursor->startDocument($this->marshaller->getSchemaVersion(), $this->marshaller->getEncoding());

        if ($this->marshaller->getIndent() > 0) {
            $this->cursor->setIndent((int) $this->marshaller->getIndent());
        }
    }

    public function startElement($name, $prefix = null, $url = null)
    {
        if ($prefix !== null) {
            $this->cursor->startElementNs($prefix, $name, $url);
        } else {
            $this->cursor->startElement($name);
        }
    }

    public function writeElement($name, $value, $prefix = null, $url = null)
    {
        if ($prefix !== null) {
            $this->cursor->writeElementNs($prefix, $name, $url, $value);
        } else {
            $this->cursor->writeElement($name, $value);
        }
    }

    public function writeNamespace($url, $prefix = null)
    {
        $attributeName = 'xmlns';
        if ($prefix !== null) {
            $attributeName .= ":$prefix";
        }
        
        $this->cursor->writeAttribute($attributeName, $url);
    }

    public function writeAttribute($name, $value, $prefix = null, $url = null)
    {
        if ($prefix !== null) {
            $this->cursor->writeAttributeNs($prefix, $name, $url, $value);
        } else {
            $this->cursor->writeAttribute($name, $value);
        }
    }

    public function endElement()
    {
        $this->cursor->endElement();
    }

    public function flush()
    {
        $this->cursor->endDocument();
        
        return $this->cursor->flush();
    }
}
