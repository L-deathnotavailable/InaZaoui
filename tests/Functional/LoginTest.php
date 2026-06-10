<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanLoginWithDatabaseAccount(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            '_username' => 'ina@zaoui.com',
            '_password' => 'test123',
        ]);

        $client->submit($form);

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertResponseIsSuccessful();

        $client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Medias');
    }

    public function testGuestCanLoginWithDatabaseAccount(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            '_username' => 'invite1@example.com',
            '_password' => 'test123',
        ]);

        $client->submit($form);

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    public function testInvalidCredentialsDoNotAuthenticateUser(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            '_username' => 'ina@zaoui.com',
            '_password' => 'mauvais-mot-de-passe',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/login');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    public function testBlockedGuestCannotLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            '_username' => 'blocked@example.com',
            '_password' => 'test123',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/login');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }
}