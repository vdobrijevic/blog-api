<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendVerificationRequestApprovalMail(string $emailAddress): void
    {
        $email = (new Email())
            ->from('info.blog.api@example.com')
            ->to($emailAddress)
            ->subject('You have been verified!')
            ->text('Congratulations! You can now proceed to write your first blog with us.')
        ;
        $this->mailer->send($email);
    }

    public function sendVerificationRequestRejectionMail(string $emailAddress, ?string $reason): void
    {
        $email = (new Email())
            ->from('info.blog.api@example.com')
            ->to($emailAddress)
            ->subject('Your verification request has been declined')
            ->text($this->getDeclinationText($reason))
        ;
        $this->mailer->send($email);
    }

    private function getDeclinationText(?string $reason): string
    {
        if (empty($reason)) {
            return 'We are sorry to inform you that your verification request has been declined.';
        }
        return sprintf(
            'We are sorry to inform you that your verification request has been declined for the following reason: %s',
            $reason
        );
    }
}
