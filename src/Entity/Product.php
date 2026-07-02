<?php

// ============================================================
// QU'EST-CE QUE CE FICHIER ?
// ============================================================
// Ce fichier définit une "Entité" Symfony appelée Product.
//
// Une entité, c'est simplement une classe PHP ordinaire qui représente
// un objet du monde réel — ici, un produit d'une boutique en ligne.
//
// La magie de Doctrine (la bibliothèque qui gère la base de données dans Symfony)
// c'est qu'elle lit cette classe et crée automatiquement la table MySQL correspondante.
//
// Concrètement :
//   - Cette classe PHP  →  devient une table "product" en MySQL
//   - Chaque propriété  →  devient une colonne dans cette table
//   - Chaque objet      →  devient une ligne (un enregistrement) dans cette table
//
// Exemple :
//   $product = new Product();
//   $product->setName('T-shirt noir');
//   $product->setPrice(1990); // 19,90€ en centimes
//   $entityManager->persist($product); // prépare l'insertion
//   $entityManager->flush();           // exécute le INSERT en BDD
//
// ============================================================


// ============================================================
// 1. NAMESPACE
// ============================================================
// Le namespace, c'est l'adresse de ce fichier dans le projet.
// Il suit la structure des dossiers : src/Entity/Product.php
// → namespace App\Entity
//
// À quoi ça sert ?
// Quand un autre fichier veut utiliser cette classe, il écrit :
//   use App\Entity\Product;
// PHP sait alors exactement où trouver le fichier.
// Sans namespace, deux classes qui s'appellent "Product" dans
// des dossiers différents entreraient en conflit.
// ============================================================
namespace App\Entity;


// ============================================================
// 2. USE — Les imports
// ============================================================
// "use" permet d'importer des classes externes pour les utiliser
// dans ce fichier sans écrire leur chemin complet à chaque fois.
//
// C'est comme les "import" en Python ou les "import" en Java.
//
// Sans "use", il faudrait écrire :
//   private \Doctrine\Common\Collections\Collection $subcategory;
// Avec "use", on écrit simplement :
//   private Collection $subcategory;
// ============================================================

// Le repository est la classe qui contient les requêtes personnalisées
// pour récupérer des Product en base (ex: findByCategory(), findActive()...)
use App\Repository\ProductRepository;

// ArrayCollection : c'est le type de "liste" que Doctrine utilise
// pour stocker des relations (plusieurs objets liés ensemble).
// Pense à ça comme un tableau PHP amélioré, avec des méthodes
// pratiques comme contains(), add(), removeElement()...
use Doctrine\Common\Collections\ArrayCollection;

// Collection : c'est l'interface (le "contrat") que ArrayCollection implémente.
// On utilise Collection comme type hint dans les propriétés et getters,
// car c'est plus flexible — ça accepte tout objet qui respecte ce contrat.
use Doctrine\Common\Collections\Collection;

// Types : fournit des constantes pour des types SQL spéciaux.
// Ex : Types::TEXT correspond à la colonne SQL de type TEXT
// (différent de VARCHAR qui a une limite de caractères)
use Doctrine\DBAL\Types\Types;

// ORM = Object-Relational Mapping (Mapping Objet-Relationnel)
// C'est la technique qui fait le pont entre :
//   - le monde objet (classes PHP)
//   - le monde relationnel (tables MySQL)
// En important "as ORM", on peut écrire #[ORM\Entity] au lieu de
// #[Doctrine\ORM\Mapping\Entity] — beaucoup plus court !
use Doctrine\ORM\Mapping as ORM;


// ============================================================
// 3. ATTRIBUT DE CLASSE #[ORM\Entity]
// ============================================================
// Les attributs PHP (introduits en PHP 8, syntaxe #[...]) sont des
// métadonnées qu'on attache à une classe, une propriété ou une méthode.
// Doctrine les lit au démarrage pour comprendre comment mapper ta classe.
//
// #[ORM\Entity] dit à Doctrine :
//   "Cette classe est une entité — crée une table pour elle en BDD"
//
// repositoryClass: ProductRepository::class
//   Indique quelle classe gérera les requêtes pour cette entité.
//   Quand tu écris $productRepository->findAll(), c'est ProductRepository
//   qui est appelé en coulisse.
//   "::class" est une syntaxe PHP qui retourne le nom complet de la classe
//   sous forme de chaîne : 'App\Repository\ProductRepository'
// ============================================================
#[ORM\Entity(repositoryClass: ProductRepository::class)]


// ============================================================
// 4. DÉCLARATION DE LA CLASSE
// ============================================================
// Une classe PHP est un modèle (blueprint) pour créer des objets.
// "class Product" définit ce qu'est un produit : ses données (propriétés)
// et ses comportements (méthodes).
//
// Pas d'héritage ici (pas de "extends") — Product est une classe autonome.
// Doctrine ne nécessite pas d'héritage pour fonctionner, contrairement
// à d'autres frameworks qui imposent d'étendre une classe de base.
// ============================================================
class Product
{

    // ============================================================
    // 5. PROPRIÉTÉS — Les colonnes de la table
    // ============================================================
    // Chaque propriété privée de cette classe correspond à une colonne
    // dans la table "product" de MySQL.
    //
    // POURQUOI "private" ?
    // En POO (Programmation Orientée Objet), on encapsule les données :
    // on les déclare "private" pour que personne ne puisse les modifier
    // directement de l'extérieur. L'accès passe obligatoirement par
    // les getters et setters (voir plus bas).
    //
    // Exemple de ce qu'on INTERDIT :
    //   $product->name = 'T-shirt'; // ERREUR — propriété privée
    //
    // Exemple de ce qu'on AUTORISE :
    //   $product->setName('T-shirt'); // OK — passe par le setter
    // ============================================================


    // --- PROPRIÉTÉ : id ---
    // #[ORM\Id] = cette propriété est la clé primaire de la table.
    //   La clé primaire identifie de façon unique chaque ligne en BDD.
    //   Il ne peut pas y avoir deux Product avec le même id.
    //
    // #[ORM\GeneratedValue] = la valeur est générée automatiquement
    //   par MySQL en auto-increment : 1, 2, 3, 4...
    //   Tu n'as jamais besoin de définir l'id manuellement.
    //
    // #[ORM\Column] = cette propriété est une colonne SQL.
    //   Sans options supplémentaires, Doctrine déduit le type SQL
    //   à partir du type PHP : ?int → colonne INT en MySQL.
    //
    // ?int = "int nullable" en PHP
    //   Le "?" signifie que la valeur peut être null.
    //   On initialise à null car avant le premier $entityManager->flush(),
    //   l'objet n'a pas encore d'id — MySQL ne l'a pas encore assigné.
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    // --- PROPRIÉTÉ : name ---
    // #[ORM\Column(length: 255, unique: true)]
    //
    // length: 255
    //   Crée une colonne VARCHAR(255) en MySQL.
    //   VARCHAR = chaîne de caractères de longueur variable (max 255 ici).
    //   Utilise uniquement l'espace nécessaire contrairement à CHAR
    //   qui réserve toujours la taille maximale.
    //
    // unique: true
    //   Ajoute une contrainte UNIQUE sur cette colonne en MySQL.
    //   MySQL refusera d'insérer deux produits avec le même nom.
    //   Si tu essaies, tu auras une IntegrityConstraintViolationException.
    //   C'est MySQL lui-même qui garantit l'unicité, pas PHP.
    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;


    // --- PROPRIÉTÉ : description ---
    // #[ORM\Column(type: Types::TEXT, nullable: true)]
    //
    // type: Types::TEXT
    //   Crée une colonne TEXT en MySQL (et non VARCHAR).
    //   TEXT peut stocker jusqu'à 65 535 caractères, sans limite définie.
    //   Utilisé pour les longues descriptions, articles de blog, etc.
    //   La constante Types::TEXT vaut la chaîne 'text' — Doctrine traduit
    //   ça en TEXT pour MySQL, en TEXT pour PostgreSQL, etc.
    //
    // nullable: true
    //   La colonne accepte NULL en BDD — la description est facultative.
    //   Sans "nullable: true", Doctrine génère une colonne NOT NULL
    //   et MySQL refuserait un INSERT sans description.
    //   En PHP, le type "?string" (avec ?) reflète ce fait : peut être null.
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;


    // --- PROPRIÉTÉ : price ---
    // #[ORM\Column] sans options → Doctrine déduit le type depuis PHP.
    // ?int → colonne INT NOT NULL en MySQL.
    //
    // BONNE PRATIQUE : stocker les prix en centimes (entier) plutôt qu'en euros (décimal).
    // Pourquoi ? Les nombres flottants (float, double) ont des erreurs d'arrondi
    // en informatique. 0.1 + 0.2 ne donne pas exactement 0.3 en binaire !
    // Avec des centimes (INT), 1990 = 19,90€, pas d'arrondi possible.
    // Dans Twig : {{ product.price / 100 }}€ pour afficher 19,90€
    #[ORM\Column]
    private ?int $price = null;


    // ============================================================
    // 6. RELATION ManyToMany avec SubCategory
    // ============================================================
    //
    // QU'EST-CE QU'UNE RELATION EN BDD ?
    // En MySQL, les tables ne peuvent pas directement "contenir" d'autres tables.
    // On relie les tables avec des clés étrangères (FK = Foreign Keys).
    //
    // QU'EST-CE QUE MANYTO MANY ?
    // Un produit peut appartenir à PLUSIEURS sous-catégories.
    //   Ex: Un "jean slim" peut être dans "Hommes" ET dans "Jeans" ET dans "Soldes"
    // Une sous-catégorie peut contenir PLUSIEURS produits.
    //   Ex: La catégorie "Jeans" contient des dizaines de produits
    //
    // COMMENT MYSQL GÈRE ÇA ?
    // MySQL crée automatiquement une TABLE PIVOT (ou table de jonction) :
    //   Table "product_sub_category" avec deux colonnes :
    //     - product_id (FK vers product.id)
    //     - sub_category_id (FK vers sub_category.id)
    //   Chaque ligne de cette table = un lien entre un produit et une catégorie
    //
    // PARAMÈTRES DE L'ATTRIBUT :
    // targetEntity: SubCategory::class
    //   → L'autre entité impliquée dans la relation
    //
    // inversedBy: 'products'
    //   → Ce côté (Product) est le côté PROPRIÉTAIRE de la relation.
    //   → SubCategory a une propriété $products qui fait le lien inverse.
    //   → Le côté propriétaire est celui qui contrôle la table pivot.
    //   → Doctrine met à jour la table pivot uniquement depuis le côté propriétaire.
    //
    // @var Collection<int, SubCategory>
    //   → Annotation PHPDoc : cette collection contient des objets SubCategory
    //   → Le "int" est le type de la clé dans la collection (index numérique)
    //   → Utilisé par les IDEs pour l'autocomplétion
    /**
     * @var Collection<int, SubCategory>
     */
    #[ORM\ManyToMany(targetEntity: SubCategory::class, inversedBy: 'products')]
    private Collection $subcategory;


    // --- PROPRIÉTÉ : image ---
    // Stocke le NOM DU FICHIER image (pas l'image elle-même !)
    // Ex: "produit-123-tshirt-noir.jpg"
    // L'image physique est stockée dans le dossier public/uploads/
    // En Twig : <img src="{{ asset('uploads/' ~ product.image) }}">
    //
    // nullable: true car un produit peut ne pas avoir d'image.
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;


    // --- PROPRIÉTÉ : stock ---
    // Nombre d'unités disponibles en stock.
    // INT NOT NULL — un stock est toujours un nombre entier (0 minimum).
    // La logique métier (ne pas vendre si stock = 0) est gérée dans les services
    // ou contrôleurs, pas dans l'entité elle-même.
    #[ORM\Column]
    private ?int $stock = null;


    // ============================================================
    // 7. RELATION OneToMany avec AddProductHistory
    // ============================================================
    //
    // QU'EST-CE QUE ONETOMANY ?
    // Un Product peut avoir PLUSIEURS entrées d'historique de stock.
    //   Ex: Le "T-shirt noir" a reçu 50 unités le 01/01, puis 30 le 15/01
    // Mais chaque entrée d'historique appartient à UN SEUL produit.
    //
    // COMMENT MYSQL GÈRE ÇA ?
    // Pas de table pivot ici ! C'est plus simple :
    // La table "add_product_history" a une colonne "product_id"
    // qui référence la table "product".
    //   product              add_product_history
    //   -------              -------------------
    //   id=1  ←──────────── product_id=1, quantity=50, createdAt=01/01
    //   id=1  ←──────────── product_id=1, quantity=30, createdAt=15/01
    //   id=2  ←──────────── product_id=2, quantity=20, createdAt=05/01
    //
    // PARAMÈTRES :
    // targetEntity: AddProductHistory::class
    //   → L'entité liée
    //
    // mappedBy: 'product'
    //   → Ce côté (Product) est le côté INVERSE de la relation.
    //   → C'est AddProductHistory qui porte la FK (product_id) en BDD.
    //   → "mappedBy: 'product'" dit : "regarde la propriété $product
    //      dans AddProductHistory, c'est elle qui gère la relation"
    //
    // ============================================================
    // CASCADE ET ORPHAN REMOVAL — LES OPTIONS DE SUPPRESSION
    // ============================================================
    //
    // PROBLÈME SANS CES OPTIONS :
    // Si tu fais $entityManager->remove($product) et qu'il existe des
    // lignes dans add_product_history avec product_id = cet id,
    // MySQL va BLOQUER avec cette erreur :
    //   "SQLSTATE[23000]: Integrity constraint violation: 1451
    //    Cannot delete or update a parent row: a foreign key constraint fails"
    // MySQL protège l'intégrité référentielle : il refuse de laisser
    // des lignes "orphelines" qui pointent vers un parent supprimé.
    //
    // SOLUTION 1 — cascade: ['remove']
    //   Quand tu supprimes un Product, Doctrine supprime automatiquement
    //   TOUS ses AddProductHistory AVANT de supprimer le Product lui-même.
    //   C'est Doctrine (PHP) qui gère ça, pas MySQL.
    //   Doctrine fait : DELETE FROM add_product_history WHERE product_id = ?
    //                   puis : DELETE FROM product WHERE id = ?
    //   Sans cascade, tu devrais supprimer les historiques manuellement
    //   avant de supprimer le produit — erreur facile à oublier.
    //
    // SOLUTION 2 — orphanRemoval: true
    //   Scénario différent : tu ne supprimes PAS le Product, mais tu
    //   retires un AddProductHistory de la collection :
    //     $product->removeAddProductHistory($history);
    //     $entityManager->flush();
    //   Sans orphanRemoval: la ligne RESTE en BDD (juste déconnectée en mémoire)
    //   Avec orphanRemoval: Doctrine voit que $history n'appartient plus
    //   à aucun Product et le supprime automatiquement en BDD au flush.
    //
    // EN RÉSUMÉ :
    //   cascade: ['remove']  → protège lors de la suppression du PARENT (Product)
    //   orphanRemoval: true  → protège lors du retrait d'un ENFANT de la collection
    //   Les deux ensemble = gestion complète et sécurisée des suppressions
    /**
     * @var Collection<int, AddProductHistory>
     */
    #[ORM\OneToMany(
        targetEntity: AddProductHistory::class,
        mappedBy: 'product',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $addProductHistories;


    // ============================================================
    // 8. RELATION OneToMany avec OrderProducts
    // ============================================================
    //
    // Un Product peut apparaître dans PLUSIEURS lignes de commande.
    //   Ex: Le "T-shirt noir" a été commandé 200 fois par 200 clients différents
    // Chaque ligne de commande (OrderProducts) concerne UN SEUL produit.
    //
    // POURQUOI PAS DE CASCADE ICI ?
    // Contrairement à l'historique de stock, les commandes ont une valeur
    // juridique et comptable. Si on supprime un produit du catalogue,
    // les commandes passées qui contiennent ce produit doivent être conservées.
    // On ne veut PAS supprimer les commandes en cascade !
    // La gestion de ce cas (produit supprimé mais commande existante) est
    // traitée au niveau applicatif (ex: archiver le produit plutôt que le supprimer,
    // ou garder une référence au nom/prix au moment de la commande).
    /**
     * @var Collection<int, OrderProducts>
     */
    #[ORM\OneToMany(targetEntity: OrderProducts::class, mappedBy: 'product')]
    private Collection $productOrders;


    // ============================================================
    // 9. CONSTRUCTEUR
    // ============================================================
    //
    // Le constructeur est appelé automatiquement quand on crée un objet :
    //   $product = new Product(); → __construct() s'exécute immédiatement
    //
    // POURQUOI INITIALISER LES COLLECTIONS ICI ?
    // Les propriétés $subcategory, $addProductHistories et $productOrders
    // sont de type Collection. Si on ne les initialise pas, elles valent null.
    //
    // Problème : si quelqu'un fait $product->getSubcategory()->contains(...)
    // AVANT que Doctrine ait chargé les données depuis la BDD, PHP lancerait :
    //   "Call to a member function contains() on null"
    //   (impossible d'appeler une méthode sur null)
    //
    // En initialisant avec new ArrayCollection(), la collection est vide
    // mais utilisable immédiatement — plus d'erreur null.
    //
    // Quand Doctrine charge un Product depuis la BDD, il REMPLACE ces
    // ArrayCollection vides par ses propres proxies qui vont chercher
    // les données en BDD uniquement quand on y accède (lazy loading).
    // ============================================================
    public function __construct()
    {
        // ArrayCollection est l'implémentation concrète de Collection fournie par Doctrine.
        // C'est essentiellement un tableau PHP encapsulé dans un objet avec des méthodes
        // pratiques : add(), remove(), contains(), filter(), map(), etc.
        $this->subcategory = new ArrayCollection();
        $this->addProductHistories = new ArrayCollection();
        $this->productOrders = new ArrayCollection();
    }


    // ============================================================
    // 10. GETTERS ET SETTERS
    // ============================================================
    //
    // QU'EST-CE QU'UN GETTER ?
    // Une méthode publique qui retourne la valeur d'une propriété privée.
    // Convention de nommage : get + NomDeLaPropriété (camelCase)
    //   $product->getName() retourne la valeur de $this->name
    //
    // QU'EST-CE QU'UN SETTER ?
    // Une méthode publique qui modifie la valeur d'une propriété privée.
    // Convention de nommage : set + NomDeLaPropriété (camelCase)
    //   $product->setName('T-shirt') modifie $this->name
    //
    // POURQUOI "static" EN RETOUR ?
    // Les setters retournent "static" (= l'instance courante $this).
    // Ça permet le chaînage de méthodes (method chaining) :
    //   $product->setName('T-shirt')->setPrice(1990)->setStock(50);
    // Au lieu de :
    //   $product->setName('T-shirt');
    //   $product->setPrice(1990);
    //   $product->setStock(50);
    //
    // POURQUOI "?int", "?string" (avec le ?) ?
    // Le "?" signifie "nullable" : la valeur peut être null.
    // Avant le flush(), les propriétés valent null car elles n'ont pas
    // encore de valeur en BDD. Sans le "?", PHP lancerait une TypeError
    // si on accède à getId() avant que Doctrine ait assigné l'id.
    // ============================================================


    // Retourne l'identifiant unique du produit en BDD.
    // Null si le produit n'a pas encore été enregistré en BDD (avant flush).
    public function getId(): ?int
    {
        return $this->id;
    }
    // Note : pas de setId() — l'id est géré exclusivement par MySQL (auto-increment).
    // Le laisser modifiable serait une faille : on pourrait écraser l'id d'un autre produit.


    public function getName(): ?string
    {
        return $this->name;
    }

    // Le paramètre est "string" (sans ?) car le nom ne peut pas être null en BDD
    // (la colonne est NOT NULL). On force donc à passer une vraie valeur.
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this; // retourne $this pour permettre le chaînage
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }

    // Le paramètre est "?string" (avec ?) car la description peut être null
    // (colonne nullable: true). On accepte donc null comme valeur valide.
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }


    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }


    // ============================================================
    // GETTER / ADDER / REMOVER pour la relation ManyToMany (SubCategory)
    // ============================================================
    //
    // Pour les relations, on n'a pas de setter classique.
    // À la place on a :
    //   - un getter qui retourne toute la collection
    //   - un "adder" qui ajoute UN élément à la collection
    //   - un "remover" qui retire UN élément de la collection
    //
    // Pourquoi pas de setter ? Parce qu'on ne remplace jamais toute
    // une collection d'un coup — on ajoute ou retire des éléments un par un.
    // ============================================================

    /**
     * @return Collection<int, SubCategory>
     */
    // Retourne tous les sous-catégories liées à ce produit.
    // Le type de retour Collection (interface) est plus flexible que ArrayCollection
    // (implémentation) — ça respecte le principe de substitution de Liskov (POO).
    public function getSubcategory(): Collection
    {
        return $this->subcategory;
    }

    // Ajoute une sous-catégorie à ce produit.
    //
    // contains() : vérifie que cette SubCategory n'est pas déjà dans la collection.
    //   Sans ce check, on pourrait ajouter la même sous-catégorie plusieurs fois,
    //   ce qui créerait des doublons dans la table pivot MySQL.
    //
    // add() : ajoute l'élément à la collection ArrayCollection.
    //   Note : on n'appelle PAS $subcategory->addProduct($this) ici car c'est
    //   le côté Product (propriétaire) qui gère la table pivot. Le côté
    //   SubCategory (inverse, mappedBy) n'a pas besoin d'être synchronisé manuellement.
    public function addSubcategory(SubCategory $subcategory): static
    {
        if (!$this->subcategory->contains($subcategory)) {
            $this->subcategory->add($subcategory);
        }

        return $this;
    }

    // Retire une sous-catégorie de ce produit.
    //
    // removeElement() : retire l'élément de la collection si il s'y trouve.
    //   Retourne true si l'élément existait et a été retiré, false sinon.
    //   Ici on ignore le retour car peu importe — si la SubCategory n'était
    //   pas dans la collection, il n'y a rien à faire.
    public function removeSubcategory(SubCategory $subcategory): static
    {
        $this->subcategory->removeElement($subcategory);

        return $this;
    }


    public function getImage(): ?string
    {
        return $this->image;
    }

    // Accepte ?string car l'image peut être null (pas d'image pour ce produit).
    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }


    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }


    // ============================================================
    // GETTER / ADDER / REMOVER pour OneToMany (AddProductHistory)
    // ============================================================

    /**
     * @return Collection<int, AddProductHistory>
     */
    public function getAddProductHistories(): Collection
    {
        return $this->addProductHistories;
    }

    // Ajoute une entrée d'historique de stock à ce produit.
    //
    // SYNCHRONISATION DES DEUX CÔTÉS DE LA RELATION :
    // Dans une relation bidirectionnelle Doctrine, il y a deux côtés :
    //   - Côté PROPRIÉTAIRE : AddProductHistory (porte la FK product_id en BDD)
    //   - Côté INVERSE : Product (côté "mappedBy")
    //
    // Doctrine ne lit que le côté propriétaire pour faire ses requêtes SQL.
    // Donc si on veut qu'une relation soit sauvegardée en BDD, il FAUT
    // que le côté propriétaire soit mis à jour.
    //
    // C'est pourquoi on appelle $addProductHistory->setProduct($this) :
    //   on dit à l'AddProductHistory "ton product, c'est moi (ce Product)"
    //   → ça met à jour product_id en BDD lors du flush
    //
    // Sans cette ligne, la relation serait dans la collection PHP en mémoire
    // mais PAS sauvegardée en BDD — le product_id resterait null.
    public function addAddProductHistory(AddProductHistory $addProductHistory): static
    {
        if (!$this->addProductHistories->contains($addProductHistory)) {
            $this->addProductHistories->add($addProductHistory);
            // Synchronise le côté propriétaire (FK product_id dans add_product_history)
            $addProductHistory->setProduct($this);
        }

        return $this;
    }

    // Retire une entrée d'historique de stock.
    //
    // SI removeElement() RETOURNE TRUE (l'élément existait et a été retiré) :
    //   On vérifie que le product de cet historique pointe encore vers $this.
    //   (Il pourrait avoir été réassigné à un autre Product entre temps.)
    //   Si oui, on met product à null côté AddProductHistory.
    //
    // POURQUOI METTRE product À NULL ?
    //   Pour ne pas laisser une référence "fantôme" vers ce Product.
    //   Si on laissait product_id en BDD, on aurait une FK qui pointe
    //   vers un Product qui ne reconnaît plus cet historique — incohérence.
    //
    // ORPHANREMOVAL PREND LE RELAIS :
    //   Comme orphanRemoval: true est configuré sur cette relation,
    //   Doctrine voit que cet AddProductHistory n'appartient plus à aucun Product
    //   (product = null) et le supprime automatiquement en BDD lors du flush().
    //   Sans orphanRemoval, la ligne resterait en BDD avec product_id = null.
    public function removeAddProductHistory(AddProductHistory $addProductHistory): static
    {
        if ($this->addProductHistories->removeElement($addProductHistory)) {
            // Vérifie que la FK pointe encore vers ce Product avant de la nullifier
            if ($addProductHistory->getProduct() === $this) {
                // Met product_id à null → déclenche la suppression via orphanRemoval
                $addProductHistory->setProduct(null);
            }
        }

        return $this;
    }


    // ============================================================
    // GETTER / ADDER / REMOVER pour OneToMany (OrderProducts)
    // ============================================================

    /**
     * @return Collection<int, OrderProducts>
     */
    public function getProductOrders(): Collection
    {
        return $this->productOrders;
    }

    // Ajoute une ligne de commande liée à ce produit.
    // Même logique de synchronisation que pour addAddProductHistory :
    // on met à jour le côté propriétaire (OrderProducts porte la FK product_id).
    public function addProductOrder(OrderProducts $productOrder): static
    {
        if (!$this->productOrders->contains($productOrder)) {
            $this->productOrders->add($productOrder);
            // Synchronise le côté propriétaire de la relation
            $productOrder->setProduct($this);
        }

        return $this;
    }

    // Retire une ligne de commande de ce produit.
    //
    // On met product à null côté OrderProducts pour éviter une FK fantôme.
    // MAIS contrairement à addProductHistories, il n'y a PAS de orphanRemoval ici.
    // La ligne de commande reste donc en BDD avec product_id = null.
    // C'est voulu : une commande doit être conservée même si le produit est supprimé
    // (traçabilité, comptabilité, litiges clients).
    public function removeProductOrder(OrderProducts $productOrder): static
    {
        if ($this->productOrders->removeElement($productOrder)) {
            // Vérifie que la FK pointe encore vers ce Product avant de la nullifier
            if ($productOrder->getProduct() === $this) {
                // Met product_id à null — la commande est conservée en BDD
                $productOrder->setProduct(null);
            }
        }

        return $this;
    }

}
// FIN DE LA CLASSE Product
//
// RÉCAPITULATIF DE CE QUE DOCTRINE VA GÉNÉRER EN MYSQL :
//
// TABLE product :
//   id          INT AUTO_INCREMENT PRIMARY KEY
//   name        VARCHAR(255) NOT NULL UNIQUE
//   description TEXT NULL
//   price       INT NOT NULL
//   image       VARCHAR(255) NULL
//   stock       INT NOT NULL
//
// TABLE product_sub_category (table pivot ManyToMany) :
//   product_id      INT → FK vers product.id
//   sub_category_id INT → FK vers sub_category.id
//
// (La colonne product_id dans add_product_history et order_products
//  est gérée par les entités AddProductHistory et OrderProducts elles-mêmes)