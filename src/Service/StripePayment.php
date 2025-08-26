<?php
namespace App\Service;

use Stripe\Stripe;

class StripePayment
{
    public $redirectUrl;

    public function __construct()
    {
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']); //recupère la cle secrete dans le fichier .env gràce au $_server. En php on peut récuper une variable du .env en tapant $_SERVER
        Stripe::setApiVersion('2025-07-30.basil'); //on gère la version de Stripe
    }

    public function startPayment($cart){
        // dd($cart);

    }
}