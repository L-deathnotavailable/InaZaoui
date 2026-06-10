<?php

namespace App\Tests\Unit;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testAdminHasAdminRole(): void
    {
        $user = new User();
        $user->setAdmin(true);

        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testGuestHasUserRole(): void
    {
        $user = new User();
        $user->setAdmin(false);

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testUserIdentifierIsEmail(): void
    {
        $user = new User();
        $user->setEmail('invite1@example.com');

        self::assertSame('invite1@example.com', $user->getUserIdentifier());
    }

    public function testEmailCanBeChanged(): void
    {
        $user = new User();
        $user->setEmail('old@example.com');

        self::assertSame('old@example.com', $user->getEmail());

        $user->setEmail('new@example.com');

        self::assertSame('new@example.com', $user->getEmail());
    }

    public function testNameCanBeChanged(): void
    {
        $user = new User();
        $user->setName('Invité 1');

        self::assertSame('Invité 1', $user->getName());
    }

    public function testDescriptionCanBeChanged(): void
    {
        $user = new User();
        $user->setDescription('Description de test');

        self::assertSame('Description de test', $user->getDescription());
    }

    public function testPasswordCanBeChanged(): void
    {
        $user = new User();
        $user->setPassword('hashed-password');

        self::assertSame('hashed-password', $user->getPassword());
    }

    public function testBlockedFlagCanBeChanged(): void
    {
        $user = new User();

        self::assertFalse($user->isBlocked());

        $user->setBlocked(true);

        self::assertTrue($user->isBlocked());
    }

    public function testEraseCredentialsDoesNothingButDoesNotCrash(): void
    {
        $user = new User();

        $user->eraseCredentials();

        self::assertTrue(true);
    }

    public function testSaltIsNull(): void
    {
        $user = new User();

        self::assertNull($user->getSalt());
    }

    public function testUsernameCompatibilityReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('invite1@example.com');

        self::assertSame('invite1@example.com', $user->getUsername());
    }
}