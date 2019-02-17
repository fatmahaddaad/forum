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

class VotesController extends AbstractController
{
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
            return View::create("Null values are not allowed", Response::HTTP_NOT_FOUND);
        }
        elseif (!empty($up) && !empty($down))
        {
            return View::create("Both values can not be send", Response::HTTP_NOT_ACCEPTABLE);
        }
        elseif ($this->voteExist($user->getId(),$reply))
        {
            $criteria = array('user' => $user->getId(), 'reply' => $reply);
            $votes = $this->getDoctrine()->getRepository('App\Entity\Votes')->findOneBy($criteria);

            if($votes->getVote() == $vote_val) {
                return View::create("You already voted on this reply", Response::HTTP_IM_USED);
            } else
            {
                return $this->editVote($votes->getId());
            }
        }
        elseif ($this->replyOwner($user->getId(),$reply->getUser()->getId()))
        {
            return View::create("You can not vote your own reply", Response::HTTP_NOT_ACCEPTABLE);
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
        return View::create($vote, Response::HTTP_UPGRADE_REQUIRED, []);
    }
}