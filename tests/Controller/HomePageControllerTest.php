<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour HomePageController
 *
 * WebTestCase est la classe Symfony qui simule un navigateur HTTP pour tester les contrôleurs.
 * Elle démarre une vraie instance de l'application Symfony en environnement de test
 * et envoie de vraies requêtes HTTP — sans ouvrir de navigateur réel.
 *
 * Tous les tests de ce fichier testent les pages publiques accessibles sans connexion.
 * La BDD de test utilisée est my_shop_test — elle est vide, donc on ne teste
 * que la structure des pages (HTTP 200, sélecteurs CSS présents) et non les données.
 */
class HomePageControllerTest extends WebTestCase
{
    /**
     * Teste que la page d'accueil répond correctement
     *
     * createClient() → crée un client HTTP simulé (comme un navigateur)
     * request('GET', '/') → envoie une requête GET sur l'URL '/'
     *
     * assertResponseIsSuccessful() → vérifie que le code HTTP est entre 200 et 299
     * assertResponseStatusCodeSame(200) → vérifie que c'est exactement HTTP 200 OK
     * Si HomePageController plante ou si la route n'existe pas → ce test échoue
     */
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * Teste que le logo StyleBoutik est présent dans le HTML rendu
     *
     * assertSelectorExists('.sb-logo')
     * → vérifie qu'un élément HTML avec la classe CSS .sb-logo existe dans la page
     * → c'est l'équivalent de document.querySelector('.sb-logo') en JavaScript
     * → si AppGlobalsExtension ou la navbar Twig est cassée, ce test le détecte
     *
     * assertSelectorTextContains('.sb-logo', 'StyleBoutik')
     * → vérifie que cet élément contient bien le texte 'StyleBoutik'
     * → Premier paramètre : sélecteur CSS — Deuxième paramètre : texte attendu
     */
    public function testHomePageContainsBrandName(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('.sb-logo');
        $this->assertSelectorTextContains('.sb-logo', 'StyleBoutik');
    }

    /**
     * Teste que la section catalogue est bien présente dans la page
     *
     * Note importante : la BDD de test my_shop_test est vide — il n'y a pas de produits.
     * On ne peut donc pas tester la présence de cartes produits (.sb-card).
     * On teste uniquement la structure de la page : l'ancre #catalogue et le titre de section.
     *
     * assertSelectorExists('#catalogue')
     * → vérifie qu'un élément avec l'id="catalogue" existe — c'est l'ancre de navigation
     *
     * assertSelectorTextContains('.sb-section-title', 'Collection Complète')
     * → vérifie que le titre de la section contient bien ce texte
     */
    public function testHomePageContainsCatalogueSection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('#catalogue');
        $this->assertSelectorTextContains('.sb-section-title', 'Collection Complète');
    }

    /**
     * Teste que la navbar et la barre de recherche sont présentes
     *
     * Ces deux éléments sont dans navbar.html.twig, inclus dans base.html.twig.
     * S'ils sont absents, c'est que le template de base est cassé
     * ou qu'AppGlobalsExtension a levé une exception non gérée.
     *
     * assertSelectorExists('.sb-navbar') → vérifie la présence de la navbar
     * assertSelectorExists('.sb-search') → vérifie la présence de la barre de recherche
     */
    public function testHomePageContainsNavbar(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('.sb-navbar');
        $this->assertSelectorExists('.sb-search');
    }

    /**
     * Teste que la route du moteur de recherche est accessible
     *
     * request('GET', '/search/engine', ['word' => 'nike'])
     * → Troisième paramètre : tableau des paramètres de la requête
     * → Équivaut à visiter l'URL : /search/engine?word=nike
     * → SearchEngineController reçoit 'nike' et appelle ProductRepository::searchEngine()
     *
     * On vérifie juste que la page répond HTTP 200 sans planter.
     * La BDD étant vide, la recherche retourne zéro résultat — c'est normal.
     */
    public function testSearchEngineIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/search/engine', ['word' => 'nike']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * Teste que le moteur de recherche retourne une page valide pour n'importe quel mot
     *
     * Même principe que le test précédent mais avec un mot générique 'test'.
     * Ce test complète le précédent pour s'assurer que searchEngine()
     * ne plante pas peu importe le mot saisi — même si la BDD est vide.
     * Résultat attendu : HTTP 200 avec une page vide de résultats.
     */
    public function testSearchEngineReturnsResults(): void
    {
        $client = static::createClient();
        $client->request('GET', '/search/engine', ['word' => 'test']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * Teste que la hero section est présente sur la page d'accueil
     *
     * La hero section est la grande bannière en haut de homePage.html.twig.
     * Elle contient le badge '.sb-section-tag' avec le texte 'Collection 2026'.
     *
     * assertSelectorTextContains('.sb-section-tag', 'Collection 2026')
     * → Si ce texte change dans le template Twig sans mettre à jour le test,
     *   le test échoue immédiatement — c'est exactement son rôle de filet de sécurité.
     */
    public function testHomePageContainsHeroSection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('.sb-section-tag');
        $this->assertSelectorTextContains('.sb-section-tag', 'Collection 2026');
    }

    /**
     * Teste que le footer est présent sur la page d'accueil
     *
     * assertSelectorExists('footer')
     * → vérifie qu'une balise HTML <footer> existe dans la page rendue
     * → le footer est dans base.html.twig — ce test garantit que le template
     *   parent est bien rendu jusqu'au bout sans erreur Twig
     *
     * C'est le test le plus simple de ce fichier — une seule assertion.
     */
    public function testHomePageContainsFooter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('footer');
    }
}