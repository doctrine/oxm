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
 * Type that maps an XML DATETIME/TIMESTAMP to a PHP DateTime object.
 *
 * @since 2.0
 */
class DateTimeType extends Type
{
    const DEFAULT_FORMAT = "Y-m-d G:i:s";

    /**
     * @return string
     */
    public function getName()
    {
        return Type::DATETIME;
    }

    /**
     * @param \DateTime $value
     * @param array     $parameters
     * @return string
     */
    public function convertToXmlValue($value, array $parameters = array())
    {
        $format = array_key_exists('format', $parameters) ? $parameters['format'] : static::DEFAULT_FORMAT;

        return !is_null($value) ? $value->format($format) : null;
    }

    /**
     * @param string $value
     * @param array  $parameters
     * @return \DateTime
     */
    public function convertToPHPValue($value, array $parameters = array())
    {
        if (is_null($value)) {
            return null;
        }

        try {
            $format = array_key_exists('format', $parameters) ? $parameters['format'] : static::DEFAULT_FORMAT;
            $val = \DateTime::createFromFormat($format, $value);

            if ($val) {
                return $val;
            } else {
                return new \DateTime($value);
            }
        } catch (\Exception $exception) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }
}
