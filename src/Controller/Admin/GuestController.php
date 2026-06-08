<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class GuestController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/admin/guests', name: 'admin_guest_index')]
    public function index()
    {
        $guests = $this->doctrine->getRepository(User::class)->findBy(
            ['admin' => false],
            ['name' => 'ASC']
        );

        return $this->render('admin/guest/index.html.twig', [
            'guests' => $guests,
        ]);
    }

    #[Route('/admin/guests/add', name: 'admin_guest_add')]
    public function add(Request $request)
    {
        $guest = new User();
        $form = $this->createForm(GuestType::class, $guest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $guest->setAdmin(false);
            $guest->setBlocked(false);
            $guest->setPassword($this->passwordHasher->hashPassword($guest, (string) $form->get('password')->getData()));

            $this->doctrine->getManager()->persist($guest);
            $this->doctrine->getManager()->flush();

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/guests/{id}/block', name: 'admin_guest_block')]
    public function block(int $id)
    {
        $guest = $this->doctrine->getRepository(User::class)->find($id);

        if (!$guest || $guest->isAdmin()) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        $guest->setBlocked(!$guest->isBlocked());
        $this->doctrine->getManager()->flush();

        $this->addFlash(
            'success',
            $guest->isBlocked()
                ? 'Invité bloqué avec succès.'
                : 'Invité débloqué avec succès.'
        );

        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/admin/guests/delete/{id}', name: 'admin_guest_delete')]
    public function delete(int $id): Response
    {
        $entityManager = $this->doctrine->getManager();

        $guest = $this->doctrine->getRepository(User::class)->find($id);

        if (!$guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        if ($guest->isAdmin()) {
            throw $this->createAccessDeniedException('Impossible de supprimer un administrateur.');
        }

        foreach ($guest->getMedias() as $media) {
            $filePath = $this->getParameter('kernel.project_dir')
                . '/public/'
                . $media->getPath();

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $entityManager->remove($media);
        }

        $entityManager->remove($guest);
        $entityManager->flush();

        $this->addFlash('success', 'Invité et médias associés supprimés.');

        return $this->redirectToRoute('admin_guest_index');
    }
}