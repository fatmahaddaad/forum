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

class ModeratorController extends AbstractController
{
    public function setUserStatus($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->find($id);
        if (empty($user))
        {
            return new View("User not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            ($user->getIsActive() == true)?$user->setIsActive(false):$user->setIsActive(true);

            $em->flush();
            return View::create($user, Response::HTTP_CREATED, []);
        }
    }
}