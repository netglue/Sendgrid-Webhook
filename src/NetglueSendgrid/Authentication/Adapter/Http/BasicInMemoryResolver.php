<?php

namespace NetglueSendgrid\Authentication\Adapter\Http;

use Zend\Authentication\Adapter\Http\ResolverInterface;

class BasicInMemoryResolver implements ResolverInterface
{

    private $username;

    private $password;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Resolve username/realm to password/hash/etc.
     *
     * @param  string $username Username
     * @param  string $realm    Authentication Realm
     * @param  string $password Password (optional)
     * @return string|array|false User's shared secret as string if found in realm, or User's identity as array
     *         if resolved, false otherwise.
     */
    public function resolve($username, $realm, $password = null)
    {
        if($username === $this->username) {
            return $this->password;
        }

        return false;
    }
}
