<?php

namespace App\Tests\Unit;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaTest extends TestCase
{
    public function testTitleCanBeChanged(): void
    {
        $media = new Media();
        $media->setTitle('Titre de test');

        self::assertSame('Titre de test', $media->getTitle());
    }

    public function testPathCanBeChanged(): void
    {
        $media = new Media();
        $media->setPath('uploads/test.jpg');

        self::assertSame('uploads/test.jpg', $media->getPath());
    }

    public function testUserCanBeChanged(): void
    {
        $media = new Media();
        $user = new User();
        $user->setEmail('invite1@example.com');

        $media->setUser($user);

        self::assertSame($user, $media->getUser());
    }

    public function testAlbumCanBeChanged(): void
    {
        $media = new Media();
        $album = new Album();
        $album->setName('Album test');

        $media->setAlbum($album);

        self::assertSame($album, $media->getAlbum());
    }

    public function testAlbumCanBeNull(): void
    {
        $media = new Media();

        $media->setAlbum(null);

        self::assertNull($media->getAlbum());
    }

    public function testUserCanBeNull(): void
    {
        $media = new Media();

        $media->setUser(null);

        self::assertNull($media->getUser());
    }

    public function testFileCanBeChanged(): void
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'media-test-');

        self::assertIsString($temporaryFile);

        file_put_contents($temporaryFile, 'fake image content');

        $uploadedFile = new UploadedFile(
            $temporaryFile,
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        $media = new Media();
        $media->setFile($uploadedFile);

        self::assertSame($uploadedFile, $media->getFile());
    }
}