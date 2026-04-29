<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    /**
     * Teste que la gestion des produits nécessite ROLE_EDITOR
     */
    public function testProductIndexRequiresEditor(): void
    {
        $client = static::createClient();
        $client->request('GET', '/editor/product');

        $this->assertResponseRedirects('/login');
    }

    /**
     * Teste que la création de produit nécessite ROLE_EDITOR
     */
    public function testProductNewRequiresEditor(): void
    {
        $client = static::createClient();
        $client->request('GET', '/editor/product/new');

        $this->assertResponseRedirects('/login');
    }
}