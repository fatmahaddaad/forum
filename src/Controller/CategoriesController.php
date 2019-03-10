<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 13/02/2019
 * Time: 12:47
 */

namespace App\Controller;

use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Categories;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;

class CategoriesController extends AbstractController
{
    /**
     * add new category
     * @param Request $request
     * @return View
     *
     * @Route("api/addCategory", methods={"POST"})
     *
     * @SWG\Tag(name="Category")
     * @SWG\Response(
     *     response=201,
     *     description="Returns created category"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns access denied if not admin or moderator"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when Null Values given"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Category Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="description", type="string")
     *     )
     * )
     */
    public function addCategory(Request $request)
    {
        $category = new Categories();
        $name = $request->get('name');
        $description = $request->get('description');
        if(empty($name) || empty($description))
        {
            return View::create(array("code" => 406, "message" => "NULL VALUES ARE NOT ALLOWED"), Response::HTTP_NOT_ACCEPTABLE);
        }
        else {
            $category->setName($name);
            $category->setDescription($description);

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            return View::create($category, Response::HTTP_CREATED, []);
        }
    }

    /**
     * edit category
     * @param Request $request
     * @param $id
     * @return View
     *
     * @Route("api/editCategory/{id}", methods={"PUT"})
     *
     * @SWG\Tag(name="Category")
     * @SWG\Response(
     *     response=200,
     *     description="Returns category"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns access denied if not admin or moderator"
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Returned when Null Values given"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when category not found"
     * )
     *
     * @SWG\Parameter(
     *     name="Values",
     *     in="body",
     *     description="Category Info",
     *     required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="description", type="string")
     *     )
     * )
     *
     *
     */
    public function editCategory(Request $request, $id)
    {
        $name = $request->get('name');
        $description = $request->get('description');

        $em = $this->getDoctrine()->getManager();
        $category = $this->getDoctrine()->getRepository('App\Entity\Categories')->find($id);
        if (empty($category)) {
            return new View(array("code" => 404, "message" => "Category not found"), Response::HTTP_NOT_FOUND);
        }
        elseif(empty($name) || empty($description))
        {
            return View::create(array("code" => 404, "message" => "NULL VALUES ARE NOT ALLOWED"), Response::HTTP_NOT_ACCEPTABLE);
        }
        else
        {
            $category->setName($name);
            $category->setDescription($description);
            $em->flush();
            return View::create($category, Response::HTTP_OK, []);
        }
    }

    /**
     * delete category
     * @param $id
     * @return View
     *
     * @Route("api/deleteCategory/{id}", methods={"POST"})
     *
     * @SWG\Tag(name="Category")
     * @SWG\Response(
     *     response=200,
     *     description="Returns success message"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Returns access denied if not admin or moderator"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when category not found"
     * )
     *
     *
     */
    public function deleteCategory($id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('App\Entity\Categories')->find($id);
        if (empty($category)) {
            return new View(array("code"=> 404, "message" => "Category not found"), Response::HTTP_NOT_FOUND);
        }
        else
        {
            $em->remove($category);
            $em->flush();
            return View::create(array("code"=> 200, "message" => "Category Deleted Successfully"), Response::HTTP_OK);
        }
    }

    /**
     * show all categories
     * @return View
     *
     * @Route("api/categories", methods={"GET"})
     *
     * @SWG\Tag(name="Category")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of categories"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when no categories found"
     * )
     *
     */
    public function categories()
    {
        $categories = $this->getDoctrine()->getRepository('App\Entity\Categories')->findAll();
        if (empty($categories)) {
            return new View(array("code" => 404 ,"message" => "No categories found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($categories, Response::HTTP_OK, []);
    }

    /**
     * show one category by ID
     * @param $id
     * @return View
     *
     * @Route("api/category/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Category")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of categories"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when category no found"
     * )
     */
    public function category($id)
    {
        $category = $this->getDoctrine()->getRepository('App\Entity\Categories')->find($id);
        if (empty($category)) {
            return new View(array("code" => 404 ,"message" => "Category can not be found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($category, Response::HTTP_OK, []);
    }

    /**
     * Count number of topics in one category by category_ID
     * @param $id
     * @return View
     *
     * @Route("api/countTopics/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Category")
     * @SWG\Response(
     *     response=200,
     *     description="Returns number"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when category no found"
     * )
     */
    public function countTopics($id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('App\Entity\Categories')->find($id);
        $topics = $category->getTopics();
        if (empty($category)) {
            return new View(array("code" => 404 , "message" => "Category can not be found"), Response::HTTP_NOT_FOUND);
        }
        return View::create(count($topics), Response::HTTP_OK, []);
    }
    /**
     * Topics by category_ID
     * @param $id
     * @return View
     *
     * @Route("api/topicsByCategory/{id}", methods={"GET"})
     *
     * @SWG\Tag(name="Category")
     * @SWG\Response(
     *     response=200,
     *     description="Returns list of topics"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Returned when category no found"
     * )
     */
    public function topicsByCategory($id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('App\Entity\Categories')->find($id);
        $topics = $em->getRepository('App\Entity\Topics')->findBy(array("category" => $category->getId()));
        if (empty($topics)) {
            return new View(array("code" => 404, "message" => "No topics found"), Response::HTTP_NOT_FOUND);
        }
        return View::create($topics, Response::HTTP_OK, []);
//        $thisTopics = [];
//        $thisCategory = array("id" => $category->getId(),
//            "name" => $category->getName(),
//            "description" => $category->getDescription());
//        if (count($topics) == 0)
//        {
//            return View::create(array("topics" => 0, "replies" => 0, "scores" => 0 ), Response::HTTP_OK, []);
//        }
//        else
//        {
//            foreach ($topics as $topic) {
//                $replies = $em->getRepository('App\Entity\Replies')->findBy(array("topic" => $topic->getId()));
//                $thisTopic = array("id" => $topic->getId(),
//                    "subject" => $topic->getSubject(),
//                    "content" => $topic->getContent(),
//                    "date" => $topic->getDate(),
//                    "views" => $topic->getViews(),
//                    "status" => $topic->getStatus(),
//                    "is_open" => $topic->getIsOpen(),
//                    "user" => array("id" => $topic->getUser()->getId(), "username" => $topic->getUser()->getUsername(), "image" => $topic->getUser()->getImage()) ,
//                    "category" => array("id" => $topic->getCategory()->getId(), "name" => $topic->getCategory()->getName()));
//                if (count($replies) == 0) {
//                    $result = array("topic" => $thisTopic, "replies" => 0, "scores" => 0 );
//                    array_push($thisTopics, $result);
//                    //return View::create(array("topics" => $thisTopics, "replies" => 0, "scores" => 0 ), Response::HTTP_OK, []);
//                }
//                else
//                {
//                    $scores = [];
//                    foreach ($replies as $reply)
//                    {
//                        $em = $this->getDoctrine()->getManager();
//                        $votes = $em->getRepository('App\Entity\Votes')->findBy(array("reply" => $reply->getId()));
//
//                        if (empty($votes)) {
//                            $score = 0;
//                        }
//                        $score = 0;
//                        foreach ($votes as $vote)
//                        {
//                            $score += $vote->getVote();
//                        }
//                        array_push($scores, array("reply" => $reply->getId(), "score" => $score));
//                    }
//                    $result = array("topic" => $thisTopic, "replies" => $replies, "scores" => $scores);
//                    array_push($thisTopics, $result);
//                }
//            }
//            return View::create(array('category' => $thisCategory, 'data' => $thisTopics), Response::HTTP_OK, []);
//        }
    }
}