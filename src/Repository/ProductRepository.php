<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository de l'entité Product.
 *
 * Hérite de ServiceEntityRepository qui fournit les méthodes standard
 * de récupération de données sans écrire de SQL :
 *
 * find($id)                           → SELECT * FROM product WHERE id = $id
 * findAll()                           → SELECT * FROM product
 * findBy(['name' => 'Nike'], ['id' => 'DESC'])  → SELECT avec filtre + tri
 * findOneBy(['name' => 'Nike'])       → retourne un seul objet ou null
 *
 * Pour les cas plus complexes (recherche plein texte, jointures, agrégations)
 * on écrit des méthodes personnalisées avec le QueryBuilder — comme searchEngine() ci-dessous.
 *
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // On passe Product::class pour que Doctrine sache quelle table
        // ce repository gère — il fera le lien avec la table 'product' en BDD
        parent::__construct($registry, Product::class);
    }

    /**
     * Recherche des produits par mot-clé dans le nom ET la description.
     *
     * Utilisé par SearchEngineController pour la barre de recherche du catalogue.
     * Retourne tous les produits dont le nom OU la description contient $query.
     */
    public function searchEngine(string $query): array
    {
        // createQueryBuilder('p') crée un constructeur de requête
        // 'p' est l'alias de l'entité Product dans la requête — comme un AS en SQL
        // Doctrine génèrera du SQL à partir de ce qu'on lui enchaîne ici
        return $this->createQueryBuilder('p')

            // WHERE p.name LIKE :query
            // On travaille sur la propriété PHP 'name' de l'entité Product
            // pas directement sur la colonne SQL — Doctrine fait la traduction
            ->where('p.name LIKE :query')

            // OR p.description LIKE :query
            // orWhere() ajoute une condition OR — un résultat suffit pour matcher
            ->orWhere('p.description LIKE :query')

            // On lie la valeur réelle au paramètre :query
            // Les % autour de $query permettent la recherche partielle :
            // '%nike%' trouve "Nike Air", "Nike Jogger", "Basket nike basse"...
            // C'est Doctrine qui gère l'échappement — protection anti-injection SQL
            ->setParameter('query', '%' . $query . '%')

            // getQuery() compile le QueryBuilder en objet Query Doctrine
            ->getQuery()

            // getResult() exécute la requête SQL et retourne
            // un tableau d'objets Product — jamais de tableau brut
            // On accède aux données via les getters : getName(), getPrice()...
            ->getResult();
    }

    
    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
