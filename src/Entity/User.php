<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class User implements UserInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=500)
     * @Serializer\Exclude()
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=225, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="datetime", name="date")
     */
    private $date;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @var array
     * @ORM\Column(type="array", length=500)
     */
    protected $roles;


    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }


    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }


    public function __construct($username)
    {
        $this->isActive = true;
        $this->username = $username;
        $this->topics = new ArrayCollection();
        $this->replies = new ArrayCollection();
        $this->roles = array('ROLE_USER');
    }
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getSalt()
    {
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        $roles = $this->roles;
        return array_unique($roles);
    }


    public function eraseCredentials()
    {
    }

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Topics", mappedBy="user")
     */
    private $topics;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Replies", mappedBy="user")
     */
    private $replies;

    /**
     * @return mixed
     */
    public function getTopics() : Collection
    {
        return $this->topics;
    }

    /**
     * @param mixed $topics
     */
    public function setTopics($topics): void
    {
        $this->topics = $topics;
    }

    /**
     * @return mixed
     */
    public function getReplies() : Collection
    {
        return $this->replies;
    }

    /**
     * @param mixed $replies
     */
    public function setReplies($replies): void
    {
        $this->replies = $replies;
    }

}
