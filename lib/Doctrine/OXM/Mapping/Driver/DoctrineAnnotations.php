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

namespace Doctrine\OXM\Mapping;

use Doctrine\Common\Annotations\Annotation;

/* Annotations for OXM Entities */
class XmlEntity extends Annotation {
    public $xml;        // implied
}
final class XmlRootEntity extends XmlEntity {
    public $repositoryClass;
}
final class XmlChangeTrackingPolicy extends Annotation {
    public $value;
}
final class XmlMappedSuperclass extends Annotation {}

final class XmlNamespace extends Annotation {
    public $url;
    public $prefix;
}
final class XmlNamespaces extends Annotation {}

final class XmlId extends Annotation {}
class XmlField extends Annotation {
    public $type;       // required
    public $name;       // implied (xml element name)
    public $node;       // implied (attribute, text, element)
    public $direct = true;
    public $nillable = false;
    public $required = false;
    public $collection = false;
    public $getMethod;  // implied
    public $setMethod;  // implied
    public $prefix;
    public $wrapper;
}

final class XmlAttribute extends XmlField {
    public $node = "attribute";
}
final class XmlElement extends XmlField {
    public $node = "element";
}
final class XmlText extends XmlField {
    public $node = "text";
}

final class XmlReferences extends Annotation {
    public $entityName;
}


/* Annotations for lifecycle callbacks */
final class HasLifecycleCallbacks extends Annotation {}
final class PreMarshal extends Annotation {}
final class PostMarshal extends Annotation {}
final class PreUnmarshal extends Annotation {}
final class PostUnmarshal extends Annotation {}
final class PrePersist extends Annotation {}
final class PostPersist extends Annotation {}
final class PreUpdate extends Annotation {}
final class PostUpdate extends Annotation {}
final class PreRemove extends Annotation {}
final class PostRemove extends Annotation {}
final class PreLoad extends Annotation {}
final class PostLoad extends Annotation {}

