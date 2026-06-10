<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityAccessTest extends WebTestCase
{
    public function testAnonymousUserIsRedirectedFromAdminMedia(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/media');

        self::assertResponseRedirects('/login');
    }

    public function testAnonymousUserIsRedirectedFromAdminGuests(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/guests');

        self::assertResponseRedirects('/login');
    }

    public function testAnonymousUserIsRedirectedFromAdminAlbum(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/album');

        self::assertResponseRedirects('/login');
    }

    public function testAdminCanAccessMediaManagement(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');

        $client->loginUser($admin);

        $client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanAccessGuestManagement(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');

        $client->loginUser($admin);

        $client->request('GET', '/admin/guests');

        self::assertResponseIsSuccessful();
    }

    public function testAdminCanAccessAlbumManagement(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');

        $client->loginUser($admin);

        $client->request('GET', '/admin/album');

        self::assertResponseIsSuccessful();
    }

    public function testGuestCanAccessOnlyMediaManagement(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->loginUser($guest);

        $client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
    }

    public function testGuestCannotAccessGuestManagement(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->loginUser($guest);

        $client->request('GET', '/admin/guests');

        self::assertResponseStatusCodeSame(403);
    }

    public function testGuestCannotAccessAlbumManagement(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->loginUser($guest);

        $client->request('GET', '/admin/album');

        self::assertResponseStatusCodeSame(403);
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