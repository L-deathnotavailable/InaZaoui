<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private ManagerRegistry $doctrine;
    private UserRepository $userRepository;

    public function __construct(
        ManagerRegistry $doctrine,
        UserRepository $userRepository
    ) {
        $this->doctrine = $doctrine;
        $this->userRepository = $userRepository;
    }

    #[Route('/', name: 'home')]
    public function home()
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/guests', name: 'guests')]
    public function guests()
    {
        $guests = $this->userRepository->findActiveGuestsWithMediaCount();

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
        ]);
    }

    #[Route('/guest/{id}', name: 'guest')]
    public function guest(int $id)
    {
        $guest = $this->doctrine->getRepository(User::class)->findOneBy([
            'id' => $id,
            'admin' => false,
            'blocked' => false,
        ]);

        if (!$guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        return $this->render('front/guest.html.twig', [
            'guest' => $guest,
        ]);
    }

    #[Route('/portfolio/{id}', name: 'portfolio')]
    public function portfolio(?int $id = null)
    {
        $albums = $this->doctrine->getRepository(Album::class)->findAll();
        $album = $id ? $this->doctrine->getRepository(Album::class)->find($id) : null;
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['admin' => true]);

        $medias = $album
            ? $this->doctrine->getRepository(Media::class)->findBy(['album' => $album])
            : $this->doctrine->getRepository(Media::class)->findBy(['user' => $user]);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias,
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about()
    {
        return $this->render('front/about.html.twig');
    }
}