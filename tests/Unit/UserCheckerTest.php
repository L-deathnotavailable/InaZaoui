<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserCheckerTest extends TestCase
{
    public function testActiveUserCanAuthenticate(): void
    {
        $checker = new UserChecker();

        $user = new User();
        $user->setBlocked(false);

        $checker->checkPreAuth($user);

        self::assertTrue(true);
    }

    public function testBlockedUserCannotAuthenticate(): void
    {
        $checker = new UserChecker();

        $user = new User();
        $user->setBlocked(true);

        $this->expectException(CustomUserMessageAccountStatusException::class);

        $checker->checkPreAuth($user);
    }

    public function testPostAuthDoesNotCrash(): void
    {
        $checker = new UserChecker();

        $user = new User();

        $checker->checkPostAuth($user);

        self::assertTrue(true);
    }
}