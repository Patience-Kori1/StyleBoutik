<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use App\Form\ProductType;
use App\Entity\AddProductHistory;
use App\Form\AddProductHistoryType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\AddProductHistoryRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


    #[Route('editor/product')]
    final class ProductController extends AbstractController
    {
        #[Route(name: 'app_product_index', methods: ['GET'])]
        public function index(ProductRepository $productRepository): Response
        {
            return $this->render('product/allProducts.html.twig', [
                'products' => $productRepository->findAll(),
            ]);
        }
 
    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

             $image = $form->get('image')->getData();/* on recup l'image et son contenu*/
   
            if ($image) {/*si l'image existe*/
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeImageName = $slugger->slug($originalName);/* permet de recup des image avec espace dans le nom et l'enlever*/
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();/*cree un id unique a toute les images meme si elles ont un nom similaire*/

                try {
                    $image->move
                        ($this->getParameter('image_directory'),
                        $newFileImageName);/* on recup l'image et on la renomme et on la stocke dans le repoertoire */
                }catch (FileException $exception) {}/*en cas d'erreur*/
                    $product->setImage($newFileImageName);
                
            }

            $entityManager->persist($product);
            $entityManager->flush();

            // Création d'un nouvel enregistrement d'historique de stock
            $stockHistory = new AddProductHistory();// 1️⃣ On crée un nouvel objet d’historique de stock

            // On enregistre la quantité actuelle de stock du produit
            $stockHistory->setQuantity($product->getStock());// 2️⃣ On définit la quantité ajoutée (actuel stock du produit)

            // On associe le produit concerné à cet enregistrement
            $stockHistory->setProduct($product);// 3️⃣ On relie ce mouvement de stock au produit concerné

            // On fixe la date d'enregistrement à maintenant
            $stockHistory->setCreatedAt(new DateTimeImmutable());// 4️⃣ On fixe la date du mouvement

            // On prépare la sauvegarde de l'historique en base de données
            $entityManager->persist($stockHistory);

            // On effectue réellement la sauvegarde
            $entityManager->flush();/*effectue la mise a jour en bdd*/

            $this->addFlash('success', "L'article a été ajouté avec succès");
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('info', "L'article a été modifié avec succès");
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }
        $this->addFlash('danger', "L'article a été supprimé avec succès");
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/product/{id}/stock', name: 'app_product_stock_add', methods: ['POST','GET'])]
    public function stockAdd ($id, EntityManagerInterface $entityManager, Request $request, ProductRepository $productRepository) : Response
    {
        $stockAdd = new AddProductHistory();
        $form =$this->createForm(AddProductHistoryType::class, $stockAdd);
        $form->handleRequest($request);

        $product = $productRepository->find($id);

        if ($form->isSubmitted() && $form->isValid()) {

            if($stockAdd->getQuantity()>0){
                $newQuantity = $product->getStock() + $stockAdd->getQuantity();
                $product->setStock($newQuantity);

                $stockAdd->setCreatedAt(new DateTimeImmutable());
                $stockAdd->setProduct($product);
                $entityManager->persist($stockAdd);
                $entityManager->flush();

                $this->addFlash('success', "Le stock du produit à été modifié");
                return $this->redirectToRoute('app_product_index');
            }else {
                $this->addFlash('danger', "Le stock du produit ne doit pas être inférieur à zéro");
                return $this->redirectToRoute('app_product_stock_add', ['id'=>$product->getId()]);
            }
        }

        return $this->render('product/addStock.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }
     #[Route('/add/product/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
     public function showHistoryProductStock($id, ProductRepository $productRepository, AddProductHistoryRepository $addProductHistory ): Response
    {

            $product= $productRepository->find($id);
            $productAddHistory = $addProductHistory->findBy(['product'=> $product],['id'=> 'DESC']);  
            
            return $this->render('product/addedHistoryStockShow.html.twig', [
                "productsAdded" => $productAddHistory,
                "product" => $product
            ]);
    }

}
