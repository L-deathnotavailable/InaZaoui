<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminGuestTest extends WebTestCase
{
    public function testAdminCanAccessGuestIndex(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/guests');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Invités');
        self::assertSelectorTextContains('body', 'Ajouter');
        self::assertSelectorTextContains('body', 'Invité 1');
        self::assertSelectorTextContains('body', 'invite1@example.com');
    }

    public function testGuestCannotAccessGuestIndex(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');
        $client->loginUser($guest);

        $client->request('GET', '/admin/guests');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessGuestAddForm(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Ajouter un invité');
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="guest[name]"]');
        self::assertSelectorExists('input[name="guest[email]"]');
        self::assertSelectorExists('textarea[name="guest[description]"]');
        self::assertSelectorExists('input[name="guest[password]"]');
    }

    public function testAdminCanCreateGuest(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/add');

        self::assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'guest[name]' => 'Invité créé par test',
            'guest[email]' => 'invite-created-by-test@example.com',
            'guest[description]' => 'Description créée par test',
            'guest[password]' => 'test123',
        ]);

        self::assertResponseRedirects('/admin/guests');

        $createdGuest = static::getContainer()
            ->get(UserRepository::class)
            ->findOneBy(['email' => 'invite-created-by-test@example.com']);

        self::assertInstanceOf(User::class, $createdGuest);
        self::assertSame('Invité créé par test', $createdGuest->getName());
        self::assertSame('Description créée par test', $createdGuest->getDescription());
        self::assertFalse($createdGuest->isAdmin());
        self::assertFalse($createdGuest->isBlocked());
        self::assertNotNull($createdGuest->getPassword());
    }

    public function testAdminCanBlockGuest(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $guest = $this->createTemporaryGuest(
            name: 'Invité temporaire à bloquer',
            email: 'temporary-block@example.com',
            blocked: false
        );

        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/'.$guest->getId().'/block');

        self::assertResponseRedirects('/admin/guests');

        $updatedGuest = static::getContainer()
            ->get(UserRepository::class)
            ->find($guest->getId());

        self::assertInstanceOf(User::class, $updatedGuest);
        self::assertTrue($updatedGuest->isBlocked());
    }

    public function testAdminCanUnblockGuest(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $guest = $this->createTemporaryGuest(
            name: 'Invité temporaire à débloquer',
            email: 'temporary-unblock@example.com',
            blocked: true
        );

        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/'.$guest->getId().'/block');

        self::assertResponseRedirects('/admin/guests');

        $updatedGuest = static::getContainer()
            ->get(UserRepository::class)
            ->find($guest->getId());

        self::assertInstanceOf(User::class, $updatedGuest);
        self::assertFalse($updatedGuest->isBlocked());
    }

    public function testBlockingUnknownGuestReturns404(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/999999/block');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCannotBlockAdminAsGuest(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/'.$admin->getId().'/block');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCanDeleteGuest(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $guest = $this->createTemporaryGuest(
            name: 'Invité temporaire à supprimer',
            email: 'temporary-delete@example.com',
            blocked: false
        );

        $guestId = $guest->getId();

        self::assertNotNull($guestId);

        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/delete/'.$guestId);

        self::assertResponseRedirects('/admin/guests');

        $deletedGuest = static::getContainer()
            ->get(UserRepository::class)
            ->find($guestId);

        self::assertNull($deletedGuest);
    }

    public function testDeletingUnknownGuestReturns404(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/delete/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCannotBeDeletedAsGuest(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/guests/delete/'.$admin->getId());

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

    private function createTemporaryGuest(
        string $name,
        string $email,
        bool $blocked
    ): User {
        $guest = new User();
        $guest->setName($name);
        $guest->setEmail($email);
        $guest->setDescription('Description temporaire');
        $guest->setAdmin(false);
        $guest->setBlocked($blocked);
        $guest->setPassword('temporary-hashed-password');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($guest);
        $entityManager->flush();

        return $guest;
    }
}