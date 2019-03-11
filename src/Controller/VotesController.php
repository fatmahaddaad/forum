<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 17/02/2019
 * Time: 14:14
 */

namespace App\Controller;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Votes;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class VotesController extends AbstractController
{
    /**
     * @param Request $request
     * @return View
     *
     * @Route("api/addVote", methods={"POST"})
     *
     * @SWG\Tag(name="Vote")
     * @SWG\Response(
     *     response=201,
     *     description="Returns created vote"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when Null or both Values given or user trying to vote his own reply"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Returned when user already voted on the reply"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returned when vote modified"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Vote Info, send `up` value empty to vote down and `down` value empty to vote up",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="up", type="string"),
     *          @SWG\Property(property="down", type="string"),
     *          @SWG\Property(property="reply_id", type="integer")
     *     )
     * )
     */
    public function addVote(Request $request)
    {
        $vote = new Votes();
        $up = $request->get('up');
        $down = $request->get('down');
        if (!empty($up) && empty($down)) {$vote_val = "1";}
        if (empty($up) && !empty($down)) {$vote_val = "-1";}
        $reply = $this->getDoctrine()->getRepository('App\Entity\Replies')->find($request->get('reply_id'));
        $user = $this->getUser();
        if(empty($reply) || (empty($up) && empty($down)))
        {
            return View::create(array("code" => 406, "message" => "Null values are not allowed"), Response::HTTP_NOT_ACCEPTABLE);
        }
        elseif (!empty($up) && !empty($down))
        {
            return View::create(array("code" => 406, "message" => "Both values can not be send"), Response::HTTP_NOT_ACCEPTABLE);
        }
        elseif ($this->voteExist($user->getId(),$reply))
        {
            $criteria = array('user' => $user->getId(), 'reply' => $reply);
            $votes = $this->getDoctrine()->getRepository('App\Entity\Votes')->findOneBy($criteria);

            if($votes->getVote() == $vote_val) {
                return View::create(array("code" => 400, "message" => "You already voted on this reply"), Response::HTTP_BAD_REQUEST);
            } else
            {
                return $this->editVote($votes->getId());
            }
        }
        elseif ($this->replyOwner($user->getId(),$reply->getUser()->getId()))
        {
            return View::create(array("code" => 406, "message" => "You can not vote your own reply"), Response::HTTP_NOT_ACCEPTABLE);
        }
        else {
            $vote->setVote($vote_val);
            $vote->setReply($reply);
            $vote->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($vote);
            $em->flush();

            return View::create($vote, Response::HTTP_CREATED, []);
        }
    }

    public function voteExist($idUser,$idReply)
    {
        $criteria = array('user' => $idUser, 'reply' => $idReply);
        $votes = $this->getDoctrine()->getRepository('App\Entity\Votes')->findOneBy($criteria);
        return (!empty($votes))?true:false;
    }

    public function replyOwner($idUser,$id)
    {
        return ($id==$idUser)?true:false;
    }
    public function editVote($id) {

        $em = $this->getDoctrine()->getManager();
        $vote = $this->getDoctrine()->getRepository('App\Entity\Votes')->find($id);
        $old_vote = $vote->getVote();
        $new_vote = $old_vote * (-1);
        $vote->setVote($new_vote);
        $em->flush();
        return View::create($vote, Response::HTTP_OK, []);
    }
}