<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 13/02/2019
 * Time: 11:49
 */

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Table(name="topics")
 * @ORM\Entity
 */

class Topics
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subject;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $content;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="integer")
     */
    private $views;

    /**
     * @var array
     * @ORM\Column(type="array", length=500)
     */
    private $status;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOpen;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Categories")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Replies", mappedBy="topic")
     */
    private $replies;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): void
    {
        $this->subject = $subject;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content): void
    {
        $this->content = $content;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getViews()
    {
        return $this->views;
    }

    public function setViews($views): void
    {
        $this->views = $views;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category): void
    {
        $this->category = $category;
    }

    public function getReplies() : Collection
    {
        return $this->replies;
    }

    public function setReplies($replies): void
    {
        $this->replies = $replies;
    }

    public function __construct()
    {
        $this->replies = new ArrayCollection();
        $this->views = 0;
        $this->isOpen = true;
        $this->status = array('open', 'unanswered');
    }

    public function getStatus()
    {
        $status[] = $this->status;
        return array_unique($status);
    }

    public function addStatu($statu)
    {
        $statu = strtoupper($statu);

        if (!in_array($statu, $this->status, true)) {
            $this->status[] = $statu;
        }

        return $this;
    }

    public function removeStatu($statu)
    {
        if (false !== $key = array_search(strtoupper($statu), $this->status, true)) {
            unset($this->status[$key]);
            $this->status = array_values($this->status);
        }

        return $this;
    }

    public function setStatus(array $status)
    {
        $this->status = array();

        foreach ($status as $statu) {
            $this->addStatu($statu);
        }

        return $this;
    }

    public function hasStatu($statu)
    {
        return in_array(strtoupper($statu), $this->getStatus(), true);
    }

    public function getIsOpen()
    {
        return $this->isOpen;
    }

    public function setIsOpen($isOpen): void
    {
        $this->isOpen = $isOpen;
    }

}