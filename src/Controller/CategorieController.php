<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategorieController extends AbstractController
{
    #[Route('/allCategories', name: 'app_allCategories')]
    public function index(): Response
    {
        return $this->render('categorie/allCategories.html.twig', [
            'controller_name' => 'CategorieController',
        ]);
    }
}
