<?php

namespace App\Tests\Functional;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdminMediaTest extends WebTestCase
{
    public function testGuestCanAccessMediaIndex(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');
        $client->loginUser($guest);

        $client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Medias');
        self::assertSelectorTextContains('body', 'Média invité 1 - 1');
        self::assertSelectorTextNotContains('body', 'Média invité 2 - 1');
    }

    public function testAdminCanAccessMediaIndexAndSeeAllMediaColumns(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/media');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Medias');
        self::assertSelectorTextContains('body', 'Artiste');
        self::assertSelectorTextContains('body', 'Album');
    }

    public function testGuestCanAccessMediaAddForm(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');
        $client->loginUser($guest);

        $client->request('GET', '/admin/media/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Medias');
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="media[file]"]');

        self::assertSelectorNotExists('input[name="media[title]"]');
        self::assertSelectorNotExists('select[name="media[user]"]');
        self::assertSelectorNotExists('select[name="media[album]"]');
    }

    public function testAdminCanAccessMediaAddFormWithAdminFields(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $client->loginUser($admin);

        $client->request('GET', '/admin/media/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Medias');
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="media[file]"]');
        self::assertSelectorExists('input[name="media[title]"]');
        self::assertSelectorExists('select[name="media[user]"]');
        self::assertSelectorExists('select[name="media[album]"]');
    }

    public function testGuestCanUploadValidImage(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');
        $client->loginUser($guest);

        $mediaRepository = static::getContainer()->get(MediaRepository::class);

        $beforeUploadCount = count($mediaRepository->findBy([
            'user' => $guest,
        ]));

        $uploadedFile = $this->createTemporaryUploadedPngFile('guest-upload-test.png');

        $client->request('GET', '/admin/media/add');

        self::assertResponseIsSuccessful();

        $client->submitForm('Ajouter', [
            'media[file]' => $uploadedFile,
        ]);

        self::assertResponseRedirects('/admin/media');

        $afterUploadMedias = $mediaRepository->findBy(
            ['user' => $guest],
            ['id' => 'DESC']
        );

        self::assertCount($beforeUploadCount + 1, $mediaRepository->findBy([
            'user' => $guest,
        ]));

        $createdMedia = $afterUploadMedias[0] ?? null;

        self::assertInstanceOf(Media::class, $createdMedia);
        self::assertSame($guest->getId(), $createdMedia->getUser()?->getId());
        self::assertStringStartsWith('media-upload-test-', $createdMedia->getTitle());
        self::assertStringStartsWith('uploads/', $createdMedia->getPath());

        $this->removeUploadedFileIfExists($createdMedia);
    }

    public function testAdminCanUploadValidImageForGuestAndAlbum(): void
    {
        $client = static::createClient();

        $admin = $this->getUserByEmail('ina@zaoui.com');
        $guest = $this->getUserByEmail('invite2@example.com');
        $album = $this->getAlbumByName('Album 1');

        $client->loginUser($admin);

        $uniqueTitle = 'admin-upload-' . uniqid('', true);
        $uploadedFile = $this->createTemporaryUploadedPngFile($uniqueTitle . '.png');

        $client->request('GET', '/admin/media/add');

        self::assertResponseIsSuccessful();

        $client->submitForm('Ajouter', [
            'media[file]' => $uploadedFile,
            'media[user]' => (string) $guest->getId(),
            'media[album]' => (string) $album->getId(),
            'media[title]' => $uniqueTitle,
        ]);

        self::assertResponseRedirects('/admin/media');

        $createdMedia = static::getContainer()
            ->get(MediaRepository::class)
            ->findOneBy(['title' => $uniqueTitle]);

        self::assertInstanceOf(Media::class, $createdMedia);
        self::assertSame($guest->getId(), $createdMedia->getUser()?->getId());
        self::assertSame($album->getId(), $createdMedia->getAlbum()?->getId());
        self::assertStringStartsWith('uploads/', $createdMedia->getPath());

        $this->removeUploadedFileIfExists($createdMedia);
    }

    public function testGuestCannotUploadInvalidFileType(): void
    {
        $client = static::createClient();

        $guest = $this->getUserByEmail('invite1@example.com');
        $client->loginUser($guest);

        $uploadedFile = $this->createTemporaryUploadedTextFile('invalid-file.txt');

        $client->request('GET', '/admin/media/add');

        self::assertResponseIsSuccessful();

        $client->submitForm('Ajouter', [
            'media[file]' => $uploadedFile,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Veuillez envoyer une image');
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

    private function getAlbumByName(string $name): Album
    {
        $album = static::getContainer()
            ->get(AlbumRepository::class)
            ->findOneBy(['name' => $name]);

        self::assertInstanceOf(Album::class, $album);

        return $album;
    }

    private function createTemporaryUploadedPngFile(string $originalName): UploadedFile
    {
        $temporaryFile = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('media-upload-test-', true)
            . '.png';

        $minimalPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/lqNqZQAAAABJRU5ErkJggg=='
        );

        self::assertIsString($minimalPng);

        file_put_contents($temporaryFile, $minimalPng);

        return new UploadedFile(
            $temporaryFile,
            $originalName,
            'image/png',
            null,
            true
        );
    }

    private function createTemporaryUploadedTextFile(string $originalName): UploadedFile
    {
        $temporaryFile = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('media-upload-test-', true)
            . '.txt';

        file_put_contents($temporaryFile, 'Ceci n’est pas une image.');

        return new UploadedFile(
            $temporaryFile,
            $originalName,
            'text/plain',
            null,
            true
        );
    }

    private function removeUploadedFileIfExists(Media $media): void
    {
        $filePath = static::getContainer()->getParameter('kernel.project_dir')
            . '/public/'
            . $media->getPath();

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}