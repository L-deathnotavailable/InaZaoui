<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FrontOfficeTest extends WebTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testGuestsPageIsAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/guests');

        self::assertResponseIsSuccessful();
    }

    public function testGuestsPageDisplaysGuests(): void
    {
        $client = static::createClient();

        $client->request('GET', '/guests');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Invité 1');
        self::assertSelectorTextContains('body', 'Invité 2');
    }

    public function testGuestsPageDoesNotDisplayAdminAsGuest(): void
    {
        $client = static::createClient();

        $client->request('GET', '/guests');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextNotContains('body', 'Ina Zaoui (');
    }

    public function testGuestDetailPageIsAccessible(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->request('GET', '/guest/'.$guest->getId());

        self::assertResponseIsSuccessful();
    }

    public function testGuestDetailPageDisplaysGuestInformation(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->request('GET', '/guest/'.$guest->getId());

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Invité 1');
        self::assertSelectorTextContains('body', 'Description de test pour Invité 1');
    }

    public function testGuestDetailPageDoesNotDisplayAnotherGuestInformation(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->request('GET', '/guest/'.$guest->getId());

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Invité 1');
        self::assertSelectorTextNotContains('body', 'Invité 2');
    }

    public function testGuestDetailPageDoesNotDisplayAnotherGuestMedias(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->request('GET', '/guest/'.$guest->getId());

        self::assertResponseIsSuccessful();

        self::assertSelectorTextNotContains('body', 'Média invité 2 - 1');
    }

    public function testUnknownGuestPageReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/guest/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPortfolioPageIsAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/portfolio');

        self::assertResponseIsSuccessful();
    }

    public function testPortfolioPageDisplaysAlbums(): void
    {
        $client = static::createClient();

        $client->request('GET', '/portfolio');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Album 1');
    }

    public function testPortfolioPageDisplaysPortfolioMedias(): void
    {
        $client = static::createClient();

        $client->request('GET', '/portfolio');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Média portfolio - 1');
    }

    public function testAboutPageIsAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/about');

        self::assertResponseIsSuccessful();
    }

    public function testAboutPageDisplaysInaInformation(): void
    {
        $client = static::createClient();

        $client->request('GET', '/about');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Ina');
    }

    private function getUserByEmail(string $email): User
    {
        $user = static::getContainer()
            ->get(UserRepository::class)
            ->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}