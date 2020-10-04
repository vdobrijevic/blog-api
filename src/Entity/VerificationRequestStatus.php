<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class VerificationRequestStatus
{
    private const SUPPORTED = [
        'verification_requested',
        'approved',
        'declined',
    ];

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $status;

    private function __construct(string $status)
    {
        if (!in_array($status, self::SUPPORTED)) {
            throw new \InvalidArgumentException(sprintf('Unsupported verification request status: "%s"', $status));
        }
        $this->status = $status;
    }

    public static function createVerificationRequested(): self
    {
        return new self('verification_requested');
    }

    public static function createApproved(): self
    {
        return new self('approved');
    }

    public static function createDeclined(): self
    {
        return new self('declined');
    }

    public function isOpen(): bool
    {
        return 'verification_requested' === $this->status;
    }

    public function toString(): string
    {
        return $this->status;
    }
}
