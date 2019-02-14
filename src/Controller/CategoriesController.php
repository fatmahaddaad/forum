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

class CategoriesController extends AbstractController
{
    /**
     * add new category
     * @param Request $request
     * @return View
     */
    public function addCategory(Request $request)
    {
        $category = new Categories();
        $name = $request->get('name');
        $description = $request->get('description');
        if(empty($name) || empty($description))
        {
            return View::create("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
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
     */
    public function editCategory(Request $request, $id)
    {
        $category = new Categories();

        $name = $request->get('name');
        $description = $request->get('description');

        $em = $this->getDoctrine()->getManager();
        $category = $this->getDoctrine()->getRepository('App\Entity\Categories')->find($id);
        if (empty($category)) {
            return new View("Tutorial not found", Response::HTTP_NOT_FOUND);
        }
        elseif(empty($name) || empty($description))
        {
            return View::create("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
        }
        else
        {
            $category->setName($name);
            $category->setDescription($description);
            $em->flush();
            return View::create($category, Response::HTTP_CREATED, []);
        }
    }

    /**
     * delete category
     * @param $id
     * @return View
     */
    public function deleteCategory($id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('App\Entity\Categories')->find($id);
        if (empty($category)) {
            return new View("Category not found", Response::HTTP_NOT_FOUND);
        }
        else
        {
            $em->remove($category);
            $em->flush();
            return View::create("Category Deleted Successfully", Response::HTTP_OK);
        }
    }

    /**
     * show all categories
     * @return View
     */
    public function categories()
    {
        $categories = $this->getDoctrine()->getRepository('App\Entity\Categories')->findAll();
        if (empty($categories)) {
            return new View("No categories found", Response::HTTP_NOT_FOUND);
        }
        return View::create($categories, Response::HTTP_OK, []);
    }

    /**
     * show one category
     * @param $id
     * @return View
     */
    public function category($id)
    {
        $category = $this->getDoctrine()->getRepository('App\Entity\Categories')->find($id);
        if (empty($category)) {
            return new View("Category can not be found", Response::HTTP_NOT_FOUND);
        }
        return View::create($category, Response::HTTP_OK, []);
    }
}