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
class Schema
{
    public $id;
    public $attributeFormDefault;
    public $elementFormDefault;
    public $blockDefault;
    public $finalDefault;
    public $targetNamespace;
    public $version;
    public $xmlns;

    public $includes;
    public $imports;
    public $redefines;


    /**
     * A set of named simple and complex type definitions
     *
     * @var
     */
    private $types;

    /**
     * A set of named (top-level) attribute declarations
     *
     * @var
     */
    private $attributes;

    /**
     * A set of named (top-level) element declarations
     *
     * @var
     */
    private $elements;

    /**
     * A set of named attribute group definitions
     *
     * @var
     */
    private $attributeGroups;

    /**
     * A set of named model group definitions
     *
     * @var
     */
    private $modelGroups;

    /**
     * A set of notation declarations
     *
     * @var
     */
    private $notations;

    /**
     * A set of annotations
     *
     * @var
     */
    private $annotations;
    

    public function getTypeDefinitions()
    {

    }

    public function getAttributeDeclarations()
    {

    }

    public function getElementDeclarations()
    {

    }

    public function getAttributeGroupDefinitions()
    {

    }

    public function getModelGroupDefinitions()
    {

    }

    public function getNotationDeclarations()
    {

    }

    public function getAnnotations()
    {
        
    }
}
