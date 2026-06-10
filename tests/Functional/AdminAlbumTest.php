<?php

namespace App\Tests\Functional;

use App\Entity\Album;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAlbumTest extends WebTestCase
{
    public function testAdminCanAccessAlbumIndex(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/album');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Albums');
        self::assertSelectorTextContains('body', 'Ajouter');
    }

    public function testAdminAlbumIndexDisplaysExistingAlbums(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/album');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Album 1');
    }

    public function testGuestCannotAccessAlbumIndex(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');
        $client->loginUser($guest);

        $client->request('GET', '/admin/album');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessAlbumAddForm(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/album/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Albums');
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="album[name]"]');
    }

    public function testAdminCanCreateAlbum(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/album/add');

        self::assertResponseIsSuccessful();

        $client->submitForm('Ajouter', [
            'album[name]' => 'Album créé par test',
        ]);

        self::assertResponseRedirects('/admin/album');

        $album = static::getContainer()
            ->get(AlbumRepository::class)
            ->findOneBy(['name' => 'Album créé par test']);

        self::assertInstanceOf(Album::class, $album);
    }

    public function testAdminCanAccessAlbumUpdateForm(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $album = $this->createTemporaryAlbum('Album temporaire update form');

        $client->loginUser($admin);

        $client->request('GET', '/admin/album/update/'.$album->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Albums');
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="album[name]"]');
    }

    public function testAdminCanUpdateAlbum(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $album = $this->createTemporaryAlbum('Album avant modification');

        $client->loginUser($admin);

        $client->request('GET', '/admin/album/update/'.$album->getId());

        self::assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'album[name]' => 'Album après modification',
        ]);

        self::assertResponseRedirects('/admin/album');

        $updatedAlbum = static::getContainer()
            ->get(AlbumRepository::class)
            ->find($album->getId());

        self::assertInstanceOf(Album::class, $updatedAlbum);
        self::assertSame('Album après modification', $updatedAlbum->getName());
    }

    public function testAdminCanDeleteAlbum(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $album = $this->createTemporaryAlbum('Album temporaire suppression');
        $albumId = $album->getId();

        self::assertNotNull($albumId);

        $client->loginUser($admin);

        $client->request('GET', '/admin/album/delete/'.$albumId);

        self::assertResponseRedirects('/admin/album');

        $deletedAlbum = static::getContainer()
            ->get(AlbumRepository::class)
            ->find($albumId);

        self::assertNull($deletedAlbum);
    }

    private function getUserByEmail(string $email): User
    {
        $user = static::getContainer()
            ->get(UserRepository::class)
            ->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function createTemporaryAlbum(string $name): Album
    {
        $album = new Album();
        $album->setName($name);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($album);
        $entityManager->flush();

        return $album;
    }
}