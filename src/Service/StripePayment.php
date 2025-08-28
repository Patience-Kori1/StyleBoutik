<?php
namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePayment
{
    private $redirectUrl;

    public function __construct()
    {
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']); //recupère la cle secrete dans le fichier .env gràce au $_server. En php on peut récuper une variable du .env en tapant $_SERVER
        Stripe::setApiVersion('2025-07-30.basil'); //on gère la version de Stripe
    }

    public function startPayment($cart, $shippingCost){
        // dd($cart);
        // Récupération des produits du panier
        $cartProducts = $cart['cart']; 
        // Initialisation d'un tableau vide pour stocker les produits formatés
        $products = [
             [ // Récupération à l'avance du prix de livraison selon la ville
                'qte' => 1, 
                'price' => $shippingCost,
                'name' => "Frais de livraison"
            ]
        ];

        // Boucle pour parcourir chaque produit du panier
        foreach ($cartProducts as $value) {
            // Initialisation d'un tableau vide pour stocker les informations d'un produit
            $productItem = [];
            // Récupération du nom du produit
            $productItem['name'] = $value['product']->getName();
            // Récupération du prix du produit
            $productItem['price'] = $value['product']->getPrice();
            // Récupération de la quantité du produit
            $productItem['qte'] = $value['quantity'];
            // Ajout du produit formaté au tableau des produits
            $products[] = $productItem;
        }
      $session = Session::create([ //création de la session Stripe
            'line_items'=>[  //tableau des produits qui vont etre payer
                array_map(fn(array $product) => [
                    'quantity' => $product['qte'],
                    'price_data' => [
                        'currency' => 'Eur',
                        'product_data' => [
                           'name' => $product['name']
                        ],
                        'unit_amount' => $product['price']*100, //prix donnée en centimes donc on multiplie
                    ],
                ],$products)
            ],
            'mode' => 'payment', //mode de paiement
            'cancel_url' => 'http://localhost:8000/pay/cancel', //si paiement annulé on redirige ici
            'success_url' => 'http://localhost:8000/pay/success', //si paiement réussi on redirige ici
            'billing_address_collection' => 'required', //si on autorise les factures
            'shipping_address_collection' => [ //tableau de pays où on souhaite autorisés le paiement
                'allowed_countries' => ['FR','EG'],
            ],
            'metadata' => [
                // 'orderid' =>$orderId//id de la commande
            ]
            
        ]); 
        $this->redirectUrl = $session->url; //redirection vers stripe pour le paiement
    }
    public function getStripeRedirectUrl(){ //permet de recuperer l'url de l'utilisateur pour stripe : Quand ton contrôleur appellera ce service, il pourra récupérer l’URL et faire une redirection vers Stripe.
        return $this->redirectUrl;
    }
}