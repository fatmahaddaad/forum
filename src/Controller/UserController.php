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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc as ApiDoc;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @param $id
     * @return View
     *
     * @Route("api/totalPosts/{id}", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns the total posts, topics, replies and comments of a user"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when user not found"
     * )
     * @SWG\Tag(name="User")
     */
    public function totalPosts($id)
    {
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user)) {
            return new View(array("code" => 404, "message" => "User can not be found"), Response::HTTP_NOT_FOUND);
        }
        $topics = count($user->getTopics());
        $replies = count($user->getReplies());
        $comments = count($user->getComments());
        $posts = $topics + $replies + $comments;
        $result = array("user"=> $user->getUsername(),"posts"=>$posts, "topics"=> $topics,"replies"=>$replies,"comments"=>$comments);
        return View::create($result, Response::HTTP_OK, []);
    }

    /**
     * @param Request $request
     * @param $id
     * @param UserPasswordEncoderInterface $encoder
     * @return View
     *
     * @Route("api/passwordChange/{id}", methods={"PUT"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns user data"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when user not found"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when error"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="old_password", type="string"),
     *          @SWG\Property(property="new_password", type="string"),
     *          @SWG\Property(property="new_password_confirm", type="string")
     *     )
     * )
     * @SWG\Tag(name="Profile")
     */
    public function passwordChange(Request $request, $id, UserPasswordEncoderInterface $encoder)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
        {
            return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
        }

        if (empty($user)) {
            return new View(array("code" => 404, "message" => "User can not be found"), Response::HTTP_NOT_FOUND);
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
                    return View::create(array("code" => 406, "message" => "Your password and confirmation password do not match "), Response::HTTP_NOT_ACCEPTABLE, []);
                }
            } else {
                return View::create(array("code" => 406, "message" => "The old password you have entered is incorrect"), Response::HTTP_NOT_ACCEPTABLE, []);
            }
        }

    }

    /**
     * @param $id
     * @param Request $request
     * @return View
     * @throws \Exception
     *
     * @Route("api/setProfilePicture/{id}", methods={"POST"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns user data and profile_images path"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when user not found"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returned when error"
     * )
     *
     * @SWG\Parameter(
     *     name="picture",
     *     type="file",
     *     in="formData",
     *     required=true,
     *     description="profile picture"
     * )
     *
     * @SWG\Tag(name="Profile")
     */
    public function setProfilePicture($id, Request $request)
    {
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user)) {
            return new View(array("code" => 404, "message" => "User can not be found"), Response::HTTP_NOT_FOUND);
        }

        if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
        {
            return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
        }
        try {
            $file = $request->files->get( 'picture' );
            if (empty($file))
            {
                return View::create(array("code" => 400, "message" => "Null value can not be send"), Response::HTTP_BAD_REQUEST, []);
            }
            $fileName = md5 ( uniqid () ) . '.' . $file->guessExtension ();
            $original_name = $file->getClientOriginalName ();
            $file->move ( $this->params->get( 'app.path.profile_images' ), $fileName );

            $user->setImage($fileName);
            $user->setUpdatedAt(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $array = array (
                "code" => 200,
                'status' => 1,
                'param' => $this->params->get('app.path.profile_images'),
                'entity' => $user
            );
            $response = View::create($array, Response::HTTP_OK, []);
            return $response;
        } catch ( Exception $e ) {
            $array = array("code" => 400, 'status'=> 0 );
            $response = View::create($array, Response::HTTP_BAD_REQUEST, []);
            return $response;
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return View
     * @throws \Exception
     *
     * @Route("api/EditProfile/{id}", methods={"PUT"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns user data"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when user not found"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Profile Info",
     *     @SWG\Schema(
     *          @SWG\Property(property="bio", type="string"),
     *          @SWG\Property(property="birthdate", type="string"),
     *          @SWG\Property(property="company", type="string"),
     *          @SWG\Property(property="fullname", type="string"),
     *          @SWG\Property(property="website", type="string")
     *     )
     * )
     *
     *
     * @SWG\Tag(name="Profile")
     */
    public function editProfile(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user)) {
            return new View(array("code" => 404, "message" => "User can not be found"), Response::HTTP_NOT_FOUND);
        }

        if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
        {
            return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
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
        return View::create($user, Response::HTTP_OK, []);

    }

    public function hasAccess($idUser,$id){
        return ($id==$idUser)?true:false;
    }

    /**
     * @param $id
     * @return View
     *
     * @SWG\Tag(name="User")
     * @Route("api/userShow/{id}", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns user data for public"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when user not found"
     * )
     *
     */
    public function userShow($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('App\Entity\User')->find($id);

        if (empty($user)) {
            return new View(array("code" => 404, "message" => "User can not be found"), Response::HTTP_NOT_FOUND);
        }
        $data = array(
                    "topics" => $user->getTopics(),
                    "replies" => $user->getReplies(),
                    "comments" => $user->getComments(),
                    "picture" => $user->getImage(),
                    "bio" => $user->getBio(),
                    "birthdate" => $user->getBirthdate(),
                    "company" => $user->getCompany(),
                    "fullname"=> $user->getFullname()
                );

        return View::create($data, Response::HTTP_OK, []);
    }

    /**
     * @param $id
     * @return View
     *
     * @SWG\Tag(name="Profile")
     * @Route("api/profileShow/{id}", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns user data for profile owner"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when user not found"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     */
    public function profileShow($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('App\Entity\User')->find($id);
        $topics = $this->getDoctrine()->getRepository('App\Entity\Topics')->findBy(array('user' => $id));
        if (empty($user)) {
            return new View(array("code" => 404, "message" => "User can not be found"), Response::HTTP_NOT_FOUND);
        }
        if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
        {
            return View::create(array("code" => 403, "message" => "FORBIDDEN"), Response::HTTP_FORBIDDEN);
        }
        $data = array('user' => $user, 'topics' => $topics);
        return View::create($data, Response::HTTP_OK, []);
    }

    /**
     * @param $id
     * @return View
     * @throws \Exception
     *
     * @SWG\Tag(name="Profile")
     * @Route("api/removeProfilePicture/{id}", methods={"POST"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns user data for profile owner"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when user not found"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns forbidden when user doesn't have access"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returned when profile picture not found"
     * )
     */
    public function removeProfilePicture($id)
    {
        $fileSystem = new Filesystem();
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user)) {
            return new View(array("code" => 404, 'message'=> "User can not be found"), Response::HTTP_NOT_FOUND);
        }

        if (!$this->hasAccess($user->getId(),$this->getUser()->getId()))
        {
            return View::create(array("code" => 403, 'message'=> "FORBIDDEN"), Response::HTTP_FORBIDDEN);
        }
        try {
            $file = $user->getImage();
            if (empty($file))
            {
                return View::create(array("code" => 400, 'message'=> "Profile picture can not be found"), Response::HTTP_BAD_REQUEST, []);
            }

            $fileSystem->remove($this->params->get('app.path.profile_images')."/".$file);
            $user->setImage("");
            $user->setUpdatedAt(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $array = array (
                "code" => 200,
                'status' => 1,
                'param' => $this->params->get('app.path.profile_images'),
                'entity' => $user,
            );
            $response = View::create($array, Response::HTTP_OK, []);
            return $response;
        } catch ( Exception $e ) {
            $array = array("code" => 400, 'status'=> 0 );
            $response = View::create($array, Response::HTTP_BAD_REQUEST, []);
            return $response;
        }
    }

    public function topicsByUser($id)
    {

    }
}