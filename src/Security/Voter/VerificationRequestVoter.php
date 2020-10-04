<?php

namespace App\Security\Voter;

use App\Entity\VerificationRequest;
use App\Repository\VerificationRequestRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class VerificationRequestVoter extends Voter
{
    private $security;
    private $verificationRequestRepository;

    public function __construct(Security $security, VerificationRequestRepository $verificationRequestRepository)
    {
        $this->security = $security;
        $this->verificationRequestRepository = $verificationRequestRepository;
    }

    protected function supports($attribute, $subject)
    {
        return (in_array($attribute, ['REQUEST_VERIFICATION']) && null === $subject)
            || (in_array($attribute, ['EDIT_VERIFICATION_REQUEST']) && $subject instanceof VerificationRequest);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case 'REQUEST_VERIFICATION':
                return $this->canRequestVerification($user);
            case 'EDIT_VERIFICATION_REQUEST':
                return $this->canEditVerificationRequest($subject, $user);
            default:
                throw new \Exception(sprintf('Unhandled attribute: "%s"', $attribute));
        }

        return false;
    }

    private function canRequestVerification(UserInterface $user): bool
    {
        return $user->hasRole('ROLE_USER') && !$this->verificationRequestRepository->existsOpenForUser($user);
    }

    private function canEditVerificationRequest(VerificationRequest $verificationRequest, UserInterface $user): bool
    {
        return ($user == $verificationRequest->getOwner() || $this->security->isGranted('ROLE_ADMIN'))
            && $verificationRequest->isOpen();
    }
}
