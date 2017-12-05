<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 */
class User implements UserInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $roles;

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles = ['ROLE_USER'])
    {
        $this->roles = $roles;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getSalt(): string
    {
        return '';
    }

    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
        $this->password = '';
    }
}
