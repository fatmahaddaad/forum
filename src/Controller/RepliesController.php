<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 13/02/2019
 * Time: 12:48
 */

namespace App\Controller;

use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Replies;
use App\Entity\Topics;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class RepliesController extends AbstractController
{
    /**
     * add new reply
     * @param Request $request
     * @return View
     * @throws \Exception
     *
     * @Route("api/addReply", methods={"POST"})
     *
     * @SWG\Tag(name="Reply")
     * @SWG\Response(
     *     response=201,
     *     description="Returns created reply"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when Null Values given"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Reply Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="content", type="string"),
     *          @SWG\Property(property="topic_id", type="integer")
     *     )
     * )
     */
    public function addReply(Request $request)
    {
        $reply = new Replies();
        $content = $request->get('content');
        $date = new \DateTime();
        $topic = $this->getDoctrine()->getRepository('App\Entity\Topics')->find($request->get('topic_id'));
        $user = $this->getUser();
        if(empty($content) || empty($topic))
        {
            return View::create(array("code" => 406, "message" => "NULL VALUES ARE NOT ALLOWED"), Response::HTTP_NOT_ACCEPTABLE);
        }
        else {
            if (count($topic->getReplies()) == 0) {
                $em = $this->getDoctrine()->getManager();
                $topic->removeStatu('UNANSWERED');
                $topic->addStatu('ANSWERED');
                $em->flush();
            }
            $reply->setContent($content);
            $reply->setTopic($topic);
            $reply->setDate($date);
            $reply->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($reply);
            $em->flush();

            return View::create($reply, Response::HTTP_CREATED, []);
        }
    }

    /**
     * edit reply
     * @param Request $request
     * @param $id
     * @return View
     *
     * @Route("api/editReply/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Reply")
     * @SWG\Response(
     *     response=200,
     *     description="Returns modified reply"
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
     *     description="Returned when reply not found"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Category Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="content", type="string")
     *     )
     * )
     */
    public function editReply(Request $request, $id)
    {
        $content = $request->get('content');
        $em = $this->getDoctrine()->getManager();
        $reply = $this->getDoctrine()->getRepository('App\Entity\Replies')->find($id);
        if (empty($reply)) {
            return new View(array("code" => 404, "message" => "Reply not found"), Response::HTTP_NOT_FOUND);
        }
        elseif(empty($content))
        {
            return View::create(array("code" => 404, "message" => "NULL VALUES ARE NOT ALLOWED"), Response::HTTP_NOT_ACCEPTABLE);
        }
        else
        {
            if(!$this->hasAccess($reply->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }
            $reply->setContent($content);
            $em->flush();
            return View::create($reply, Response::HTTP_OK, []);
        }
    }

    /**
     * delete reply
     * @param $id
     * @return View
     *
     * @Route("api/deleteReply/{id}", methods={"POST"})
     *
     * @SWG\Tag(name="Reply")
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
     *     description="Returned when category not found"
     * )
     */
    public function deleteReply($id)
    {
        $em = $this->getDoctrine()->getManager();
        $reply = $em->getRepository('App\Entity\Replies')->find($id);
        if (empty($reply)) {
            return new View(array("code"=> 404, "message" => "Reply not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($reply->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }
            $em->remove($reply);
            $em->flush();
            return View::create(array("code"=> 200, "message" => "Reply Deleted Successfully"), Response::HTTP_OK);
        }
    }

    /**
     * show all replies
     * @return View
     *
     * Route("api/replies", methods={"GET"})
     *
     * @SWG\Tag(name="Reply")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of replies"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when no replies found"
     * )
     */
    public function replies()
    {
        $replies = $this->getDoctrine()->getRepository('App\Entity\Replies')->findAll();
        if (empty($replies)) {
            return new View(array("code" => 404 ,"message" => "No replies found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($replies, Response::HTTP_OK, []);
    }

    /**
     * show one reply
     * @param $id
     * @return View
     *
     * @Route("api/reply/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Reply")
     * @SWG\Response(
     *     response=200,
     *     description="Returns one reply by id"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when reply not found"
     * )
     */
    public function reply($id)
    {
        $reply = $this->getDoctrine()->getRepository('App\Entity\Replies')->find($id);
        if (empty($reply)) {
            return new View(array("code"=> 404, "message" => "Reply can not be found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($reply, Response::HTTP_OK, []);
    }

    /**
     * Count score of votes in one reply by reply_ID
     * @param $id
     * @return View
     *
     * @Route("api/countVotes/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Reply")
     * @SWG\Response(
     *     response=200,
     *     description="Returns number"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when reply not found"
     * )
     */
    public function countVotes($id)
    {
        $em = $this->getDoctrine()->getManager();
        $votes = $em->getRepository('App\Entity\Votes')->findBy(array("reply" => $id));

        if (empty($votes)) {
            return new View(0, Response::HTTP_NOT_FOUND);
        }
        $score = 0;
        foreach ($votes as $vote)
        {
            $score += $vote->getVote();
        }
        return View::create($score , Response::HTTP_OK, []);
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
     * Mark reply as solution
     * @param $id
     * @return View
     *
     * @Route("api/setCorrectAnswer/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Reply")
     * @SWG\Response(
     *     response=200,
     *     description="Returns marked reply"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when reply not found"
     * )
     *
     */
    public function setCorrectAnswer($id)
    {
        $em = $this->getDoctrine()->getManager();
        $reply = $this->getDoctrine()->getRepository('App\Entity\Replies')->find($id);
        if (empty($reply)) {
            return new View(array("code" => 404, "message" => "Reply not found"), Response::HTTP_NOT_FOUND);
        }
        elseif($reply->getIsCorrect() == true)
        {
            if(!$this->hasAccess($reply->getTopic()->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }
            $reply->setIsCorrect(false);
            $em->flush();
            return View::create($reply, Response::HTTP_OK, []);
        }
        else
        {
            if(!$this->hasAccess($reply->getTopic()->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()))
            {
                return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }
            $reply->setIsCorrect(true);
            $em->flush();
            return View::create($reply, Response::HTTP_OK, []);
        }
    }
}