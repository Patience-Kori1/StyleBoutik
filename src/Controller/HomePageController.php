<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function homePage(ProductRepository $productRepo): Response
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
       
        return $this->render('home_page/homeShowProduct.html.twig', [
            'product' => $product,
        ]);
    }
}
