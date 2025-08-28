<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StripeController extends AbstractController
{
    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(SessionInterface $session): Response
    {
         $session->set('cart',[]);
        return $this->render('stripe/stripeSuccess.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/stripeCancel.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

    #[Route('/stripe/notify', name: 'app_stripe_notify')]
    public function stripeNotify(Request $request, OrderRepository $orderRepo, EntityManagerInterface $em): Response
    {
        // Définir la clé secrète de Stripe à partir de la variable d'environnement
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);
        
        // Définir la clé de webhook de Stripe
        $endpoint_secret = 'whsec_73e2ec576913aebbf8b975bdf7cded40382fa240795bfed902aa9fa3974071cc';
        // Récupérer le contenu de la requête
        $payload = $request->getContent();
        // Récupérer l'en-tête de signature de la requête
        $sigHeader = $request->headers->get('Stripe-Signature');
        // Initialiser l'événement à null
        $event = null;

        try {
            // Construire l'événement à partir de la requête et de la signature
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Retourner une erreur 400 si le payload est invalide
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Retourner une erreur 400 si la signature est invalide
            return new Response('Invalid signature', 400);
        }
        
        // Gérer les différents types d'événements
        switch ($event->type) {
            case 'payment_intent.succeeded':  // Événement de paiement réussi
                // Récupérer l'objet payment_intent
                $paymentIntent = $event->data->object;
                
                // Enregistrer les détails du paiement dans un fichier
                $fileName = 'stripe-detail-'.uniqid().'.txt';  
                $orderId = $paymentIntent->metadata->orderId; // Récupère l'id dans Stripe
                $order = $orderRepo->find($orderId); //récupère l'id dans l'entity

                //Vérification du prix total de l'entity order et du stripe sont égaux
                $cartPrice = $order->getTotalPrice();
                $stripeTotalAmount = $paymentIntent->amount/100;
                if($cartPrice==$stripeTotalAmount){
                    $order->setIsPaymentCompleted(1);
                    $em->flush();
                }


                file_put_contents($fileName, $orderId);//affiche dans le fichier Stripe de public l'id de la commande
                break;
            case 'payment_method.attached':   // Événement de méthode de paiement attachée
                // Récupérer l'objet payment_method
                $paymentMethod = $event->data->object; 
                break;
            default :
                // Ne rien faire pour les autres types d'événements
                break;
        }

        // Retourner une réponse 200 pour indiquer que l'événement a été reçu avec succès
        return new Response('Événement reçu avec succès', 200);
    }
    
}
