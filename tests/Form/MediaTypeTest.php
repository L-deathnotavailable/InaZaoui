<?php

namespace App\Tests\Form;

use App\Entity\Media;
use App\Form\MediaType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->formFactory = static::getContainer()->get(FormFactoryInterface::class);
    }

    public function testMediaTypeForGuestContainsOnlyFileField(): void
    {
        $form = $this->formFactory->create(MediaType::class, new Media(), [
            'is_admin' => false,
            'csrf_protection' => false,
        ]);

        self::assertTrue($form->has('file'));

        self::assertFalse($form->has('user'));
        self::assertFalse($form->has('album'));
        self::assertFalse($form->has('title'));
    }

    public function testMediaTypeForAdminContainsAllExpectedFields(): void
    {
        $form = $this->formFactory->create(MediaType::class, new Media(), [
            'is_admin' => true,
            'csrf_protection' => false,
        ]);

        self::assertTrue($form->has('file'));
        self::assertTrue($form->has('user'));
        self::assertTrue($form->has('album'));
        self::assertTrue($form->has('title'));
    }

    public function testMediaTypeDefaultIsNotAdmin(): void
    {
        $form = $this->formFactory->create(MediaType::class, new Media(), [
            'csrf_protection' => false,
        ]);

        self::assertTrue($form->has('file'));

        self::assertFalse($form->has('user'));
        self::assertFalse($form->has('album'));
        self::assertFalse($form->has('title'));
    }

    public function testMediaTypeUsesMediaDataClass(): void
    {
        $form = $this->formFactory->create(MediaType::class, null, [
            'csrf_protection' => false,
        ]);

        self::assertSame(Media::class, $form->getConfig()->getDataClass());
    }

    public function testMediaTypeAcceptsValidImageFile(): void
    {
        $temporaryFile = $this->createTemporaryPngFile();

        $uploadedFile = new UploadedFile(
            $temporaryFile,
            'image-test.png',
            'image/png',
            null,
            true
        );

        $media = new Media();

        $form = $this->formFactory->create(MediaType::class, $media, [
            'is_admin' => false,
            'csrf_protection' => false,
        ]);

        $form->submit([
            'file' => $uploadedFile,
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertSame($uploadedFile, $media->getFile());
    }

    public function testMediaTypeRejectsInvalidFileMimeType(): void
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'media-form-test-');

        self::assertIsString($temporaryFile);

        file_put_contents($temporaryFile, 'fake pdf content');

        $uploadedFile = new UploadedFile(
            $temporaryFile,
            'document.pdf',
            'application/pdf',
            null,
            true
        );

        $media = new Media();

        $form = $this->formFactory->create(MediaType::class, $media, [
            'is_admin' => false,
            'csrf_protection' => false,
        ]);

        $form->submit([
            'file' => $uploadedFile,
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    private function createTemporaryPngFile(): string
    {
        $temporaryFile = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('media-form-test-', true)
            . '.png';

        $minimalPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/lqNqZQAAAABJRU5ErkJggg=='
        );

        self::assertIsString($minimalPng);

        file_put_contents($temporaryFile, $minimalPng);

        return $temporaryFile;
    }
}