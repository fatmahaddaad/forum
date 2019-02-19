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
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserController extends AbstractController
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

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

    public function setProfilePicture($id, Request $request)
    {
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user)) {
            return new View("User can not be found", Response::HTTP_NOT_FOUND);
        }

        if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
        {
            return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
        }
        try {
            $file = $request->files->get( 'picture' );
            if (empty($file))
            {
                return View::create("Null value can not be send", Response::HTTP_BAD_REQUEST, []);
            }
            $fileName = md5 ( uniqid () ) . '.' . $file->guessExtension ();
            $original_name = $file->getClientOriginalName ();
            $file->move ( $this->params->get( 'app.path.profile_images' ), $fileName );

            $user->setImage($fileName);
            $user->setUpdatedAt(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $array = array (
                'status' => 1,
                'entity' => $user,
                'param' => $this->params->get('app.path.profile_images')
            );
            $response = View::create($array, Response::HTTP_OK, []);
            return $response;
        } catch ( Exception $e ) {
            $array = array('status'=> 0 );
            $response = View::create($array, Response::HTTP_BAD_REQUEST, []);
            return $response;
        }
    }

    public function editProfile(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user)) {
            return new View("User can not be found", Response::HTTP_NOT_FOUND);
        }

        if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
        {
            return View::create("FORBIDDEN", Response::HTTP_FORBIDDEN);
        }
        $bio = $request->get( 'bio' );
        $birthdate = new \DateTime($request->get( 'birthdate' ));
        $company = $request->get( 'company' );
        $fullname = $request->get( 'fullname' );
        $website = $request->get( 'website' );
        $user->setBio($bio);
        $user->setBirthdate($birthdate);
        $user->setCompany($company);
        $user->setFullname($fullname);
        $user->setWebsite($website);

        $em->flush();
        return View::create($user, Response::HTTP_CREATED, []);

    }

    public function hasAccess($idUser,$id){
        return ($id==$idUser)?true:false;
    }
}