<?php

namespace Doctrine\Tests\Mocks;

class TypeMock extends \Doctrine\OXM\Types\Type
{
    public function getName()
    {
        return 'mock';
    }
    
    
}
