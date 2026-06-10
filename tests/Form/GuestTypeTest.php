<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\GuestType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class GuestTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->formFactory = static::getContainer()->get(FormFactoryInterface::class);
    }

    public function testGuestTypeContainsExpectedFields(): void
    {
        $form = $this->formFactory->create(GuestType::class, new User(), [
            'csrf_protection' => false,
        ]);

        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('email'));
        self::assertTrue($form->has('description'));
        self::assertTrue($form->has('password'));
    }

    public function testGuestTypeSubmitValidData(): void
    {
        $user = new User();

        $form = $this->formFactory->create(GuestType::class, $user, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            'name' => 'Invité formulaire',
            'email' => 'invite-form@example.com',
            'description' => 'Description formulaire',
            'password' => 'test123',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertSame('Invité formulaire', $user->getName());
        self::assertSame('invite-form@example.com', $user->getEmail());
        self::assertSame('Description formulaire', $user->getDescription());
    }

    public function testGuestTypePasswordFieldIsNotMappedDirectlyToUser(): void
    {
        $user = new User();

        $form = $this->formFactory->create(GuestType::class, $user, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            'name' => 'Invité formulaire',
            'email' => 'invite-form@example.com',
            'description' => 'Description formulaire',
            'password' => 'test123',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertNull($user->getPassword());
        self::assertSame('test123', $form->get('password')->getData());
    }

    public function testGuestTypeDescriptionIsOptional(): void
    {
        $user = new User();

        $form = $this->formFactory->create(GuestType::class, $user, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            'name' => 'Invité sans description',
            'email' => 'invite-sans-description@example.com',
            'description' => '',
            'password' => 'test123',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertSame('Invité sans description', $user->getName());
        self::assertSame('invite-sans-description@example.com', $user->getEmail());
        self::assertNull($user->getDescription());
    }

    public function testGuestTypeUsesUserDataClass(): void
    {
        $form = $this->formFactory->create(GuestType::class, null, [
            'csrf_protection' => false,
        ]);

        self::assertSame(User::class, $form->getConfig()->getDataClass());
    }
}