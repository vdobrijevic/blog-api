<?php

namespace App\Tests\Functional;

use App\Entity\BlogPost;
use App\Entity\User;
use App\Entity\VerificationRequest;
use App\Repository\UserRepository;
use App\Repository\VerificationRequestRepository;

trait ApiTestUtilities
{
    protected $client;
    protected $entityManager;
    protected $userRepository;
    protected $verificationRequestRepository;

    public function setUp(): void
    {
        $this->client = self::createClient();
        $this->entityManager = self::$container->get('doctrine')->getManager();
        $this->userRepository = self::$container->get(UserRepository::class);
        $this->verificationRequestRepository = self::$container->get(VerificationRequestRepository::class);
        $this->truncateDatabase();
    }

    protected function login(string $email, string $password): void
    {
        $this->client->request('POST', '/login', [
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);
    }

    protected function createUserThroughApi(string $email, string $password, string $firstName, string $lastName): void
    {
        $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => $email,
                'password' => $password,
                'firstName' => $firstName,
                'lastName' => $lastName,
            ],
        ]);
    }

    protected function createUserInDatabase(
        string $email,
        string $password,
        array $roles = [],
        string $firstName = 'Nobody',
        string $lastName = 'Cares'
    ): User {
        $user = new User($email, $password, $firstName, $lastName);
        $user->setRoles($roles);
        $this->store($user);

        return $user;
    }

    protected function createSampleUserInDatabase(): User
    {
        return $this->createUserInDatabase('nobody@example.com', 'whatever');
    }

    protected function createAndLoginSampleUser(): User
    {
        $user = $this->createUserInDatabase('nobody@example.com', 'whatever');
        $this->login('nobody@example.com', 'whatever');

        return $user;
    }

    protected function createAndLoginSampleAdmin(): void
    {
        $this->createUserInDatabase('lowlyadmin@example.com', 'whatever', ['ROLE_ADMIN']);
        $this->login('lowlyadmin@example.com', 'whatever');
    }

    protected function createAndLoginSampleBlogger(): User
    {
        $user = $this->createUserInDatabase('regular.john@example.com', 'plainpassword', ['ROLE_BLOGGER']);
        $this->login('regular.john@example.com', 'plainpassword');

        return $user;
    }

    protected function updateUserThroughApi(int $userId, array $payload): void
    {
        $this->client->request('PUT', sprintf('/api/users/%d', $userId), [
            'json' => $payload,
        ]);
    }

    protected function createBlogPostThroughApi(string $title, string $content, string $owner): void
    {
        $this->client->request('POST', '/api/blog_posts', [
            'json' => [
                'title' => $title,
                'content' => $content,
                'owner' => $owner,
            ],
        ]);
    }

    protected function updateBlogPostThroughApi(int $postId, array $payload): void
    {
        $this->client->request('PUT', sprintf('/api/blog_posts/%d', $postId), [
            'json' => $payload,
        ]);
    }

    protected function createBlogPostInDatabase(string $title, string $content, User $owner): BlogPost
    {
        $blogPost = new BlogPost($title, $content, $owner);
        $this->store($blogPost);

        return $blogPost;
    }

    protected function createSampleBlogPostInDatabase(): BlogPost
    {
        return $this->createBlogPostInDatabase(
            'Nobody reads this stuff',
            'I hope they hire me after reviewing this code',
            $this->createSampleUserInDatabase()
        );
    }

    protected function deleteBlogPostThroughApi(int $postId): void
    {
        $this->client->request('DELETE', sprintf('/api/blog_posts/%d', $postId));
    }

    protected function createVerificationRequestThroughApi(string $pidImage, User $owner): void
    {
        $this->client->request('POST', '/api/verification_requests', [
            'json' => [
                'pidImage' => $pidImage,
                'owner' => sprintf('/api/users/%d', $owner->getId()),
            ],
        ]);
    }

    protected function createOpenVerificationRequestInDatabase(User $owner): VerificationRequest
    {
        $verificationRequest = new VerificationRequest('some/image', $owner);
        $this->store($verificationRequest);

        return $verificationRequest;
    }

    protected function createClosedVerificationRequestInDatabase(User $owner, bool $approved = true): VerificationRequest
    {
        $verificationRequest = new VerificationRequest('some/image', $owner);
        $verificationRequest->setApproved($approved);
        $this->store($verificationRequest);

        return $verificationRequest;
    }

    protected function createSeveralVerificationRequestsWithDifferentCreationTimes(): void
    {
        $user = $this->createSampleUserInDatabase();
        // sleep is used to ensure two request aren't created within the same second, because the string representation
        // doesn't use milliseconds which makes less/greater than comparisons fail where only milliseconds differ
        $this->createClosedVerificationRequestInDatabase($user, false);
        sleep(1);
        $this->createClosedVerificationRequestInDatabase($user, false);
        sleep(1);
        $this->createOpenVerificationRequestInDatabase($user);
    }

    protected function updateVerificationRequestThroughApi(int $verReqId, array $payload): void
    {
        $this->client->request('PUT', sprintf('/api/verification_requests/%d', $verReqId), [
            'json' => $payload,
        ]);
    }

    protected function truncateDatabase()
    {
        $entities = [
            BlogPost::class,
            User::class,
            VerificationRequest::class,
        ];
        $connection = $this->entityManager->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
        }
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->entityManager->getClassMetadata($entity)->getTableName()
            );
            $connection->executeUpdate($query);
        }
        if ($databasePlatform->supportsForeignKeyConstraints()) {
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function store($entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
