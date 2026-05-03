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
        // On vide le panier ici aussi par sécurité — normalement il est déjà vide
        // si le webhook s'est exécuté correctement, mais c'est une double protection
        // Le client arrive sur cette page après redirection depuis Stripe
        $session->set('cart', []);

        return $this->render('stripe/stripeSuccess.html.twig');
    }

    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(): Response
    {
        // Le client a annulé le paiement sur la page Stripe
        // On ne vide PAS le panier — il peut vouloir réessayer avec un autre moyen
        // La commande existe en BDD mais isPaymentCompleted reste à 0
        return $this->render('stripe/stripeCancel.html.twig');
    }

    #[Route('/stripe/notify', name: 'app_stripe_notify')]
    public function stripeNotify(
        Request $request,
        OrderRepository $orderRepo,
        EntityManagerInterface $em
    ): Response {
        // Cette route est appelée par Stripe (pas par le navigateur du client)
        // C'est ce qu'on appelle un webhook — Stripe nous notifie qu'un paiement
        // s'est produit en envoyant une requête POST vers cette URL
        // En local, le Terminal 2 (Stripe CLI) crée le tunnel :
        // stripe listen --forward-to localhost:8000/stripe/notify

        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);

        // La clé webhook (whsec_...) est générée par Stripe CLI en local
        // En production elle viendrait du dashboard Stripe et serait dans .env.local
        // Elle sert à vérifier que la requête vient bien de Stripe et pas d'un attaquant
        $endpoint_secret = 'whsec_73e2ec576913aebbf8b975bdf7cded40382fa240795bfed902aa9fa3974071cc';

        // Le contenu brut de la requête POST envoyée par Stripe
        // On le récupère tel quel — ne pas le parser avant la vérification de signature
        $payload = $request->getContent();

        // En-tête HTTP envoyé par Stripe contenant la signature cryptographique
        // Stripe la génère avec notre endpoint_secret — personne d'autre ne peut la forger
        $sigHeader = $request->headers->get('Stripe-Signature');

        $event = null;

        try {
            // constructEvent() fait deux choses en même temps :
            // 1. Désérialise le payload JSON en objet PHP exploitable
            // 2. Vérifie que la signature correspond — si quelqu'un envoie
            //    un faux webhook pour valider une commande sans payer,
            //    cette vérification le bloque immédiatement
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Le payload JSON est malformé ou illisible
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // La signature ne correspond pas — requête suspecte ou falsifiée
            return new Response('Invalid signature', 400);
        }

        // Stripe peut envoyer de nombreux types d'événements
        // On ne traite que ceux qui nous intéressent
        switch ($event->type) {

            case 'payment_intent.succeeded':
                // Ce cas se déclenche quand un paiement est confirmé par Stripe
                // C'est ici qu'on valide la commande en BDD

                $paymentIntent = $event->data->object;

                // On récupère l'id de notre commande depuis les métadonnées
                // qu'on avait stockées dans StripePayment::startPayment()
                // C'est le seul moyen de faire le lien entre le paiement Stripe
                // et notre commande en BDD
                $orderId = $paymentIntent->metadata->orderId;
                $order   = $orderRepo->find($orderId);

                // DOUBLE VÉRIFICATION SÉCURITÉ — étape critique
                // On compare le montant stocké en BDD avec ce que Stripe dit avoir reçu
                // Stripe travaille en centimes donc on divise par 100 pour avoir des euros
                // Cette vérification empêche une manipulation du montant côté client :
                // si quelqu'un modifie le prix dans Stripe, les montants ne correspondront pas
                // et on ne validera pas la commande
                $cartPrice         = $order->getTotalPrice();
                $stripeTotalAmount = $paymentIntent->amount / 100;

                if ($cartPrice == $stripeTotalAmount) {
                    // Les montants correspondent — paiement légitime
                    // On marque la commande comme payée en BDD
                    // pas besoin de persist() — l'entité est déjà gérée par Doctrine
                    $order->setIsPaymentCompleted(1);
                    $em->flush();
                }

                break;

            case 'payment_method.attached':
                // Événement déclenché quand une carte est enregistrée
                // On ne l'utilise pas dans StyleBoutik mais on le laisse
                // pour ne pas déclencher le cas default
                $paymentMethod = $event->data->object;
                break;

            default:
                // Tous les autres événements Stripe sont ignorés
                break;
        }

        // On retourne 200 pour dire à Stripe "message bien reçu"
        // Si on retourne une erreur, Stripe réessaiera le webhook plusieurs fois
        // ce qui pourrait valider la commande plusieurs fois
        return new Response('Événement reçu avec succès', 200);
    }
}