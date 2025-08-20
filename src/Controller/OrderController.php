<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Form\OrderType;
use App\Entity\OrderProducts;
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
                
                //Redirection vers la page du panier qui normalement et remise à zéro
                return $this->redirectToRoute('app_cart');
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
