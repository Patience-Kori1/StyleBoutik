<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OrderController extends AbstractController
{
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

        if ($form->isSubmitted() && $form->isValid()) {
             if($order->isPayOnDelivery()) {

                $order->setTotalPrice($data['total']);
                // Définit la date de création de la commande
                $order->setCreatedAt(new \DateTimeImmutable());
                $em->persist($order);
                $em->flush();
            }
            // return $this->redirectToRoute('app_sub_category_index', [], Response::HTTP_SEE_OTHER);
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
}
