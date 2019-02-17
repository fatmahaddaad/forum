<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 17/02/2019
 * Time: 11:35
 */

namespace App\Controller;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Replies;
use App\Entity\Topics;
use App\Entity\Comments;

class CommentsController extends AbstractController
{
    /**
     * add new comment
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function addComment(Request $request)
    {
        $comment = new Comments();
        $content = $request->get('content');
        $date = new \DateTime();
        $reply = $this->getDoctrine()->getRepository('App\Entity\Replies')->find($request->get('reply_id'));
        $user = $this->getUser();
        if(empty($content) || empty($reply))
        {
            return View::create("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }
        else {
            $comment->setContent($content);
            $comment->setReply($reply);
            $comment->setDate($date);
            $comment->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            return View::create($comment, Response::HTTP_CREATED, []);
        }
    }

    /**
     * edit comment
     * @param Request $request
     * @param $id
     * @return View
     */
    public function editComment(Request $request, $id)
    {
        $content = $request->get('content');
        $em = $this->getDoctrine()->getManager();
        $comment = $this->getDoctrine()->getRepository('App\Entity\Comments')->find($id);
        if (empty($comment)) {
            return new View("Comment not found", Response::HTTP_NOT_FOUND);
        }
        elseif(empty($content))
        {
            return View::create("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }
        else
        {
            if(!$this->hasAccess($comment->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()))
            {
                return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
            }
            $comment->setContent($content);
            $em->flush();
            return View::create($comment, Response::HTTP_CREATED, []);
        }
    }

    /**
     * delete comment
     * @param $id
     * @return View
     */
    public function deleteComment($id)
    {
        $em = $this->getDoctrine()->getManager();
        $comment = $em->getRepository('App\Entity\Comments')->find($id);
        if (empty($comment)) {
            return new View("Comment not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($comment->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()))
            {
                return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
            }
            $em->remove($comment);
            $em->flush();
            return View::create("Comment Deleted Successfully", Response::HTTP_OK);
        }
    }

    /**
     * show all comments
     * @return View
     */
    public function comments()
    {
        $comments = $this->getDoctrine()->getRepository('App\Entity\Comments')->findAll();
        if (empty($comments)) {
            return new View("No comments found", Response::HTTP_NOT_FOUND);
        }
        return View::create($comments, Response::HTTP_OK, []);
    }

    /**
     * show one comment
     * @param $id
     * @return View
     */
    public function comment($id)
    {
        $comment = $this->getDoctrine()->getRepository('App\Entity\Comments')->find($id);
        if (empty($comment)) {
            return new View("Comment can not be found", Response::HTTP_NOT_FOUND);
        }
        return View::create($comment, Response::HTTP_OK, []);
    }

    public function hasAccess($idUser,$id){
        return ($id==$idUser)?true:false;
    }

    public function isAdmin($idUser){
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($idUser);
        return (in_array("ROLE_ADMIN" ,$user->getRoles()))?true:false;
    }

}