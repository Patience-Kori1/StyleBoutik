<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepo): Response
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
        return $this->render('order/orderIndex.html.twig', [
            'form'=>$form->createView(),
            'total'=> $total
        ]);
    }
}
