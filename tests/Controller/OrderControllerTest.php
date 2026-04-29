<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    /**
     * Teste que la page commande nécessite d'être connecté
     */
    public function testOrderRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/order');

        $this->assertResponseRedirects('/login');
    }

    /**
     * Teste que la gestion des commandes nécessite ROLE_EDITOR
     */
    public function testOrdersShowRequiresEditor(): void
    {
        $client = static::createClient();
        $client->request('GET', '/editor/order/all/');

        $this->assertResponseRedirects('/login');
    }
}
