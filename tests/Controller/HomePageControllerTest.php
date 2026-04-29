<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour HomePageController
 * Teste l'accueil, le détail produit, le filtre et la recherche
 */
class HomePageControllerTest extends WebTestCase
{
    /**
     * Teste que la page d'accueil est accessible (HTTP 200)
     */
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * Teste que la page d'accueil contient le logo StyleBoutik
     */
    public function testHomePageContainsBrandName(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('.sb-logo');
        $this->assertSelectorTextContains('.sb-logo', 'StyleBoutik');
    }

    /**
     * Teste que la section catalogue est présente
     * Note : pas de .sb-card car la BDD de test est vide
     */
    public function testHomePageContainsCatalogueSection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('#catalogue');
        $this->assertSelectorTextContains('.sb-section-title', 'Collection Complète');
    }

    /**
     * Teste que la navbar est présente
     */
    public function testHomePageContainsNavbar(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('.sb-navbar');
        $this->assertSelectorExists('.sb-search');
    }

    /**
     * Teste que le moteur de recherche est accessible
     */
    public function testSearchEngineIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/search/engine', ['word' => 'nike']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * Teste que la hero section est présente sur la page d'accueil
     */
    public function testHomePageContainsHeroSection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('.sb-section-tag');
        $this->assertSelectorTextContains('.sb-section-tag', 'Collection 2026');
    }

    /**
     * Teste que le footer est présent
     */
    public function testHomePageContainsFooter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertSelectorExists('footer');
    }
}