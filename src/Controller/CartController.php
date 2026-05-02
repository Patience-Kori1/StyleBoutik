<?php

namespace App\Controller;

use App\Service\Cart;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CartController extends AbstractController
{
    // ProductRepository injecté dans le constructeur pour être disponible
    // dans toutes les méthodes si besoin — bonne pratique Symfony
    public function __construct(private readonly ProductRepository $productRepo)
    {
    }

    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function cart(SessionInterface $session, Cart $cart): Response
    {
        // Le service Cart fait le travail lourd :
        // il lit le panier brut de la session (juste des ids + quantités)
        // et l'enrichit avec les vrais objets Product depuis la BDD
        // Il calcule aussi le total — on n'a rien à faire ici
        $data = $cart->getCart($session);

        return $this->render('cart/cart.html.twig', [
            'items' => $data['cart'],  // tableau d'objets Product avec quantités
            'total' => $data['total'], // total en euros, hors frais de livraison
        ]);
    }

    #[Route('/cart/add/{id}/', name: 'app_cart_new', methods: ['GET'])]
    public function addProductToCart(int $id, SessionInterface $session): Response
    {
        // La session stocke le panier comme un tableau associatif simple :
        // [id_produit => quantite] — ex: [3 => 2, 7 => 1]
        // Si le panier n'existe pas encore en session, on part d'un tableau vide
        $cart = $session->get('cart', []);

        // Si ce produit est déjà dans le panier on incrémente sa quantité
        // sinon on l'ajoute avec une quantité de 1
        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }

        // On écrase l'ancien panier par le nouveau dans la session
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}/', name: 'app_cart_product_remove', methods: ['GET'])]
    public function removeToCart($id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        // unset() supprime une clé d'un tableau PHP
        // on vérifie d'abord que le produit est bien dans le panier
        // pour éviter une erreur si l'id n'existe pas
        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove', name: 'app_cart_remove', methods: ['GET'])]
    public function remove(SessionInterface $session): Response
    {
        // On remplace le panier par un tableau vide — c'est tout ce qu'il
        // faut pour vider le panier, pas besoin de remove() ou clear()
        $session->set('cart', []);

        return $this->redirectToRoute('app_cart');
    }
}