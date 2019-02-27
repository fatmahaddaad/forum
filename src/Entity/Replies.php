<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 13/02/2019
 * Time: 11:50
 */

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
/**
 * @ORM\Table(name="replies")
 * @ORM\Entity
 */

class Replies
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isCorrect;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Topics")
     * @ORM\JoinColumn(name="topic_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $topic;


    public function getId()
    {
        return $this->id;
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

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function getTopic()
    {
        return $this->topic;
    }

    public function setTopic($topic): void
    {
        $this->topic = $topic;
    }

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Votes", mappedBy="reply")
     */
    private $votes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comments", mappedBy="reply")
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

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->isCorrect = false;
    }

    public function getIsCorrect()
    {
        return $this->isCorrect;
    }

    public function setIsCorrect($isCorrect): void
    {
        $this->isCorrect = $isCorrect;
    }

}