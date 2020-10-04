<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Entity\VerificationRequest;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;

class VerificationRequestDataPersister implements DataPersisterInterface
{
    private $decoratedDataPersister;
    private $entityManager;
    private $notificationService;

    public function __construct(
        DataPersisterInterface $decoratedDataPersister,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ) {
        $this->decoratedDataPersister = $decoratedDataPersister;
        $this->entityManager = $entityManager;
        $this->notificationService = $notificationService;
    }

    public function supports($data): bool
    {
        return $data instanceof VerificationRequest;
    }

    public function persist($data)
    {
        if ($data->isNewEntity()) {
            return $this->decoratedDataPersister->persist($data);
        }

        $originalData = $this->entityManager->getUnitOfWork()->getOriginalEntityData($data);
        if ('verification_requested' === $originalData['status'] && $data->isApproved()) {
            $this->notificationService->sendVerificationRequestApprovalMail($data->getOwner()->getEmail());
        }
        if ('verification_requested' === $originalData['status'] && $data->isDeclined()) {
            $this->notificationService->sendVerificationRequestRejectionMail(
                $data->getOwner()->getEmail(),
                $data->getRejectionReason()
            );
        }

        return $this->decoratedDataPersister->persist($data);
    }

    public function remove($data)
    {
        return $this->decoratedDataPersister->remove($data);
    }
}
