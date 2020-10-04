<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\VerificationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VerificationRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method VerificationRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method VerificationRequest[]    findAll()
 * @method VerificationRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerificationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerificationRequest::class);
    }

    public function store(VerificationRequest $verificationRequest): void
    {
        $this->entityManager->persist($verificationRequest);
        $this->entityManager->flush();
    }

    public function existsOpenForUser(User $user): bool
    {
        $result = $this->createQueryBuilder('v')
            ->andWhere('v.owner = :owner')
            ->andWhere('v.status = :status')
            ->setParameters([
                'owner' => $user,
                'status' => 'verification_requested',
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result !== null;
    }
}
