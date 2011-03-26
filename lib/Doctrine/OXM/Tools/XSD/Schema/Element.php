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
class Element
{
    const BLOCK_EXTENSION     = 'extension';
    const BLOCK_RESTRICTION   = 'restriction';
    const BLOCK_SUBSTITUTION  = 'substitution';

    const FINAL_EXTENSION     = 'extension';
    const FINAL_RESTRICTION   = 'restriction';

    const MAXOCCURS_UNBOUNDED = 'unbounded';

    public $parent;

    public $abstract = false;
    public $block;
    public $default;
    public $final;
    public $fixed;
    public $id;
    public $maxOccurs = 1;
    public $minOccurs = 1;
    public $name;
    public $nillable;
    public $ref;
    public $substitutionGroup;
    public $type;

    public $annotation;
    
    public $simpleType;
    public $complexType;

    public $uniques;
    public $keys;
    public $keyrefs;

    public function getName()
    {

    }

    public function getTargetNamespace()
    {

    }

    public function getScope()
    {

    }

    public function getTypeDefinition()
    {

    }

    public function getNillable()
    {

    }

    public function getValueConstraint()
    {

    }

    public function getIdentityConstraintDefinitions()
    {

    }

    public function getSubstitutionGroupAffiliation()
    {

    }

    public function getSubstitutionGroupExclusions()
    {

    }

    public function getDisallowedSubstitutions()
    {
        
    }

    public function getAbstract()
    {

    }
    
    public function getAnnotation()
    {

    }
}
