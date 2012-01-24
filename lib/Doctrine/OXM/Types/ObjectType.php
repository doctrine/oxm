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

namespace Doctrine\OXM\Types;

/**
 * Type that maps a PHP object to a XML type via serialization.
 *
 * @since 2.0
 */
class ObjectType extends Type
{
    public function convertToXmlValue($value, array $options = array())
    {
        return serialize($value);
    }

    public function convertToPHPValue($value, array $options = array())
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;
        $val = unserialize($value);
        if ($val === false && $value !== 'b:0;') {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
        return $val;
    }

    public function getName()
    {
        return Type::OBJECT;
    }
}
