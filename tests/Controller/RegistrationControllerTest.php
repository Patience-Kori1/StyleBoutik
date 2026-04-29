<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    /**
     * Teste que la page d'inscription est accessible (HTTP 200)
     */
    public function testRegisterPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * Teste que la page d'inscription contient les champs attendus
     */
    public function testRegisterPageContainsForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        // Vérifie les champs du formulaire
        $this->assertSelectorExists('input[name="registration_form[email]"]');
        $this->assertSelectorExists('input[name="registration_form[plainPassword]"]');
        $this->assertSelectorExists('input[name="registration_form[agreeTerms]"]');
    }

    /**
     * Teste que le titre de la page est correct
     */
    public function testRegisterPageTitle(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        // Vérifie le texte visible sur la page
        $this->assertSelectorTextContains('p', 'Rejoindre la boutique');
    }
}