<?php

namespace App\Controller;

use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class DefaultController extends AbstractController
{
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $user = new User($request->get('username'));
        $user->setPassword($encoder->encodePassword($user, $request->get('password')));
        $user->setEmail($request->get('email'));
        $user->setDate(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return View::create($user, Response::HTTP_CREATED, []);
    }

    public function api()
    {
        $data = ['isLogged' => sprintf('Logged in as %s', $this->getUser()->getUsername())];
        return View::create($data, Response::HTTP_OK);
    }

    public function promoteUser(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository("App\Entity\User")->find($id);
        $user->addRole("ROLE_MODERATOR");
        $em->persist($user);
        $em->flush();
        return View::create($user, Response::HTTP_CREATED, []);
    }

    public function allTotalPosts()
    {
        $topics = count($this->getDoctrine()->getRepository('App\Entity\Topics')->findAll());
        $replies = count($this->getDoctrine()->getRepository('App\Entity\Replies')->findAll());
        $comments = count($this->getDoctrine()->getRepository('App\Entity\Comments')->findAll());
        $posts = $topics + $replies + $comments;
        $result = array(["posts"=>$posts, "topics"=> $topics,"replies"=>$replies,"comments"=>$comments]);
        return View::create($result, Response::HTTP_OK, []);
    }
}
