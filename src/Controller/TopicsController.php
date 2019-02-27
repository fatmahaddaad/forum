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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class TopicsController extends AbstractController
{
    /**
     * add new topic
     * @param Request $request
     * @return View
     * @throws \Exception
     *
     * @Route("api/addTopic", methods={"POST"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=201,
     *     description="Returns created topic"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when Null Values given"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Topic Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="subject", type="string"),
     *          @SWG\Property(property="content", type="string"),
     *          @SWG\Property(property="category_id", type="integer")
     *     )
     * )
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
            return View::create(array("code" => 406, "message" => "NULL VALUES ARE NOT ALLOWED"), Response::HTTP_NOT_ACCEPTABLE);
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
     *
     * @Route("api/editTopic/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns modified topic"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when Null Values given"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Topic Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="subject", type="string"),
     *          @SWG\Property(property="content", type="string")
     *     )
     * )
     */
    public function editTopic(Request $request, $id)
    {
        $subject = $request->get('subject');
        $content = $request->get('content');

        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View(array("code" => 404, "message" => "Topic not found"), Response::HTTP_NOT_FOUND);
        }
        elseif(empty($subject)|| empty($content))
        {
            return View::create(array("code" => 406, "message" => "NULL VALUES ARE NOT ALLOWED"), Response::HTTP_NOT_ACCEPTABLE);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN") , Response::HTTP_FORBIDDEN);
            }
            $topic->setSubject($subject);
            $topic->setContent($content);
            $em->flush();
            return View::create($topic, Response::HTTP_OK, []);
        }
    }

    /**
     * delete topic
     * @param $id
     * @return View
     *
     * @Route("api/deleteTopic/{id}", methods={"POST"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns success message"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
     */
    public function deleteTopic($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $em->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View(array("code" => 404, "message" => "Topic not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }
            $em->remove($topic);
            $em->flush();
            return View::create(array("code" => 200, "message" => "Topic Deleted Successfully"), Response::HTTP_OK);
        }
    }

    /**
     * show all topics
     * @return View
     *
     * @Route("api/topics", methods={"GET"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of topics"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when no topics found"
     * )
     */
    public function topics()
    {
        $topics = $this->getDoctrine()->getRepository('App\Entity\Topics')->findAll();
        if (empty($topics)) {
            return new View(array("code" => 404, "message" => "No topics found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($topics, Response::HTTP_OK, []);
    }

    /**
     * show one topic
     * @param $id
     * @return View
     *
     * @Route("api/topic/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns one topic by id"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
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
            return new View(array("code" => 404, "message" => "Topic can not be found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($topic, Response::HTTP_OK, []);
    }

    /**
     * @param $id
     * @return View
     *
     * @Route("api/repliesByTopic/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of replies from one topic"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
     */
    public function repliesByTopic($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $em->getRepository('App\Entity\Topics')->find($id);
        $replies = $em->getRepository('App\Entity\Replies')->findBy(array("topic" => $topic->getId()));
        if (empty($topic)) {
            return new View(array("code" => 404, "message" => "Topic can not be found"), Response::HTTP_NOT_FOUND);
        }
        if (count($replies) == 0) {
            return new View(array("code" => 404, "message" => "No replies found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($replies, Response::HTTP_OK, []);
    }

    /**
     * count number of replies in one topic
     * @param $id
     * @return View
     *
     * @Route("api/countReplies/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns number"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
     */
    public function countReplies($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $em->getRepository('App\Entity\Topics')->find($id);
        $replies = $topic->getReplies();
        if (empty($topic)) {
            return new View(array("code" => 404, "message" => "Topic can not be found"), Response::HTTP_NOT_FOUND);
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

    /**
     * Mark topic as resolved
     * @param $id
     * @return View
     *
     * @Route("api/setResolved/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns resolved topic"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returned when topic already resolved"
     * )
     *
     */
    public function setResolved($id) {
        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View(array("code" => 404, "message" => "Topic not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }

            if ($topic->hasStatu("RESOLVED"))
            {
                return View::create(array("code" => 400, "message" => "Topic already resolved"), Response::HTTP_BAD_REQUEST, []);
            }
            else
            {
                $topic->addStatu('RESOLVED');
                $topic->removeStatu('UNRESOLVED');
                $em->flush();
                return View::create($topic, Response::HTTP_OK, []);
            }

        }
    }

    /**
     * Mark topic as unresolved
     * @param $id
     * @return View
     *
     * @Route("api/setUnresolved/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns unresolved topic"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returned when topic already unresolved"
     * )
     */
    public function setUnresolved($id) {
        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View(array("code" => 404, "message" => "Topic not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }
            else
            {

                if ($topic->hasStatu("UNRESOLVED"))
                {
                    return View::create(array("code" => 400, "message" => "Topic already unresolved"), Response::HTTP_BAD_REQUEST, []);
                }
                else
                {
                    $topic->addStatu('UNRESOLVED');
                    $topic->removeStatu('RESOLVED');
                    $em->flush();
                    return View::create($topic, Response::HTTP_OK, []);
                }
            }
        }
    }
    /**
     * Mark topic as closed
     * @param $id
     * @return View
     *
     * @Route("api/setTopicClose/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns closed topic"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returned when topic already closed"
     * )
     */
    public function setTopicClose($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View(array("code" => 404, "message" => "Topic not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }

            if ($topic->hasStatu("CLOSED") && !$topic->hasStatu("OPEN"))
            {
                return View::create(array("code" => 400, "message" => "Topic already closed"), Response::HTTP_BAD_REQUEST, []);
            }
            else
            {
                $topic->setIsOpen(false);
                $topic->addStatu('CLOSED');
                $topic->removeStatu('OPEN');
                $em->flush();
                return View::create($topic, Response::HTTP_OK, []);
            }
        }
    }
    /**
     * Mark topic as open
     * @param $id
     * @return View
     *
     * @Route("api/setTopicOpen/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Topic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns open topic"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when topic not found"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returned when topic already open"
     * )
     */
    public function setTopicOpen($id)
    {
        $em = $this->getDoctrine()->getManager();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($id);
        if (empty($topic)) {
            return new View(array("code" => 404, "message" => "Topic not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($topic->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }

            if (!$topic->hasStatu("CLOSED") && $topic->hasStatu("OPEN"))
            {
                return View::create(array("code" => 400, "message" => "Topic already open"), Response::HTTP_BAD_REQUEST, []);
            }
            else
            {
                $topic->setIsOpen(true);
                $topic->removeStatu('CLOSED');
                $topic->addStatu('OPEN');
                $em->flush();
                return View::create($topic, Response::HTTP_OK, []);
            }
        }
    }
}