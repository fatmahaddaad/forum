<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 13/02/2019
 * Time: 12:47
 */

namespace App\Controller;

use FOS\RestBundle\View\View;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Topics;
use App\Entity\Categories;

class TopicsController extends AbstractController
{
    /**
     * add new topic
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function addTopic(Request $request)
    {
        $topic = new Topics();
        $subject = $request->get('subject');
        $content = $request->get('content');
        $date = new \DateTime();
        $category = $this->getDoctrine()->getRepository('App\Entity\Categories')->find($request->get('category_id'));
        $user = $this->getUser();
        if(empty($subject) || empty($category))
        {
            return View::create("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }
        else {
            $topic->setSubject($subject);
            $topic->setContent($content);
            $topic->setCategory($category);
            $topic->setDate($date);
            $topic->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($topic);
            $em->flush();

            return View::create($topic, Response::HTTP_CREATED, []);
        }
    }

    /**
     * edit topic
     * @param Request $request
     * @param $id
     * @return View
     * @throws \Exception
     */
    public function editTopic(Request $request, $id)
    {
        $subject = $request->get('subject');
        $content = $request->get('content');

        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View("Topic not found", Response::HTTP_NOT_FOUND);
        }
        elseif(empty($subject)|| empty($content))
        {
            return View::create("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create("FORBIDDEN" , Response::HTTP_FORBIDDEN);
            }
            $topic->setSubject($subject);
            $topic->setContent($content);
            $em->flush();
            return View::create($topic, Response::HTTP_CREATED, []);
        }
    }

    /**
     * delete topic
     * @param $id
     * @return View
     */
    public function deleteTopic($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $em->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View("Topic not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
            }
            $em->remove($topic);
            $em->flush();
            return View::create("Topic Deleted Successfully", Response::HTTP_OK);
        }
    }

    /**
     * show all topics
     * @return View
     */
    public function topics()
    {
        $topics = $this->getDoctrine()->getRepository('App\Entity\Topics')->findAll();
        if (empty($topics)) {
            return new View("No topics found", Response::HTTP_NOT_FOUND);
        }
        return View::create($topics, Response::HTTP_OK, []);
    }

    /**
     * show one topic
     * @param $id
     * @return View
     */
    public function topic($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $em->getRepository('App\Entity\Topics')->find($id);
        $views = $topic->getViews();
        $topic->setViews($views + 1);
        $em->persist($topic);
        $em->flush();
        if (empty($topic)) {
            return new View("Topic can not be found", Response::HTTP_NOT_FOUND);
        }
        return View::create($topic, Response::HTTP_OK, []);
    }

    public function countReplies($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $em->getRepository('App\Entity\Topics')->find($id);
        $replies = $topic->getReplies();
        if (empty($topic)) {
            return new View("Topic can not be found", Response::HTTP_NOT_FOUND);
        }
        return View::create(count($replies), Response::HTTP_OK, []);
    }

    public function hasAccess($idUser,$id){
        return ($id==$idUser)?true:false;
    }

    public function isAdmin($idUser){
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($idUser);
        return (in_array("ROLE_ADMIN" ,$user->getRoles()))?true:false;
    }

    public function isModerator($idUser){
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($idUser);
        return (in_array("ROLE_MODERATOR" ,$user->getRoles()))?true:false;
    }


    public function setResolved($id) {
        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View("Topic not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
            }

            if ($topic->hasStatu("RESOLVED"))
            {
                return View::create("Topic already resolved", Response::HTTP_BAD_REQUEST, []);
            }
            else
            {
                $topic->addStatu('RESOLVED');
                $topic->removeStatu('UNRESOLVED');
                $em->flush();
                return View::create($topic, Response::HTTP_CREATED, []);
            }

        }
    }

    public function setUnresolved($id) {
        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View("Topic not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
            }
            else
            {

                if ($topic->hasStatu("UNRESOLVED"))
                {
                    return View::create("Topic already unresolved", Response::HTTP_BAD_REQUEST, []);
                }
                else
                {
                    $topic->addStatu('UNRESOLVED');
                    $topic->removeStatu('RESOLVED');
                    $em->flush();
                    return View::create($topic->getStatus(), Response::HTTP_CREATED, []);
                }
            }


        }
    }

    public function setTopicClose($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View("Topic not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
            }

            if ($topic->hasStatu("CLOSED") && !$topic->hasStatu("OPEN"))
            {
                return View::create("Topic already closed", Response::HTTP_BAD_REQUEST, []);
            }
            else
            {
                $topic->setIsOpen(false);
                $topic->addStatu('CLOSED');
                $topic->removeStatu('OPEN');
                $em->flush();
                return View::create($topic, Response::HTTP_CREATED, []);
            }
        }
    }

    public function setTopicOpen($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View("Topic not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
            }

            if (!$topic->hasStatu("CLOSED") && $topic->hasStatu("OPEN"))
            {
                return View::create("Topic already open", Response::HTTP_BAD_REQUEST, []);
            }
            else
            {
                $topic->setIsOpen(true);
                $topic->removeStatu('CLOSED');
                $topic->addStatu('OPEN');
                $em->flush();
                return View::create($topic, Response::HTTP_CREATED, []);
            }
        }
    }
}