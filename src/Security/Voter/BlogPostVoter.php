<?php

namespace App\Security\Voter;

use App\Entity\BlogPost;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class BlogPostVoter extends Voter
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, ['POST_EDIT', 'POST_DELETE']) && $subject instanceof BlogPost;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case 'POST_EDIT':
            case 'POST_DELETE':
                return $this->isOwnerOrAdmin($subject, $user);
            default:
                throw new \Exception(sprintf('Unhandled attribute: "%s"', $attribute));
        }

        return false;
    }

    private function isOwnerOrAdmin($subject, UserInterface $user): bool
    {
        if ($user == $subject->getOwner()) {
            return true;
        }
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return false;
    }
}
