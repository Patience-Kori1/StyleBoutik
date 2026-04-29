<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartControllerTest extends WebTestCase
{
    /**
     * Teste que la page panier redirige si non connecté
     */
    public function testCartRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cart');

        // Le panier nécessite ROLE_USER
        $this->assertResponseRedirects('/login');
    }

    /**
     * Teste que l'ajout au panier redirige si non connecté
     */
    public function testAddToCartRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cart/add/1/');

        $this->assertResponseRedirects('/login');
    }
}