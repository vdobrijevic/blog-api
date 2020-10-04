<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Controller\UserVerificationApproval;
use App\Repository\VerificationRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"verification_request:read"}},
 *     denormalizationContext={"groups"={"verification_request:write"}},
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_ADMIN') or object.owner == user"},
 *         "put"={"security"="is_granted('EDIT_VERIFICATION_REQUEST', object)"}
 *     },
 *     collectionOperations={
 *         "get"={"security"="is_granted('ROLE_ADMIN')"},
 *         "post"={"security"="is_granted('REQUEST_VERIFICATION')"}
 *     }
 * )
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *         "status": "exact",
 *         "owner.email": "partial",
 *         "owner.firstName": "partial",
 *         "owner.lastName": "partial",
 *     }
 * )
 * @ApiFilter(OrderFilter::class, properties={"created"}, arguments={"orderParameterName"="order"})
 *
 * @ORM\Entity(repositoryClass=VerificationRequestRepository::class)
 */
class VerificationRequest
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @Groups({"verification_request:read"})
     */
    private $status;

    /**
     * URL to the image of a personal identification document.
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Groups({"verification_request:read", "verification_request:write"})
     *
     * @Assert\NotBlank()
     */
    private $pidImage;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"verification_request:read", "admin:write"})
     */
    private $rejectionReason;

    /**
     * @ORM\Column(type="datetime_immutable")
     *
     * @Groups({"verification_request:read"})
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="verificationRequests")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Groups({"verification_request:read", "verification_request:write"})
     */
    private $owner;

    public function __construct(string $pidImage, User $owner)
    {
        $this->status = 'verification_requested';
        $this->pidImage = $pidImage;
        $this->owner = $owner;
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isNewEntity(): bool
    {
        return null === $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isOpen(): bool
    {
        return 'verification_requested' === $this->status;
    }

    public function isApproved(): bool
    {
        return 'approved' === $this->status;
    }

    public function isDeclined(): bool
    {
        return 'declined' === $this->status;
    }

    /**
     * @Groups({"admin:write"})
     */
    public function setApproved(bool $approved): self
    {
        if ($approved) {
            $this->status = 'approved';
            $this->owner->setRoles(['ROLE_BLOGGER']);
        } else {
            $this->status = 'declined';
        }

        return $this;
    }

    public function getPidImage(): string
    {
        return $this->pidImage;
    }

    public function setPidImage(string $pidImage): self
    {
        $this->pidImage = $pidImage;

        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;

        return $this;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }
}
