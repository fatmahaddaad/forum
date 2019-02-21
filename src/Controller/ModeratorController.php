<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 18/02/2019
 * Time: 14:00
 */

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

class ModeratorController extends AbstractController
{
    /**
     * Change user status
     * @param $id
     * @return View
     *
     * @Route("api/setUserStatus/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="User")
     * @SWG\Response(
     *     response=200,
     *     description="Returns modified comment"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns access denied if not moderator"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when comment not found"
     * )
     */
    public function setUserStatus($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user))
        {
            return new View(array("code" => 404, "message" => "User not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            ($user->getIsActive() == true)?$user->setIsActive(false):$user->setIsActive(true);

            $em->flush();
            return View::create($user, Response::HTTP_CREATED, []);
        }
    }
}