<?php

namespace App\DataFixtures;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private ParameterBagInterface $params
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $uploadPaths = $this->getExistingUploadPaths();

        $admin = new User();
        $admin->setName('Ina Zaoui');
        $admin->setEmail('ina@zaoui.com');
        $admin->setDescription("Photographe à l'objectif errant des horizons sans limites, parcourt le globe uniquement au gré des murmures de la nature, capturant le souffle du monde dans des cadres silencieux.");
        $admin->setAdmin(true);
        $admin->setBlocked(false);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'test123')
        );

        $manager->persist($admin);

        $albums = [];

        for ($i = 1; $i <= 5; $i++) {
            $album = new Album();
            $album->setName('Album '.$i);

            $manager->persist($album);
            $albums[] = $album;
        }

        $invite1 = $this->createGuest(
            name: 'Invité 1',
            email: 'invite1@example.com',
            password: 'test123',
            blocked: false
        );

        $invite2 = $this->createGuest(
            name: 'Invité 2',
            email: 'invite2@example.com',
            password: 'test123',
            blocked: false
        );

        $blockedGuest = $this->createGuest(
            name: 'Invité bloqué',
            email: 'blocked@example.com',
            password: 'test123',
            blocked: true
        );

        $manager->persist($invite1);
        $manager->persist($invite2);
        $manager->persist($blockedGuest);

        for ($i = 1; $i <= 50; $i++) {
            $media = new Media();
            $media->setUser($invite1);
            $media->setTitle('Média invité 1 - '.$i);
            $media->setPath($this->pickImagePath($uploadPaths, $i));

            $manager->persist($media);
        }

        for ($i = 1; $i <= 3; $i++) {
            $media = new Media();
            $media->setUser($invite2);
            $media->setTitle('Média invité 2 - '.$i);
            $media->setPath($this->pickImagePath($uploadPaths, $i + 50));

            $manager->persist($media);
        }

        for ($i = 1; $i <= 5; $i++) {
            $media = new Media();
            $media->setUser($admin);
            $media->setAlbum($albums[0]);
            $media->setTitle('Média portfolio - '.$i);
            $media->setPath($this->pickImagePath($uploadPaths, $i + 60));

            $manager->persist($media);
        }

        $manager->flush();
    }

    private function createGuest(
        string $name,
        string $email,
        string $password,
        bool $blocked
    ): User {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setDescription('Description de test pour '.$name);
        $user->setAdmin(false);
        $user->setBlocked($blocked);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );

        return $user;
    }

    /**
     * Récupère automatiquement les images réellement présentes dans public/uploads.
     *
     * @return string[]
     */
    private function getExistingUploadPaths(): array
    {
        $uploadsDirectory = $this->params->get('kernel.project_dir').'/public/uploads';

        if (!is_dir($uploadsDirectory)) {
            return [];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        $files = array_filter(scandir($uploadsDirectory), function (string $file) use ($uploadsDirectory, $allowedExtensions): bool {
            $path = $uploadsDirectory.'/'.$file;

            if (!is_file($path)) {
                return false;
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            return in_array($extension, $allowedExtensions, true);
        });

        return array_values(array_map(
            fn (string $file): string => 'uploads/'.$file,
            $files
        ));
    }

    /**
     * Sélectionne une image existante.
     * Si le nombre de médias dépasse le nombre d'images disponibles,
     * on réutilise les mêmes images en boucle.
     */
    private function pickImagePath(array $uploadPaths, int $index): string
    {
        if ($uploadPaths === []) {
            return 'uploads/placeholder.jpg';
        }

        return $uploadPaths[($index - 1) % count($uploadPaths)];
    }
}