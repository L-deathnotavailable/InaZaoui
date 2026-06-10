<?php

namespace App\Tests\Unit;

use App\Entity\Album;
use PHPUnit\Framework\TestCase;

class AlbumTest extends TestCase
{
    public function testNameCanBeChanged(): void
    {
        $album = new Album();

        $album->setName('Album test');

        self::assertSame('Album test', $album->getName());
    }

    public function testIdIsNullBeforePersist(): void
    {
        $album = new Album();

        self::assertNull($album->getId());
    }
}