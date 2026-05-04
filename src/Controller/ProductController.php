<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
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

// Toutes les routes de ce contrôleur commencent par /editor/product
// Elles sont protégées par access_control dans security.yaml (ROLE_EDITOR requis)
#[Route('editor/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        // findAll() retourne tous les produits sans filtre ni tri
        // triés par id par défaut — l'affichage est géré côté Twig
        return $this->render('product/allProducts.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $product = new Product();

        // createForm() lie le formulaire HTML à l'entité Product
        // handleRequest() lit les données POST et les injecte dans $product via les setters
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // getData() récupère le fichier uploadé depuis le champ 'image' du formulaire
            // Il retourne un objet UploadedFile ou null si aucun fichier n'a été envoyé
            $image = $form->get('image')->getData();

            if ($image) {
                // getClientOriginalName() retourne le nom original du fichier côté client
                // pathinfo() avec PATHINFO_FILENAME extrait uniquement le nom sans extension
                // ex: "Nike Air Max.jpg" → "Nike Air Max"
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                // slug() transforme le nom en version URL-safe sans espaces ni caractères spéciaux
                // ex: "Nike Air Max" → "Nike-Air-Max"
                // Indispensable pour éviter des noms de fichiers avec espaces ou accents
                $safeImageName = $slugger->slug($originalName);

                // uniqid() génère un identifiant unique basé sur l'horodatage
                // Même si deux images ont le même nom, leurs fichiers finaux seront différents
                // ex: "Nike-Air-Max-64f3a2b1c8e9d.jpg"
                $newFileImageName = $safeImageName . '-' . uniqid() . '.' . $image->guessExtension();

                try {
                    // move() déplace le fichier temporaire vers le dossier de destination
                    // getParameter('image_directory') lit le paramètre défini dans services.yaml
                    // qui pointe vers public/uploads/images/
                    $image->move(
                        $this->getParameter('image_directory'),
                        $newFileImageName
                    );
                } catch (FileException $exception) {
                    // Si le déplacement échoue (permissions, dossier inexistant...)
                    // on laisse passer silencieusement — le produit sera créé sans image
                    // Amélioration possible : ajouter un addFlash('danger', ...) ici
                }

                // On enregistre uniquement le nom du fichier en BDD
                // pas le chemin complet — Twig reconstruit le chemin avec asset()
                $product->setImage($newFileImageName);
            }

            // persist() signale à Doctrine que cet objet doit être sauvegardé
            // flush() exécute réellement le INSERT en BDD
            // On fait un premier flush ici pour que $product ait un id
            // avant de créer l'historique de stock associé
            $entityManager->persist($product);
            $entityManager->flush();

            // Chaque création de produit génère automatiquement une entrée dans
            // add_product_history pour tracer le stock initial
            // Cela permet de voir l'historique complet des mouvements de stock
            $stockHistory = new AddProductHistory();

            // On enregistre le stock saisi lors de la création comme premier mouvement
            $stockHistory->setQuantity($product->getStock());

            // On lie cet historique au produit qu'on vient de créer
            // Doctrine utilise cette relation pour renseigner product_id en BDD
            $stockHistory->setProduct($product);

            // DateTimeImmutable est préférable à DateTime car il ne peut pas être modifié
            // après sa création — plus sûr pour les données d'audit comme l'historique
            // On force le fuseau Europe/Paris pour être cohérent avec le serveur de prod
            $stockHistory->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));

            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success', "L'article a été ajouté avec succès");

            // HTTP_SEE_OTHER (303) est le code de redirection recommandé après un POST
            // Il indique au navigateur de faire un GET sur la nouvelle URL
            // et évite le "voulez-vous renvoyer le formulaire ?" au rafraîchissement
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        // Symfony injecte directement l'objet Product grâce au ParamConverter
        // Il lit {id} dans l'URL, fait un SELECT WHERE id = {id} automatiquement
        // et retourne une 404 si le produit n'existe pas
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product, // ParamConverter — Doctrine injecte l'objet Product via {id}
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        // ProductUpdateType est un formulaire séparé de ProductType
        // car la modification n'a pas les mêmes champs requis que la création
        // (le stock n'est pas modifiable ici — il passe par stockAdd())
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Premier flush pour les champs texte (nom, description, prix...)
            // avant de traiter l'image qui peut nécessiter des opérations supplémentaires
            $entityManager->flush();

            $image = $form->get('image')->getData();

            if ($image) {
                // Même logique de sécurisation que dans new()
                // slug + uniqid pour éviter les conflits de noms de fichiers
                $originalName     = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeImageName    = $slugger->slug($originalName);
                $newFileImageName  = $safeImageName . '-' . uniqid() . '.' . $image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('image_directory'),
                        $newFileImageName
                    );
                } catch (FileException $exception) {
                    // Même gestion silencieuse que dans new()
                }

                $product->setImage($newFileImageName);

                // Deuxième flush uniquement si une nouvelle image a été uploadée
                // pour sauvegarder le nouveau nom de fichier en BDD
                $entityManager->flush();
            }

            $this->addFlash('info', "L'article a été modifié avec succès");
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérification du token CSRF avant toute suppression
        // Le token est généré dans le template Twig et vérifié ici
        // Sans ça n'importe qui pourrait supprimer un produit en envoyant
        // une requête POST vers cette URL depuis un autre site (attaque CSRF)
        // 'delete'.$product->getId() = clé unique par produit pour éviter la réutilisation du token
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        $this->addFlash('danger', "L'article a été supprimé avec succès");
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/product/{id}/stock', name: 'app_product_stock_add', methods: ['POST', 'GET'])]
    public function stockAdd(
        $id,
        EntityManagerInterface $entityManager,
        Request $request,
        ProductRepository $productRepository
    ): Response {
        $stockAdd = new AddProductHistory();
        $form     = $this->createForm(AddProductHistoryType::class, $stockAdd);
        $form->handleRequest($request);

        // On charge le produit manuellement ici car on a besoin de son stock actuel
        // pour calculer le nouveau stock — on ne peut pas utiliser le ParamConverter
        // car on doit aussi lier $stockAdd au produit après la soumission
        $product = $productRepository->find($id);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($stockAdd->getQuantity() > 0) {
                // Nouveau stock = stock actuel + quantité ajoutée
                // On met à jour directement l'entité Product
                // Doctrine détecte le changement et génère un UPDATE lors du flush
                $newQuantity = $product->getStock() + $stockAdd->getQuantity();
                $product->setStock($newQuantity);

                $stockAdd->setCreatedAt(new DateTimeImmutable());
                $stockAdd->setProduct($product);

                // persist() nécessaire car $stockAdd est un nouvel objet
                // pas encore connu de Doctrine (contrairement à $product qui est managed)
                $entityManager->persist($stockAdd);
                $entityManager->flush();

                $this->addFlash('success', "Le stock du produit à été modifié");
                return $this->redirectToRoute('app_product_index');

            } else {
                // On refuse les quantités négatives ou nulles
                // Un stock ne peut qu'augmenter via ce formulaire
                // La diminution se fait automatiquement lors des commandes
                $this->addFlash('danger', "Le stock du produit ne doit pas être inférieur à zéro");
                return $this->redirectToRoute('app_product_stock_add', ['id' => $product->getId()]);
            }
        }

        return $this->render('product/addStock.html.twig', [
            'form'    => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/add/product/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function showHistoryProductStock(
        $id,
        ProductRepository $productRepository,
        AddProductHistoryRepository $addProductHistory
    ): Response {
        $product = $productRepository->find($id);

        // findBy() avec deux paramètres :
        // 1. ['product' => $product] — filtre sur le produit concerné
        // 2. ['id' => 'DESC'] — trie du plus récent au plus ancien
        // On voit ainsi en premier les derniers mouvements de stock
        $productAddHistory = $addProductHistory->findBy(
            ['product' => $product],
            ['id'      => 'DESC']
        );

        return $this->render('product/addedHistoryStockShow.html.twig', [
            'productsAdded' => $productAddHistory,
            'product'       => $product,
        ]);
    }
}