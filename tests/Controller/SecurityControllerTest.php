<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    // Étape 1 : La page login est accessible
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
    }

    // Étape 2 : Connexion valide → redirection
    public function testLoginRedirectsAfterSuccess(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()
            ->get('security.user_password_hasher');

        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => 'test@test.com',
            '_password' => 'password',
        ]);

        $this->assertResponseRedirects();
    }

    // Étape 3 : Route admin sans connexion → redirige vers login
    public function testAdminRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/user');
        $this->assertResponseRedirects('/login');
    }

    public function testLogout(): void
    {
        $client = static::createClient();

        // D'abord on se connecte
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => 'test@test.com',
            '_password' => 'password',
        ]);

        // Puis on se déconnecte
        $client->request('GET', '/logout');
        $this->assertResponseRedirects();
    }
}