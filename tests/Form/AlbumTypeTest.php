<?php

namespace App\Tests\Form;

use App\Entity\Album;
use App\Form\AlbumType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class AlbumTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->formFactory = static::getContainer()->get(FormFactoryInterface::class);
    }

    public function testAlbumTypeContainsExpectedFields(): void
    {
        $form = $this->formFactory->create(AlbumType::class, new Album(), [
            'csrf_protection' => false,
        ]);

        self::assertTrue($form->has('name'));
    }

    public function testAlbumTypeSubmitValidData(): void
    {
        $album = new Album();

        $form = $this->formFactory->create(AlbumType::class, $album, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            'name' => 'Album test',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertSame('Album test', $album->getName());
    }

    public function testAlbumTypeUsesAlbumDataClass(): void
    {
        $form = $this->formFactory->create(AlbumType::class, null, [
            'csrf_protection' => false,
        ]);

        self::assertSame(Album::class, $form->getConfig()->getDataClass());
    }
}