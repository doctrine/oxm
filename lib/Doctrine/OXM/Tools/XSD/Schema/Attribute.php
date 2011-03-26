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

namespace Doctrine\OXM\Tools\XSD\Schema;

/**
 *
 */
class Attribute
{
    // Constants
    const FORM_QUALIFIED   = 'qualified';
    const FORM_UNQUALIFIED = 'unqualified';

    const USE_OPTIONAL   = 'optional';
    const USE_PROHIBITED = 'prohibited';
    const USE_REQUIRED   = 'required';

    public $parent;

    public $default;
    public $fixed;
    public $form;
    public $id;
    public $ref;
    public $type;
    public $use = self::USE_OPTIONAL;
    public $name;


    /**
     * Optional.  An annotation
     *
     * @var
     */
    public $annotation;

    /**
     * A simple type definition
     *
     * @var
     */
    public $simpleType;



    public function getName()
    {
        return $this->name;
    }

    public function getTargetNamespace()
    {
        $parentTargetNamespace = $this->parent->getTargetNamespace();
        return ($parentTargetNamespace !== null) ? $parentTargetNamespace : null;
    }

    public function getTypeDefinition()
    {
        if ($this->simpleType !== null) {
            return $this->simpleType;
        } else {
            return $this->type; // todo - make from class?
        }
    }

    public function getScope()
    {
        return 'global'; // todo magic string
    }

    public function getValueConstraint()
    {
        if ($this->default !== null || $this->fixed !== null) {
             // todo - make getValue(), implement default|fixed as appropriate
            return array($this->getTypeDefinition()->getValue(), 'default|fixed');
        }
        
        return null;
    }

    public function getAnnotation()
    {
        if (isset($this->annotation)) {
            return $this->annotation;
        }

        return null;
    }

    public function getRequired()
    {
        return $this->use === self::USE_REQUIRED;
    }
}
