<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepo )
    {
        

    }

    #[Route('/cart', name: 'app_cart', methods:['GET'])]
    public function cart(SessionInterface $session, ProductRepository $productRepo): Response
    {
        $cart= $session->get('cart',[]);
        $cartWithData = [];
        foreach ($cart as $id => $quantity) {
            $cartWithData[] = [
                'product' => $this->productRepo->find($id),
                'quantity' => $quantity
            ];
        }

        //Calcul du total du panier  
        $total = array_sum(array_map(function($item){
            return $item['product']->getPrice()* $item['quantity'];
        },$cartWithData));
        // dd($cartWithData);
        return $this->render('cart/cart.html.twig', [
            'items' => $cartWithData,
            'total' => $total,
        ]);
    }

    #[Route("/cart/add/{id}/", name: "app_cart_new", methods: ['GET'])]
    // Définit une route pour ajouter un produit au panier

    public function addProductToCart(int $id, SessionInterface $session): Response
    // Méthode pour ajouter un produit au panier, prend l'ID du produit et la session en paramètres

    {
        $cart = $session->get('cart', []);
        // Récupère le panier actuel de la session, ou un tableau vide si il n'existe pas
        if (!empty($cart[$id])){
            $cart[$id]++;
        }else{
            $cart[$id]=1;
        }
        // Si le produit est déjà dans le panier, incrémente sa quantité, sinon l'ajoute avec une quantité de 1
        $session->set('cart',$cart);
        // Met à jour le panier dans la session
        return $this->redirectToRoute('app_cart');
        // Redirige vers la page du panier
    }

}
