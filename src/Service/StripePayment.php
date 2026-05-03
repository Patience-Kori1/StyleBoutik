<?php
namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePayment
{
    // L'URL de redirection vers la page Stripe Checkout
    // Elle est stockée ici après startPayment() pour être récupérée
    // par OrderController via getStripeRedirectUrl()
    private $redirectUrl;

    public function __construct()
    {
        // $_SERVER permet d'accéder aux variables d'environnement définies dans .env.local
        // La clé secrète Stripe ne doit jamais être écrite en dur dans le code
        // Elle est dans .env.local qui est dans .gitignore — jamais publiée sur GitHub
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);

        // On fixe la version de l'API Stripe pour éviter les breaking changes
        // si Stripe sort une nouvelle version avec des changements incompatibles
        Stripe::setApiVersion('2025-07-30.basil');
    }

    public function startPayment($cart, $shippingCost, $orderId)
    {
        // $cart est le tableau retourné par Cart::getCart()
        // Il a deux clés : 'cart' (les produits enrichis) et 'total' (le montant)
        // On extrait uniquement les produits ici
        $cartProducts = $cart['cart'];

        // Stripe attend un tableau de line_items — un par article à payer
        // On initialise avec les frais de livraison en premier
        // car ils doivent apparaître sur la page Stripe comme une ligne à part
        $products = [
            [
                'qte'   => 1,
                'price' => $shippingCost, // ex: 15 pour Pessac, 30 pour Bordeaux
                'name'  => 'Frais de livraison',
            ]
        ];

        // On formate chaque produit du panier pour l'API Stripe
        // Stripe ne connaît pas nos objets Product — il faut lui envoyer
        // un tableau simple avec name, price et qte
        foreach ($cartProducts as $value) {
            $productItem          = [];
            $productItem['name']  = $value['product']->getName();
            $productItem['price'] = $value['product']->getPrice();
            $productItem['qte']   = $value['quantity'];
            $products[]           = $productItem;
        }

        // Session::create() appelle l'API Stripe et génère une page de paiement unique
        // Stripe retourne une session avec une URL vers laquelle on redirige le client
        $session = Session::create([

            // line_items = les articles qui apparaissent sur la page de paiement Stripe
            // array_map() transforme notre tableau $products en format attendu par Stripe
            'line_items' => [
                array_map(fn(array $product) => [
                    'quantity'   => $product['qte'],
                    'price_data' => [
                        'currency'     => 'Eur',
                        'product_data' => [
                            'name' => $product['name'],
                        ],
                        // IMPORTANT : Stripe travaille en centimes pas en euros
                        // 90€ → on envoie 9000 à Stripe
                        // Sans ce *100 les prix seraient divisés par 100 côté Stripe
                        'unit_amount'  => $product['price'] * 100,
                    ],
                ], $products)
            ],

            // 'payment' = paiement unique (par opposition à 'subscription' pour abonnement)
            'mode' => 'payment',

            // Si le client annule sur Stripe, il revient sur cette URL
            'cancel_url'  => 'http://localhost:8000/pay/cancel',

            // Si le paiement réussit, Stripe redirige vers cette URL
            // Le webhook payment_intent.succeeded sera aussi déclenché en parallèle
            'success_url' => 'http://localhost:8000/pay/success',

            // Force Stripe à collecter l'adresse de facturation du client
            'billing_address_collection' => 'required',

            // Pays autorisés pour la livraison sur la page Stripe
            'shipping_address_collection' => [
                'allowed_countries' => ['FR', 'EG'],
            ],

            // Les métadonnées sont des données custom qu'on attache au paiement
            // On stocke l'id de notre commande pour pouvoir la retrouver
            // dans StripeController quand le webhook arrive
            // Sans ça on ne saurait pas quelle commande valider après le paiement
            'payment_intent_data' => [
                'metadata' => [
                    'orderId' => $orderId,
                ]
            ],
        ]);

        // On sauvegarde l'URL Stripe dans la propriété de classe
        // pour que OrderController puisse la récupérer via getStripeRedirectUrl()
        $this->redirectUrl = $session->url;
    }

    // Getter simple — OrderController appelle cette méthode après startPayment()
    // pour récupérer l'URL et faire la redirection vers Stripe
    public function getStripeRedirectUrl()
    {
        return $this->redirectUrl;
    }
}