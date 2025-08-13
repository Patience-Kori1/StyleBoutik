<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
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
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepo, EntityManagerInterface $em): Response
    {
        $cart= $session->get('cart',[]);
        $cartWithData = [];
        foreach ($cart as $id => $quantity) {
            $cartWithData[] = [
                'product' => $productRepo->find($id),
                'quantity' => $quantity
            ];  
        }

        //Calcul du total du panier  
        $total = array_sum(array_map(function($item){
            return $item['product']->getPrice()* $item['quantity'];
        },$cartWithData));
        // dd($cartWithData);

        $order= new Order;
        $form= $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($order);
            $em->flush();

            return $this->redirectToRoute('app_sub_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/orderIndex.html.twig', [
            'form'=>$form->createView(),
            'total'=> $total
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
