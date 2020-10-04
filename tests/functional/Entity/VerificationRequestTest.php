<?php

namespace App\Tests\Functional\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestUtilities;
use Symfony\Contracts\HttpClient\ResponseInterface;

class VerificationRequestTest extends ApiTestCase
{
    use ApiTestUtilities;

    /**
     * @test
     *
     * @dataProvider verificationRequestCreationDataProvider
     */
    public function onlyRegisteredUnverifiedUsersShouldBeAbleToCreateVerificationRequests(
        array $roles,
        int $expectedResponseStatusCode
    ): void {
        $user = $this->createUserInDatabase('frogger@example.com', 'somuchtraffic', $roles);
        $this->login('frogger@example.com', 'somuchtraffic');

        $this->createVerificationRequestThroughApi('link/to/some/image', $user);

        $this->assertResponseStatusCodeSame($expectedResponseStatusCode);
    }

    public function verificationRequestCreationDataProvider(): array
    {
        return [
            [['ROLE_USER'], 201],
            [['ROLE_BLOGGER'], 403],
            [['ROLE_ADMIN'], 403],
            [['ROLE_SUPER_ADMIN'], 403],
        ];
    }

    /**
     * @test
     */
    public function usersShouldNotBeAbleToCreateANewVerificationRequestWhileTheyHaveAnOpenOne(): void
    {
        $user = $this->createAndLoginSampleUser();
        $this->createOpenVerificationRequestInDatabase($user);

        $this->createVerificationRequestThroughApi('link/to/some/image', $user);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @test
     *
     * @dataProvider verificationRequestListDataProvider
     */
    public function onlyAdminsShouldBeAbleToViewAListOfAllVerificationRequests(
        array $roles,
        int $expectedResponseStatusCode
    ): void {
        $user = $this->createSampleUserInDatabase();
        $verReq = $this->createOpenVerificationRequestInDatabase($user);
        $this->createUserInDatabase('tonic.huljic@example.com', 'hulj0f0n_rulz', $roles);
        $this->login('tonic.huljic@example.com', 'hulj0f0n_rulz');

        $this->client->request('GET', '/api/verification_requests');

        $this->assertResponseStatusCodeSame($expectedResponseStatusCode);
    }

    public function verificationRequestListDataProvider(): array
    {
        return [
            [['ROLE_USER'], 403],
            [['ROLE_BLOGGER'], 403],
            [['ROLE_ADMIN'], 200],
            [['ROLE_SUPER_ADMIN'], 200],
        ];
    }

    /**
     * @test
     */
    public function adminShouldBeAbleToFilterVerificationRequestsByExactStatusAndPartialUserDetails(): void
    {
        $user1 = $this->createUserInDatabase('bumblebee@example.com', 'flightofthe', [], 'Aram', 'Khachaturyan');
        $user2 = $this->createUserInDatabase('dj.skiljo@example.com', 'karaokeman', [], 'DJ', 'Škiljo');
        $user3 = $this->createUserInDatabase('bumblebee.dj@example.com', 'wuteva', [], 'DJ', 'Khachatovsky');
        $this->createClosedVerificationRequestInDatabase($user1, false);
        $this->createOpenVerificationRequestInDatabase($user1);
        $this->createClosedVerificationRequestInDatabase($user2, true);
        $this->createOpenVerificationRequestInDatabase($user3);
        $this->createAndLoginSampleAdmin();

        $response = $this->client->request('GET', '/api/verification_requests?status=verification_requested');
        $this->assertNumberOfCollectionMembersMatches($response, 2, 'Expected 2 open verification requests');
        $this->assertAllCollectionMembersHaveExactFieldValue($response, 'status', 'verification_requested');

        $response = $this->client->request('GET', '/api/verification_requests?status=approved');
        $this->assertNumberOfCollectionMembersMatches($response, 1, 'Expected 1 approved verification request');
        $this->assertAllCollectionMembersHaveExactFieldValue($response, 'status', 'approved');

        $response = $this->client->request('GET', '/api/verification_requests?status=declined');
        $this->assertNumberOfCollectionMembersMatches($response, 1, 'Expected 1 declined verification request');
        $this->assertAllCollectionMembersHaveExactFieldValue($response, 'status', 'declined');

        $response = $this->client->request('GET', '/api/verification_requests?owner.email=dj');
        $this->assertNumberOfCollectionMembersMatches(
            $response,
            2,
            'Expected 2 verification requests for users with "dj" in email'
        );
        $this->assertAllCollectionChildMembersHavePartialFieldValue($response, 'owner', 'email', 'dj');

        $response = $this->client->request('GET', '/api/verification_requests?owner.firstName=Aram');
        $this->assertNumberOfCollectionMembersMatches(
            $response,
            2,
            'Expected 2 verification requests for users with "Aram" in first name'
        );
        $this->assertAllCollectionChildMembersHavePartialFieldValue($response, 'owner', 'firstName', 'Aram');

        $response = $this->client->request('GET', '/api/verification_requests?owner.lastName=Khachat');
        $this->assertNumberOfCollectionMembersMatches(
            $response,
            3,
            'Expected 3 verification requests for users with "Khachat" in last name'
        );
        $this->assertAllCollectionChildMembersHavePartialFieldValue($response, 'owner', 'lastName', 'Khachat');
    }

    /**
     * @test
     */
    public function adminShouldBeAbleToOrderVerificationRequestsByCreationDate(): void
    {
        $this->createSeveralVerificationRequestsWithDifferentCreationTimes();
        $this->createAndLoginSampleAdmin();

        $response = $this->client->request('GET', '/api/verification_requests?order[created]=asc');
        $this->assertMembersAreOrderedChronologicallyAscending($response, 'created');

        $response = $this->client->request('GET', '/api/verification_requests?order[created]=desc');
        $this->assertMembersAreOrderedChronologicallyDescending($response, 'created');
    }

    /**
     * @test
     */
    public function usersShouldBeAbleToUpdateTheirOwnOpenVerificationRequests(): void
    {
        $user = $this->createAndLoginSampleUser();
        $verReq = $this->createOpenVerificationRequestInDatabase($user);

        $this->updateVerificationRequestThroughApi($verReq->getId(), ['pidImage' => 'link/to/a/better/image']);

        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     */
    public function usersShouldNotBeAbleToUpdateClosedVerificationRequests(): void
    {
        $this->markTestIncomplete('Figure out why the user loses authentication in this test');
        $user = $this->createAndLoginSampleUser();
        $verReq = $this->createClosedVerificationRequestInDatabase($user);

        $this->updateVerificationRequestThroughApi($verReq->getId(), ['pidImage' => 'link/to/a/better/image']);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @test
     *
     * @dataProvider verificationRequestClosingDataProvider
     */
    public function onlyAdminsShouldBeAbleToApproveOrDeclineVerificationRequests(
        array $roles,
        bool $approved,
        int $expectedResponseStatusCode
    ): void {
        $user = $this->createSampleUserInDatabase();
        $verReq = $this->createOpenVerificationRequestInDatabase($user);
        $this->createUserInDatabase('lemmy.is.god@example.com', '0rg4sm4tr0n', $roles);
        $this->login('lemmy.is.god@example.com', '0rg4sm4tr0n');

        $this->updateVerificationRequestThroughApi($verReq->getId(), ['approved' => $approved]);

        $this->assertResponseStatusCodeSame($expectedResponseStatusCode);
    }

    public function verificationRequestClosingDataProvider(): array
    {
        return [
            [['ROLE_USER'], true, 403],
            [['ROLE_BLOGGER'], false, 403],
            [['ROLE_ADMIN'], false, 200],
            [['ROLE_SUPER_ADMIN'], true, 200],
        ];
    }

    /**
     * @test
     */
    public function whenVerificationRequestIsApprovedUserShouldGetBloggerRole(): void
    {
        $user = $this->createSampleUserInDatabase();
        $verReq = $this->createOpenVerificationRequestInDatabase($user);
        $this->createAndLoginSampleAdmin();

        $this->updateVerificationRequestThroughApi($verReq->getId(), ['approved' => true]);
        $updatedVerReq = $this->verificationRequestRepository->find($verReq->getId());
        $updatedUser = $this->userRepository->find($user->getId());

        $this->assertSame('approved', $updatedVerReq->getStatus());
        $this->assertContains('ROLE_BLOGGER', $updatedUser->getRoles());
    }

    private function assertNumberOfCollectionMembersMatches(
        ResponseInterface $response,
        int $expectedNumber,
        string $failMessage = ''
    ): void {
        $this->assertCount($expectedNumber, $this->getMembersFromResponse($response), $failMessage);
    }

    private function assertAllCollectionMembersHaveExactFieldValue(
        ResponseInterface $response,
        string $fieldName,
        $value
    ): void {
        $this->assertResponseStatusCodeSame(200);
        foreach ($this->getMembersFromResponse($response) as $member) {
            $this->assertSame($value, $member[$fieldName]);
        }
    }

    private function assertAllCollectionChildMembersHavePartialFieldValue(
        ResponseInterface $response,
        string $parentFieldName,
        string $childFieldName,
        $value
    ): void {
        $this->assertResponseStatusCodeSame(200);
        foreach ($this->getMembersFromResponse($response) as $member) {
            $this->assertStringContainsString($value, $member[$parentFieldName][$childFieldName]);
        }
    }

    private function assertMembersAreOrderedChronologicallyAscending(
        ResponseInterface $response,
        string $fieldName,
        string $failMessage = ''
    ): void {
        $this->assertMembersAreOrderedChronologically($response, $fieldName, true, $failMessage);
    }

    private function assertMembersAreOrderedChronologicallyDescending(
        ResponseInterface $response,
        string $fieldName,
        string $failMessage = ''
    ): void {
        $this->assertMembersAreOrderedChronologically($response, $fieldName, false, $failMessage);
    }

    private function assertMembersAreOrderedChronologically(
        ResponseInterface $response,
        string $fieldName,
        bool $asc,
        string $failMessage = ''
    ): void {
        $this->assertResponseStatusCodeSame(200);
        $members = $this->getMembersFromResponse($response);
        for ($i = 0; $i < count($members) - 2; $i++) {
            if ($asc) {
                $this->assertLessThan(
                    $members[$i + 1][$fieldName], $members[$i][$fieldName],
                    'Members are not in a chronologically ascending order'
                );
            } else {
                $this->assertGreaterThan(
                    $members[$i + 1][$fieldName], $members[$i][$fieldName],
                    'Members are not in a chronologically descending order'
                );
            }
        }
    }

    private function getMembersFromResponse(ResponseInterface $response): array
    {
        return json_decode($response->getContent(), true)['hydra:member'];
    }
}
