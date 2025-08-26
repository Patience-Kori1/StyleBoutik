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
    public function __construct(private MailerInterface $mailer){
    }

    #[Route('/order', name: 'app_order')]
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepo, EntityManagerInterface $em, Cart $cart): Response
    {
        // Récupère les données du panier à partir de la session using le service Cart
        $data = $cart->getCart($session);

        // Crée un nouvel objet Order
        $order = new Order();
        
        // Crée un formulaire pour gérer la création de la commande en utilisant le type de formulaire OrderType
        $form= $this->createForm(OrderType::class, $order);
        // dd($order);
        // Gère la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            if($order->isPayOnDelivery()) 
            {

                // Vérifie si le total du panier n'est pas vide pour savoir si le panier n'est pas vide
                if(!empty($data['total'])) {
                    $order->setTotalPrice($data['total']);
                    // Définit la date de création de la commande
                    $order->setCreatedAt(new \DateTimeImmutable());
                    $em->persist($order);
                    // $em->flush();
                    // dd($data['cart']);

                    foreach($data['cart'] as $value) {
                        // Crée un nouvel objet OrderProducts
                        $orderProduct = new OrderProducts();
                        // Définit la commande pour le produit de la commande
                        $orderProduct->setOrderedProducts($order);
                        // Définit le produit pour le produit de la commande
                        $orderProduct->setProduct($value['product']);
                        // Définit la quantité pour le produit de la commande
                        $orderProduct->setQuantity($value['quantity']);
                        // Enregistre le produit de la commande dans la base de données
                        $em->persist($orderProduct);
                        // $em->flush();
                    }
                    //Nettoyer la logique de persistance (un seul flush à la fin).
                    $em->flush();
                }
                
                // Remise à zéro du contenu du panier en session après chaque soumission
                $session->set('cart',[]);

                // Gestion du mail de la confirmation de commande

                $html = $this->renderView('email/orderConfirm.html.twig',[ //crée une vue mail
                    'order'=>$order //on recupere le $order apres le flush donc on a toutes les infos           
                ]);
                $email = (new Email()) //On importe la classe depuis Symfony\Component\Mime\Email;
                ->from('sneakhub@gmailcom') //Adresse de l'expéditeur donc notre boutique ou vous mêmes
                // ->to('to@gmailcom') //Adresse du receveur
                ->to($order->getEmail())
                ->subject('Confirmation de réception de commande') //Intitulé du mail
                ->html($html); // Une fonction et un contructeur ont été créer en haut pour gérer l'envoi du mail
                $this->mailer->send($email);
                
                //Redirection vers la page du panier qui normalement et remise à zéro
                return $this->redirectToRoute('app_order_message');
            }
             // quand c'est false
                $paymentStripe = new StripePayment(); //on importe notre service avec sa classe
                $paymentStripe->startPayment($data); //on importe le panier donc $data
        }

        return $this->render('order/orderIndex.html.twig', [
            'form'=>$form->createView(),
            'total'=> $data['total'],
        ]);
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        
        $cityShippingPrice = $city->getShippingCost();
        // dd($cityShippingPrice);
        return new Response(json_encode(['status'=>200, "message"=>'on', 'content'=> $cityShippingPrice]));

    }

    #[Route('/order_message', name:'app_order_message')]
    public function orderMessage() : Response 
    {
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/editor/order', name:'app_orders_show')]
    public function getAllOrder(OrderRepository $orderRepo, Request $request, PaginatorInterface $paginator) : Response
    {
        $orders = $orderRepo->findAll(); 
        $data = $orderRepo->findBy([],['id'=>'DESC']);
        $orders = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),//met en place la pagination
            3 //je choisi la limite de 3 commandes par page
        );   
        return $this->render('order/orders.html.twig', [
            'orders'=>$orders
        ]);
    }

    #[Route('/editor/order/{id}/is-completed/update', name: 'app_orders_is-completed-update')]
    public function isCompletedUpdate(Request $request, $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager):Response
    {
        $order = $orderRepository->find($id);

        if($order->isCompleted() !== true )
        {
            $order->setIsCompleted(true);
            $entityManager->flush();
            $this->addFlash('success', 'Commande livrée');
        } 

        elseif ($order->isCompleted() !== false) 
        {
            $order->setIsCompleted(false);
            $entityManager->flush();
            $this->addFlash('danger', 'Commande pas encore livrée');
        }
        
        return $this->redirectToRoute('app_orders_show');

        // return $this->redirect($request->headers->get('referer'));//cela fait reference a la route precedent cette route ci
    }

    #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $em):Response 
    {
        $em->remove($order);
        $em->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirectToRoute('app_orders_show');
    }
}
