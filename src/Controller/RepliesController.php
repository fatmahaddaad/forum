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

class RepliesController extends AbstractController
{
    /**
     * add new reply
     * @param Request $request
     * @return View
     * @throws \Exception
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
            return View::create("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }
        else {
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
     */
    public function editReply(Request $request, $id)
    {
        $reply = new Replies();
        $content = $request->get('content');
        $em = $this->getDoctrine()->getManager();
        $reply = $this->getDoctrine()->getRepository('App\Entity\Replies')->find($id);
        if (empty($reply)) {
            return new View("Reply not found", Response::HTTP_NOT_FOUND);
        }
        elseif(empty($content))
        {
            return View::create("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }
        else
        {
            $reply->setContent($content);
            $em->flush();
            return View::create($reply, Response::HTTP_CREATED, []);
        }
    }

    /**
     * delete reply
     * @param $id
     * @return View
     */
    public function deleteReply($id)
    {
        $em = $this->getDoctrine()->getManager();
        $reply = $em->getRepository('App\Entity\Replies')->find($id);
        if (empty($reply)) {
            return new View("Reply not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            $em->remove($reply);
            $em->flush();
            return View::create("Reply Deleted Successfully", Response::HTTP_OK);
        }
    }

    /**
     * show all replies
     * @return View
     */
    public function replies()
    {
        $replies = $this->getDoctrine()->getRepository('App\Entity\Replies')->findAll();
        if (empty($replies)) {
            return new View("No replies found", Response::HTTP_NOT_FOUND);
        }
        return View::create($replies, Response::HTTP_OK, []);
    }

    /**
     * show one reply
     * @param $id
     * @return View
     */
    public function reply($id)
    {
        $reply = $this->getDoctrine()->getRepository('App\Entity\Replies')->find($id);
        if (empty($reply)) {
            return new View("Reply can not be found", Response::HTTP_NOT_FOUND);
        }
        return View::create($reply, Response::HTTP_OK, []);
    }
}