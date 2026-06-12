<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Retourne les invités actifs avec le nombre de médias associés.
     *
     * Cette méthode évite de charger la collection complète des médias
     * pour chaque invité dans Twig avec guest.medias|length.
     *
     * @return array<int, array{guest: User, mediaCount: int}>
     */
    public function findActiveGuestsWithMediaCount(): array
    {
        $results = $this->createQueryBuilder('u')
            ->select('u AS guest')
            ->addSelect('COUNT(m.id) AS mediaCount')
            ->leftJoin('u.medias', 'm')
            ->andWhere('u.admin = :admin')
            ->andWhere('u.blocked = :blocked')
            ->setParameter('admin', false)
            ->setParameter('blocked', false)
            ->groupBy('u.id')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(static function (array $row): array {
            return [
                'guest' => $row['guest'],
                'mediaCount' => (int) $row['mediaCount'],
            ];
        }, $results);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}