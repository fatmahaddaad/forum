<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

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
        $this->votes = new ArrayCollection();
        $this->comments = new ArrayCollection();
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

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Votes", mappedBy="user")
     */
    private $votes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comments", mappedBy="user")
     */
    private $comments;

    /**
     * @return mixed
     */
    public function getVotes() : Collection
    {
        return $this->votes;
    }

    /**
     * @param mixed $votes
     */
    public function setVotes($votes): void
    {
        $this->votes = $votes;
    }

    /**
     * @return mixed
     */
    public function getComments() : Collection
    {
        return $this->comments;
    }

    /**
     * @param mixed $comments
     */
    public function setComments($comments): void
    {
        $this->comments = $comments;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive($isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $image;

    /**
     * @Vich\UploadableField(mapping="profile_pictures", fileNameProperty="image")
     */
    private $imageFile;

    /**
     *
     * @ORM\Column(type="datetime", name="updatedAt", nullable=true)
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return User
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($image) {
            // if 'updatedAt' is not defined in your entity, use another property
            $this->updatedAt = new \DateTime('now');
        }
    }

    public function getImageFile()
    {
        return $this->imageFile;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }

}
