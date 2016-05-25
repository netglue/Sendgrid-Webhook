<?php

namespace NetglueSendgridTest\Authentication\Adapter\Http;

use NetglueSendgrid\Authentication\Adapter\Http\BasicInMemoryResolver as Resolver;

class BasicInMemoryResolverTest extends \PHPUnit_Framework_TestCase
{

    public function testResolve()
    {
        $username = 'test';
        $password = 'password';
        $resolver = new Resolver($username, $password);
        $this->assertFalse($resolver->resolve('foo', 'foo', 'foo'));
        $this->assertSame($password, $resolver->resolve($username, 'foo'));
    }

}
