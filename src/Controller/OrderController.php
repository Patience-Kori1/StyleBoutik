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
    // MailerInterface injecté dans le constructeur car utilisé uniquement
    // dans index() pour l'envoi de l'email de confirmation livraison
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
        // On récupère le panier enrichi (objets Product + quantités + total)
        // via le service Cart qui lit la session et interroge la BDD
        $data = $cart->getCart($session);

        $order = new Order();

        // createForm() lie le formulaire HTML à l'entité Order
        // Symfony va mapper automatiquement les champs du formulaire
        // sur les setters de l'entité (setFirstName, setCity, etc.)
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // On vérifie que le panier n'est pas vide avant de créer la commande
            // Un panier vide aurait un total à 0
            if (!empty($data['total'])) {

                // Total final = sous-total panier + frais de livraison de la ville choisie
                // getShippingCost() vient de l'entité City liée à l'entité Order
                $totalPrice = $data['total'] + $order->getCity()->getShippingCost();
                $order->setTotalPrice($totalPrice);
                $order->setCreatedAt(new \DateTimeImmutable());

                // On initialise isPaymentCompleted à 0 (false)
                // Il passera à 1 uniquement quand le webhook Stripe confirmera le paiement
                $order->setIsPaymentCompleted(0);

                $em->persist($order);
                $em->flush(); // flush ici pour obtenir l'id de la commande avant la boucle

                // Chaque produit du panier devient une ligne dans order_products
                // On crée un OrderProducts par article pour tracer quantités et prix
                foreach ($data['cart'] as $value) {
                    $orderProduct = new OrderProducts();
                    $orderProduct->setOrderedProducts($order);
                    $orderProduct->setProduct($value['product']);
                    $orderProduct->setQuantity($value['quantity']);
                    $em->persist($orderProduct);
                    $em->flush();
                }

                // ── BIFURCATION selon le mode de paiement choisi ──

                if ($order->isPayOnDelivery()) {

                    // Mode livraison : on vide le panier, on envoie un email
                    // de confirmation et on redirige vers la page de confirmation
                    $session->set('cart', []);

                    // renderView() génère le HTML de l'email depuis un template Twig
                    // On passe $order après le flush pour avoir toutes ses données
                    // (id, produits, ville, total, etc.)
                    $html = $this->renderView('email/orderConfirm.html.twig', [
                        'order' => $order,
                    ]);

                    // Symfony Messenger intercepte cet envoi et le place
                    // en file d'attente dans la table messenger_messages
                    // Le worker (php bin/console messenger:consume async) l'envoie réellement
                    $email = (new Email())
                        ->from('sneakhub@gmailcom')
                        ->to($order->getEmail())
                        ->subject('Confirmation de réception de commande')
                        ->html($html);

                    $this->mailer->send($email);

                    return $this->redirectToRoute('app_order_message');
                }

                // Mode Stripe : on crée une session Checkout sur les serveurs Stripe
                // avec les articles du panier, les frais de livraison et l'id de commande
                // Stripe nous retourne une URL vers laquelle on redirige le client
                $paymentStripe = new StripePayment();
                $shippingCost  = $order->getCity()->getShippingCost();

                // On passe l'id de la commande en métadonnée Stripe
                // pour pouvoir retrouver la commande dans le webhook
                $paymentStripe->startPayment($data, $shippingCost, $order->getId());
                $stripeRedirectUrl = $paymentStripe->getStripeRedirectUrl();

                return $this->redirect($stripeRedirectUrl);
            }
        }

        return $this->render('order/orderIndex.html.twig', [
            'form'  => $form->createView(),
            'total' => $data['total'],
        ]);
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        // Endpoint appelé en AJAX depuis orderIndex.html.twig via jQuery
        // quand le client change de ville dans le formulaire de commande
        // Doctrine injecte automatiquement l'objet City grâce à l'id dans l'URL
        $cityShippingPrice = $city->getShippingCost();

        return new Response(json_encode([
            'status'  => 200,
            'content' => $cityShippingPrice, // ex: 15 pour Pessac, 30 pour Bordeaux
        ]));
    }

    #[Route('/order_message', name: 'app_order_message')]
    public function orderMessage(): Response
    {
        // Page de confirmation affichée après une commande en livraison
        // Le panier a déjà été vidé dans index() avant la redirection
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/editor/order/{type}/', name: 'app_orders_show')]
    public function getAllOrder(
        $type,
        OrderRepository $orderRepo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        // findBy() permet de filtrer les commandes selon les champs de l'entité Order
        // Le deuxième paramètre ['id' => 'DESC'] trie par id décroissant (plus récent en premier)
        // Les différents types correspondent aux liens de la navbar éditeur

        if ($type == 'all') {
            // Toutes les commandes sans filtre
            $data = $orderRepo->findBy([], ['id' => 'DESC']);

        } elseif ($type == 'is-completed') {
            // Commandes marquées comme livrées (isCompleted = 1)
            $data = $orderRepo->findBy(['isCompleted' => 1], ['id' => 'DESC']);

        } elseif ($type == 'is-not-completed') {
            // Commandes pas encore livrées (isCompleted = 0)
            $data = $orderRepo->findBy(['isCompleted' => 0], ['id' => 'DESC']);

        } elseif ($type == 'pay-on-stripe-not-delivered') {
            // Paiement Stripe confirmé (isPaymentCompleted = 1)
            // mais pas encore physiquement livrées (isCompleted = 0)
            $data = $orderRepo->findBy(
                ['isCompleted' => 0, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1],
                ['id' => 'DESC']
            );

        } elseif ($type == 'pay-on-stripe-is-delivered') {
            // Paiement Stripe confirmé ET commande livrée
            $data = $orderRepo->findBy(
                ['isCompleted' => 1, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1],
                ['id' => 'DESC']
            );
        }

        // KnpPaginator découpe $data en pages de 3 commandes
        // $request->query->getInt('page', 1) lit le paramètre ?page=X dans l'URL
        $orders = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('order/orders.html.twig', [
            'orders' => $orders,
            'type'   => $type, // on passe le type pour gérer l'affichage actif dans la navbar
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

        // Toggle du statut de livraison — si pas livrée on marque livrée et inversement
        // Pas besoin de persist() ici car l'entité est déjà gérée par Doctrine (managed state)
        if ($order->isCompleted() !== true) {
            $order->setIsCompleted(true);
            $entityManager->flush();
            $this->addFlash('success', 'Commande livrée');
        } elseif ($order->isCompleted() !== false) {
            $order->setIsCompleted(false);
            $entityManager->flush();
            $this->addFlash('danger', 'Commande pas encore livrée');
        }

        // headers->get('referer') retourne l'URL de la page précédente
        // On reste sur le même filtre de commandes au lieu de revenir sur 'all'
        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $em): Response
    {
        // Doctrine injecte directement l'objet Order via l'id dans l'URL (ParamConverter)
        // remove() + flush() → DELETE FROM `order` WHERE id = $id
        $em->remove($order);
        $em->flush();

        $this->addFlash('danger', 'Commande supprimée');

        return $this->redirectToRoute('app_orders_show');
    }
}