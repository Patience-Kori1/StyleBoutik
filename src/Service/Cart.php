<?php

namespace App\Service;

use App\Repository\ProductRepository;

class Cart
{
    // ProductRepository injecté automatiquement par Symfony (DI)
    // Il nous permet de faire des SELECT en BDD pour récupérer les objets Product
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    public function getCart($session): array
    {
        // Le panier en session est un tableau associatif [id_produit => quantite]
        // ex: [3 => 2, 7 => 1] — léger, on ne stocke pas d'objets en session
        // Si la clé 'cart' n'existe pas encore, on retourne un tableau vide
        $cart = $session->get('cart', []);

        // $cart ne contient que des IDs bruts — on ne peut pas afficher
        // les noms, prix ou images directement depuis la session
        // Ce tableau va enrichir chaque entrée avec l'objet Product complet
        $cartWithData = [];

        // $id = clé du tableau = identifiant du produit en BDD
        // $quantity = valeur du tableau = nombre d'exemplaires commandés
        foreach ($cart as $id => $quantity) {
            // find($id) fait un SELECT WHERE id = $id et retourne un objet Product
            // On recharge depuis la BDD pour avoir le prix et le stock à jour
            // (le prix peut avoir changé depuis l'ajout au panier)
            // $cartWithData[] — le [] sans index ajoute à la fin du tableau
            $cartWithData[] = [
                'product'  => $this->productRepository->find($id),
                'quantity' => $quantity,
            ];
        }

        // Calcul du total en deux étapes :
        // 1. array_map() transforme chaque item en un montant (prix × quantité)
        //    ex: [90 * 2, 79 * 1] → [180, 79]
        // 2. array_sum() additionne tous ces montants
        //    ex: [180, 79] → 259
        $total = array_sum(array_map(function ($item) {
            return $item['product']->getPrice() * $item['quantity'];
        }, $cartWithData));

        // On retourne les deux clés dont ont besoin CartController et OrderController
        // 'cart' → tableau enrichi pour l'affichage Twig
        // 'total' → montant total hors frais de livraison
        return [
            'cart'  => $cartWithData,
            'total' => $total,
        ];
    }
}