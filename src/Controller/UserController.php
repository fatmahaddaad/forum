<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 18/02/2019
 * Time: 17:31
 */

namespace App\Controller;
use FOS\RestBundle\View\View;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends AbstractController
{
    public function totalPosts($id)
    {
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user)) {
            return new View("User can not be found", Response::HTTP_NOT_FOUND);
        }
        $topics = count($user->getTopics());
        $replies = count($user->getReplies());
        $comments = count($user->getComments());
        $posts = $topics + $replies + $comments;
        $result = array(["user"=> $user->getUsername(),"posts"=>$posts, "topics"=> $topics,"replies"=>$replies,"comments"=>$comments]);
        return View::create($result, Response::HTTP_OK, []);
    }

    public function passwordChange(Request $request, $id, UserPasswordEncoderInterface $encoder)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user)) {
            return new View("User can not be found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
            {
                return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
            }
            $password = $encoder->encodePassword($user, $request->get('password'));
            $user->setPassword($password);
            $em->flush();
            return View::create($user, Response::HTTP_OK, []);
        }

    }

    public function hasAccess($idUser,$id){
        return ($id==$idUser)?true:false;
    }
}