<?php

namespace App\Controller;

use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return View
     * @throws \Exception
     *
     * @Route("api/register", methods={"POST"})
     *
     * @SWG\Tag(name="User")
     * @SWG\Response(
     *     response=201,
     *     description="Returns created user"
     * )
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="User Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="username", type="string"),
     *          @SWG\Property(property="password", type="string"),
     *          @SWG\Property(property="email", type="string")
     *     )
     * )
     *
     */
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
        $data = ['isLogged' => array('id' => $this->getUser()->getId(),
                                    'username' => $this->getUser()->getUsername(),
                                    'roles' => $this->getUser()->getRoles()
                                    )
                ];
        return View::create($data, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param $id
     * @return View
     *
     * @Route("api/promoteUser/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="User")
     * @SWG\Response(
     *     response=200,
     *     description="Returns modified user"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns access denied if not admin"
     * )
     */
    public function promoteUser(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository("App\Entity\User")->find($id);
        $user->addRole("ROLE_MODERATOR");
        $em->persist($user);
        $em->flush();
        return View::create($user, Response::HTTP_OK, []);
    }

    /**
     * @return View
     * @Route("api/allTotalPosts", methods={"GET"})
     *
     * @SWG\Tag(name="default")
     * @SWG\Response(
     *     response=200,
     *     description="Returns all total posts, topics, replies and comments"
     * )
     */
    public function allTotalPosts()
    {
        $topics = count($this->getDoctrine()->getRepository('App\Entity\Topics')->findAll());
        $replies = count($this->getDoctrine()->getRepository('App\Entity\Replies')->findAll());
        $comments = count($this->getDoctrine()->getRepository('App\Entity\Comments')->findAll());
        $posts = $topics + $replies + $comments;
        $result = array("posts"=>$posts, "topics"=> $topics,"replies"=>$replies,"comments"=>$comments);
        return View::create($result, Response::HTTP_OK, []);
    }
}
