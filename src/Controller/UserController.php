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
        if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
        {
            return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
        }

        if (empty($user)) {
            return new View("User can not be found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            $old_pwd = $request->get('old_password');
            $checkPass = $encoder->isPasswordValid($user, $old_pwd);
            if($checkPass === true) {
                $new_pwd = $request->get('new_password');
                $new_pwd_confirm = $request->get('new_password_confirm');
                if ($new_pwd == $new_pwd_confirm)
                {
                    $password = $encoder->encodePassword($user, $new_pwd);
                    $user->setPassword($password);
                    $em->flush();
                    return View::create($user, Response::HTTP_OK, []);
                }
                else
                {
                    return View::create("Your password and confirmation password do not match ", Response::HTTP_NOT_ACCEPTABLE, []);
                }
            } else {
                return View::create("The old password you have entered is incorrect", Response::HTTP_NOT_ACCEPTABLE, []);
            }
        }

    }

    public function hasAccess($idUser,$id){
        return ($id==$idUser)?true:false;
    }
}