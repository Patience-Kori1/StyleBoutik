<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategorieRepository;
use App\Repository\SubCategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function homePage(ProductRepository $productRepo, CategorieRepository $categoryRepo): Response
    {
        $products= $productRepo->findAll();
       
        return $this->render('home_page/homePage.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/product/{id}/show ', name: 'app_home_product_show', methods: ['GET'])]
    public function index(ProductRepository $productRepo, $id): Response
    {
        $product= $productRepo->find($id);
        $lastProductsAdd = $productRepo->findBy([],['id'=>'DESC'],5);//on crée la variable a laquelle on donne le repo et la methode findBy, puis on donne une limit de 5 en affichage
       
        return $this->render('home_page/homeShowProduct.html.twig', [
            'product' => $product,
            'products'=>$lastProductsAdd,
        ]);
    }

     #[Route('/product/subcategory/{id}/filter ', name: 'app_home_product_filter', methods: ['GET'])]
    public function filter($id, SubCategoryRepository $subCategoryRepository): Response //ici on recupere l'id et la repo des sous catégories
    
    {
        
        return $this->render('home_page/filter.html.twig', [ //il faut bien sur créer ce fichier
            
        ]);
    }
}
