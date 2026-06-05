<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Form\MediaType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/admin/media', name: 'admin_media_index')]
    public function index(Request $request)
    {
        $page = $request->query->getInt('page', 1);

        $criteria = [];

        if (!$this->isGranted('ROLE_ADMIN')) {
            $criteria['user'] = $this->getUser();
        }

        $medias = $this->doctrine->getRepository(Media::class)->findBy(
            $criteria,
            ['id' => 'ASC'],
            25,
            25 * ($page - 1)
        );

        $total = count($this->doctrine->getRepository(Media::class)->findBy([]));

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
        ]);
    }

    #[Route('/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request)
    {
        $media = new Media();

        $form = $this->createForm(
            MediaType::class,
            $media,
            ['is_admin' => $this->isGranted('ROLE_ADMIN')]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$this->isGranted('ROLE_ADMIN')) {
                $media->setUser($this->getUser());
            }

            $file = $media->getFile();

            if ($file === null) {
                $this->addFlash(
                    'danger',
                    'Veuillez sélectionner une image.'
                );

                return $this->redirectToRoute('admin_media_add');
            }

            $filename = md5(uniqid('', true)) . '.' . $file->guessExtension();

            try {
                $file->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads',
                    $filename
                );

                $media->setPath('uploads/' . $filename);

                $this->doctrine->getManager()->persist($media);
                $this->doctrine->getManager()->flush();

                $this->addFlash(
                    'success',
                    'Image ajoutée avec succès.'
                );
            } catch (FileException $e) {

                $this->addFlash(
                    'danger',
                    'Une erreur est survenue lors de l’upload du fichier.'
                );

                return $this->redirectToRoute('admin_media_add');
            }

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete')]
    public function delete(int $id)
    {
        $media = $this->doctrine->getRepository(Media::class)->find($id);

        if (!$media) {
            throw $this->createNotFoundException('Média introuvable.');
        }

        $filePath = $this->getParameter('kernel.project_dir')
            . '/public/'
            . $media->getPath();

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->doctrine->getManager()->remove($media);
        $this->doctrine->getManager()->flush();

        $this->addFlash(
            'success',
            'Image supprimée avec succès.'
        );

        return $this->redirectToRoute('admin_media_index');
    }
}