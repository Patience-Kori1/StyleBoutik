<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Form\OrderType;
use App\Entity\OrderProducts;
use App\Service\StripePayment;
use Symfony\Component\Mime\Email;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OrderController extends AbstractController
{
    // MailerInterface injecté dans le constructeur plutôt qu'en paramètre de méthode
    // car Symfony ne peut pas injecter des services qui nécessitent une configuration
    // spéciale (comme le transport SMTP) directement dans les méthodes d'action
    public function __construct(private MailerInterface $mailer)
    {
    }

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepo,
        EntityManagerInterface $em,
        Cart $cart
    ): Response {
        // Cart::getCart() lit la session et enrichit chaque entrée avec
        // les vrais objets Product depuis la BDD — on récupère le panier
        // sous la forme ['cart' => [...], 'total' => float]
        $data = $cart->getCart($session);

        $order = new Order();

        // createForm() lie le formulaire à l'entité Order
        // handleRequest() lit les données POST et les mappe sur les setters de l'entité :
        // setFirstName(), setLastName(), setEmail(), setCity(), setPayOnDelivery()...
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Sécurité basique : on refuse de créer une commande si le panier est vide
            // Cela peut arriver si quelqu'un accède directement à /order sans passer par le panier
            if (!empty($data['total'])) {

                // getCity() retourne l'objet City lié à la commande (sélectionné dans le formulaire)
                // getShippingCost() retourne le double — ex: 15.0 pour Pessac
                // On additionne le sous-total panier et les frais pour avoir le prix final
                $totalPrice = $data['total'] + $order->getCity()->getShippingCost();
                $order->setTotalPrice($totalPrice);
                $order->setCreatedAt(new \DateTimeImmutable());

                // isPaymentCompleted reste à 0 jusqu'à la confirmation du webhook Stripe
                // Pour les commandes en livraison il restera à 0 — c'est normal
                // car il n'y a pas de paiement en ligne à confirmer
                $order->setIsPaymentCompleted(0);

                // Premier flush avant la boucle pour que Doctrine génère un id à $order
                // Sans cet id on ne pourrait pas lier les OrderProducts à la bonne commande
                $em->persist($order);
                $em->flush();

                // Chaque produit du panier devient une ligne dans la table order_products
                // Cette table fait le lien entre une commande et ses produits
                // avec la quantité commandée — c'est la relation OneToMany entre Order et OrderProducts
                foreach ($data['cart'] as $value) {
                    $orderProduct = new OrderProducts();

                    // setOrderedProducts() relie cette ligne à la commande parente
                    // Doctrine renseignera la FK ordered_products_id en BDD
                    $orderProduct->setOrderedProducts($order);
                    $orderProduct->setProduct($value['product']);
                    $orderProduct->setQuantity($value['quantity']);
                    $em->persist($orderProduct);
                    $em->flush();
                }

                // ── BIFURCATION ──
                // isPayOnDelivery() retourne le booléen du champ coché dans le formulaire
                // true  → le client paie à la réception — on envoie un email et c'est tout
                // false → le client paie en ligne — on le redirige vers Stripe

                if ($order->isPayOnDelivery()) {

                    // On vide le panier immédiatement pour la livraison
                    // Pour Stripe on ne le vide pas ici — si le paiement échoue
                    // le client doit pouvoir réessayer sans reconstruire son panier
                    $session->set('cart', []);

                    // renderView() retourne le HTML du template Twig sous forme de string
                    // contrairement à render() qui retourne une Response HTTP complète
                    // On passe $order après le flush pour avoir accès à ses données
                    // complètes : id, produits liés, ville, total, email du client...
                    $html = $this->renderView('email/orderConfirm.html.twig', [
                        'order' => $order,
                    ]);

                    // On construit l'email avec la classe Email de Symfony (Mime component)
                    // from() → expéditeur affiché dans la boîte du client
                    // to()   → adresse saisie par le client dans le formulaire de commande
                    $email = (new Email())
                        ->from('sneakhub@gmailcom')
                        ->to($order->getEmail())
                        ->subject('Confirmation de réception de commande')
                        ->html($html);

                    // mailer->send() ne fait pas un envoi SMTP direct
                    // Symfony Messenger intercepte l'appel et stocke l'email sérialisé
                    // dans la table messenger_messages en BDD
                    // Le worker (Terminal 3 : php bin/console messenger:consume async)
                    // lit cette table toutes les secondes et envoie réellement via MailTrap
                    $this->mailer->send($email);

                    return $this->redirectToRoute('app_order_message');
                }

                // ── CHEMIN STRIPE ──
                // On instancie le service StripePayment qui encapsule
                // toute la communication avec l'API Stripe
                $paymentStripe = new StripePayment();
                $shippingCost  = $order->getCity()->getShippingCost();

                // startPayment() formate le panier pour Stripe et crée une Session Checkout
                // L'id de commande est passé en métadonnée Stripe pour pouvoir
                // identifier la commande quand le webhook payment_intent.succeeded arrive
                $paymentStripe->startPayment($data, $shippingCost, $order->getId());

                // getStripeRedirectUrl() retourne l'URL unique de la page Stripe
                // Le client quitte notre site pour payer sur checkout.stripe.com
                $stripeRedirectUrl = $paymentStripe->getStripeRedirectUrl();

                return $this->redirect($stripeRedirectUrl);
            }
        }

        // Si GET ou formulaire invalide — on affiche le formulaire de commande
        // On passe le total pour l'afficher dans le récapitulatif côté Twig
        // avant que le client choisisse sa ville (les frais s'ajoutent via AJAX)
        return $this->render('order/orderIndex.html.twig', [
            'form'  => $form->createView(),
            'total' => $data['total'],
        ]);
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        // Route appelée en AJAX par jQuery dans orderIndex.html.twig
        // quand le client change de ville dans le select du formulaire
        // Doctrine injecte l'objet City directement via le ParamConverter
        // qui lit {id} dans l'URL et fait le SELECT automatiquement
        $cityShippingPrice = $city->getShippingCost();

        // On retourne du JSON — pas de template Twig ici
        // jQuery reçoit cette réponse et met à jour l'affichage des frais
        // et du total sans recharger la page
        return new Response(json_encode([
            'status'  => 200,
            'content' => $cityShippingPrice,
        ]));
    }

    #[Route('/order_message', name: 'app_order_message')]
    public function orderMessage(): Response
    {
        // Page de confirmation pour le mode paiement à la livraison
        // On arrive ici après la redirection dans index()
        // Le panier est déjà vide et la commande est enregistrée en BDD
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/editor/order/{type}/', name: 'app_orders_show')]
    public function getAllOrder(
        $type,
        OrderRepository $orderRepo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        // On utilise findBy() avec différents critères selon le filtre demandé
        // Premier paramètre  → critères WHERE (tableau vide = pas de filtre)
        // Deuxième paramètre → ORDER BY (id DESC = plus récente en premier)
        // Les types correspondent exactement aux paramètres passés dans les liens de la navbar

        if ($type == 'all') {
            $data = $orderRepo->findBy([], ['id' => 'DESC']);

        } elseif ($type == 'is-completed') {
            // isCompleted = 1 → commande physiquement livrée au client
            $data = $orderRepo->findBy(['isCompleted' => 1], ['id' => 'DESC']);

        } elseif ($type == 'is-not-completed') {
            // isCompleted = 0 → commande en attente de livraison
            $data = $orderRepo->findBy(['isCompleted' => 0], ['id' => 'DESC']);

        } elseif ($type == 'pay-on-stripe-not-delivered') {
            // Commandes Stripe payées (isPaymentCompleted = 1)
            // mais pas encore livrées (isCompleted = 0)
            // payOnDelivery = 0 confirme que c'est bien un paiement Stripe
            // et non une commande en livraison dont isPaymentCompleted resterait à 0
            $data = $orderRepo->findBy(
                ['isCompleted' => 0, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1],
                ['id' => 'DESC']
            );

        } elseif ($type == 'pay-on-stripe-is-delivered') {
            // Commandes Stripe payées ET physiquement livrées — cycle complet
            $data = $orderRepo->findBy(
                ['isCompleted' => 1, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1],
                ['id' => 'DESC']
            );
        }

        // paginate() reçoit le tableau complet et retourne
        // uniquement les 3 commandes de la page demandée
        // getInt('page', 1) lit ?page=2 dans l'URL ou retourne 1 si absent
        $orders = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('order/orders.html.twig', [
            'orders' => $orders,
            // On repasse $type à la vue pour mettre en surbrillance
            // le filtre actif dans la navbar de l'espace éditeur
            'type'   => $type,
        ]);
    }

    #[Route('/editor/order/{id}/is-completed/update', name: 'app_orders_is-completed-update')]
    public function isCompletedUpdate(
        Request $request,
        $id,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $order = $orderRepository->find($id);

        // Système de toggle — un clic marque livrée, un autre clic annule
        // Pas besoin de persist() car Doctrine tracked déjà cet objet
        // (il vient d'être chargé par find() — il est en "managed state")
        // Un simple flush() suffit pour enregistrer le changement
        if ($order->isCompleted() !== true) {
            $order->setIsCompleted(true);
            $entityManager->flush();
            $this->addFlash('success', 'Commande livrée');

        } elseif ($order->isCompleted() !== false) {
            $order->setIsCompleted(false);
            $entityManager->flush();
            $this->addFlash('danger', 'Commande pas encore livrée');
        }

        // getReferer() retourne l'URL de la page d'où vient la requête
        // Cela permet de rester sur le même filtre de commandes après le toggle
        // plutôt que de toujours revenir sur le filtre 'all'
        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $em): Response
    {
        // ParamConverter : Doctrine lit {id} dans l'URL, charge l'objet Order
        // et le retourne directement en paramètre — pas besoin de find() manuellement
        // Si l'id n'existe pas en BDD, Doctrine retourne automatiquement une 404

        // remove() marque l'entité pour suppression
        // flush() exécute le DELETE FROM `order` WHERE id = {id}
        // Les OrderProducts liés sont supprimés en cascade (orphanRemoval: true dans l'entité)
        $em->remove($order);
        $em->flush();

        $this->addFlash('danger', 'Commande supprimée');

        // On passe 'all' implicitement via la route par défaut
        return $this->redirectToRoute('app_orders_show', ['type' => 'all']);
    }
}