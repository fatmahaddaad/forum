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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class CommentsController extends AbstractController
{
    /**
     * add new comment
     * @param Request $request
     * @return View
     * @throws \Exception
     *
     * @Route("api/addComment", methods={"POST"})
     *
     * @SWG\Tag(name="Comment")
     * @SWG\Response(
     *     response=201,
     *     description="Returns created comment"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when Null Values given"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Comment Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="content", type="string"),
     *          @SWG\Property(property="reply_id", type="integer")
     *     )
     * )
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
            return View::create(array("code" => 406, "message" => "NULL VALUES ARE NOT ALLOWED"), Response::HTTP_NOT_ACCEPTABLE);
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
     *
     * @Route("api/editComment/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Comment")
     * @SWG\Response(
     *     response=200,
     *     description="Returns modified comment"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when Null Values given"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when comment not found"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Comment Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="content", type="string"),
     *          @SWG\Property(property="reply_id", type="integer")
     *     )
     * )
     */
    public function editComment(Request $request, $id)
    {
        $content = $request->get('content');
        $em = $this->getDoctrine()->getManager();
        $comment = $this->getDoctrine()->getRepository('App\Entity\Comments')->find($id);
        if (empty($comment)) {
            return new View(array("code"=> 404, "message" => "Comment not found"), Response::HTTP_NOT_FOUND);
        }
        elseif(empty($content))
        {
            return View::create(array("code"=> 406, "message" => "NULL VALUES ARE NOT ALLOWED"), Response::HTTP_NOT_ACCEPTABLE);
        }
        else
        {
            if(!$this->hasAccess($comment->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code"=> 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }
            $comment->setContent($content);
            $em->flush();
            return View::create($comment, Response::HTTP_OK, []);
        }
    }

    /**
     * delete comment
     * @param $id
     * @return View
     *
     * @Route("api/deleteComment/{id}", methods={"POST"})
     *
     * @SWG\Tag(name="Comment")
     * @SWG\Response(
     *     response=200,
     *     description="Returns success message"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when comment not found"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     *
     */
    public function deleteComment($id)
    {
        $em = $this->getDoctrine()->getManager();
        $comment = $em->getRepository('App\Entity\Comments')->find($id);
        if (empty($comment)) {
            return new View(array("code"=> 404, "message" => "Comment not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            if(!$this->hasAccess($comment->getUser()->getId(),$this->getUser()->getId()) && !$this->isAdmin($this->getUser()->getId()) && !$this->isModerator($this->getUser()->getId()))
            {
                return View::create(array("code"=> 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
            }
            $em->remove($comment);
            $em->flush();
            return View::create(array("code"=> 200, "message" => "Comment Deleted Successfully"), Response::HTTP_OK);
        }
    }

    /**
     * show all comments
     * @return View
     *
     * @Route("api/comments", methods={"GET"})
     *
     * @SWG\Tag(name="Comment")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of comments"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when no comments found"
     * )
     */
    public function comments()
    {
        $comments = $this->getDoctrine()->getRepository('App\Entity\Comments')->findAll();
        if (empty($comments)) {
            return new View(array("code"=> 404, "message" => "No comments found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($comments, Response::HTTP_OK, []);
    }

    /**
     * show one comment by ID
     * @param $id
     * @return View
     *
     * @Route("api/comment/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Comment")
     * @SWG\Response(
     *     response=200,
     *     description="Returns one comment by id"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when comment not found"
     * )
     */
    public function comment($id)
    {
        $comment = $this->getDoctrine()->getRepository('App\Entity\Comments')->find($id);
        if (empty($comment)) {
            return new View(array("code"=> 404, "message" => "Comment can not be found"), Response::HTTP_NOT_FOUND);
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

    public function isModerator($idUser){
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($idUser);
        return (in_array("ROLE_MODERATOR" ,$user->getRoles()))?true:false;
    }
}