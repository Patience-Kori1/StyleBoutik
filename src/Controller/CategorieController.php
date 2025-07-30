<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategoryType;
use App\Form\UpdateFormType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class CategorieController extends AbstractController
{
    #[Route('/admin/allCategories', name: 'app_allCategories')]
    public function allCategories (CategorieRepository $repo): Response
    {
        $categories = $repo->findAll();
        
        return $this->render('categorie/allCategories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/category/new', name: 'app_category_new')]
    public function addCategory(EntityManagerInterface $em, Request $request): Response
    {
        $category = new Categorie();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'La catégorie a bien été ajouté');
            return $this->redirectToRoute('app_allCategories');
        }

        return $this->render('categorie/newCategorie.html.twig', [
            'form' => $form->createView()
        ]);

    }

        #[Route('/admin/category/update/{id}', name : 'app_update_category')]
        public function updateCategory (CategorieRepository $repo, EntityManagerInterface $em, $id, Request $request) : Response 
        {
            $category = $repo->find($id);
            $form = $this->createForm(UpdateFormType::class, $category);
            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()) {
                $em->persist($category);
                $em-> flush();

                $this->addFlash('success', 'La catégorie a été modifiée avec succès');
                return $this->redirectToRoute('app_allCategories');
            }

            return $this->render('categorie/updateCategorie.html.twig', [
                'form' => $form->createView(),
                // 'categorie'=> $id
            ]);
        }

        #[Route('/admin/category/delete/{id}', name: 'app_delete_category')]
        public function deleteForm (EntityManagerInterface $em, $id, CategorieRepository $repo)
        {
            $category = $repo->find($id);
            $em->remove($category);
            $em->flush();

            $this->addFlash('danger', 'La catégorie a été supprimée avec succès');
            return $this->redirectToRoute('app_allCategories');

        }
}

