<?php

namespace App\Tests\Functional;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MediaPermissionTest extends WebTestCase
{
    public function testGuestOnlySeesOwnMedias(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->loginUser($guest);

        $client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Média invité 1 - 1');
        self::assertSelectorTextNotContains('body', 'Média invité 2 - 3');
    }

    public function testGuestMediaPaginationDisplaysSecondPage(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->loginUser($guest);

        $client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', '2');
    }

    public function testGuestCanAccessSecondPageOfOwnMedias(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $client->loginUser($guest);

        $client->request('GET', '/admin/media?page=2');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Média invité 1 - 26');
        self::assertSelectorTextNotContains('body', 'Média invité 2 - 3');
    }

    public function testAdminCanAccessMediaManagement(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');

        $client->loginUser($admin);

        $client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Média invité 1 - 1');
    }

    public function testAdminCanSeeMediasFromAnotherGuestOnLaterPages(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');

        $client->loginUser($admin);

        $client->request('GET', '/admin/media?page=3');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('body', 'Invité 2');
        self::assertSelectorTextContains('body', 'Média invité 2 - 2');
    }

    public function testGuestCannotDeleteAnotherGuestMedia(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');
        $otherGuestMedia = $this->getMediaByTitle('Média invité 2 - 1');

        $client->loginUser($guest);

        $client->request('GET', '/admin/media/delete/'.$otherGuestMedia->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testGuestCanDeleteOwnMedia(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');

        $media = new Media();
        $media->setUser($guest);
        $media->setTitle('Média temporaire à supprimer');
        $media->setPath('uploads/test-temporary-delete.jpg');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($media);
        $entityManager->flush();

        $mediaId = $media->getId();

        self::assertNotNull($mediaId);

        $client->loginUser($guest);

        $client->request('GET', '/admin/media/delete/'.$mediaId);

        self::assertResponseRedirects('/admin/media');

        $deletedMedia = static::getContainer()
            ->get(MediaRepository::class)
            ->find($mediaId);

        self::assertNull($deletedMedia);
    }

    public function testDeletingUnknownMediaReturns404(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');

        $client->loginUser($admin);

        $client->request('GET', '/admin/media/delete/999999');

        self::assertResponseStatusCodeSame(404);
    }

    private function getUserByEmail(string $email): User
    {
        $user = static::getContainer()
            ->get(UserRepository::class)
            ->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function getMediaByTitle(string $title): Media
    {
        $media = static::getContainer()
            ->get(MediaRepository::class)
            ->findOneBy(['title' => $title]);

        self::assertInstanceOf(Media::class, $media);

        return $media;
    }
}