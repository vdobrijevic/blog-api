<?php

namespace App\Tests\Functional\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestUtilities;

class UserTest extends ApiTestCase
{
    use ApiTestUtilities;

    /**
     * @test
     */
    public function userRegistrationShouldBeAccessibleToAnonymousUsers(): void
    {
        $this->createUserThroughApi('sime.torcida@example.com', 'bogihrvati', 'Šime', 'Kralj');
        $this->assertResponseStatusCodeSame(201);
    }

    /**
     * @test
     */
    public function usersShouldBeCreatedWithTrimmedValues(): void
    {
        $this->createUserThroughApi('letebaze@example.com ', ' tibief ', ' Tomo', ' Tomić');
        $this->assertJsonContains(['email' => 'letebaze@example.com', 'firstName' => 'Tomo', 'lastName' => 'Tomić']);

        // password trimming tested through login because it's not in the read group
        $this->login('letebaze@example.com', 'tibief');
        $this->assertResponseStatusCodeSame(204);
    }

    /**
     * @test
     */
    public function loginWithValidCredentials(): void
    {
        $this->createUserInDatabase('mc.zlaja@example.com', 'maci');
        $this->login('mc.zlaja@example.com', 'maci');
        $this->assertResponseStatusCodeSame(204);
    }

    /**
     * @test
     */
    public function loginWithInvalidCredentials(): void
    {
        $this->createUserInDatabase('kikaizkafica@example.com', 'samokava');
        $this->login('kikaizkafica@example.com', 'samokaba');
        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @test
     */
    public function anonymousUsersShouldBeAllowedToViewAUserProfile(): void
    {
        $user = $this->createSampleUserInDatabase();
        $this->client->request('GET', sprintf('/api/users/%d', $user->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     */
    public function anonymousUsersShouldNotBeAllowedToSeeAListOfAllUsers(): void
    {
        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @test
     *
     * @dataProvider listOfAllUsersDataProvider
     */
    public function onlyAdminsShouldBeAllowedToViewAListOfAllUsers(array $roles, int $expectedResponseCode): void
    {
        $this->createUserInDatabase('admini.ukechukwu@example.com', 'UKEstrator', $roles);
        $this->login('admini.ukechukwu@example.com', 'UKEstrator');
        $this->client->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame($expectedResponseCode);
    }

    public function listOfAllUsersDataProvider(): array
    {
        return [
            [['ROLE_SUPER_ADMIN'], 200],
            [['ROLE_ADMIN'], 200],
            [['ROLE_BLOGGER'], 403],
            [['ROLE_USER'], 403],
        ];
    }

    /**
     * @test
     */
    public function usersShouldBeAllowedToUpdateTheirOwnDetails(): void
    {
        $user = $this->createUserInDatabase('ciprian.tat@example.com', 'goalkeeper', [], 'Ciprian', 'Tatarusanu');

        $this->login('ciprian.tat@example.com', 'goalkeeper');
        $this->updateUserThroughApi($user->getId(), ['lastName' => 'Tătărușanu']);

        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     */
    public function usersShouldNotBeAllowedToUpdateOthersDetails(): void
    {
        $user = $this->createUserInDatabase('tony.adams@example.com', 'arsenallegend', [], 'Tony', 'Adams');
        $this->createAndLoginSampleUser();

        $this->updateUserThroughApi($user->getId(), ['firstName' => 'Anthony']);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @test
     *
     * @testWith ["ROLE_ADMIN"]
     *           ["ROLE_SUPER_ADMIN"]
     */
    public function adminsShouldBeAllowedToUpdateAnyonesDetails(string $role): void
    {
        $user = $this->createSampleUserInDatabase();
        $admin = $this->createUserInDatabase('adi.mini@example.com', 'bezze', [$role]);

        $this->login('adi.mini@example.com', 'bezze');
        $this->updateUserThroughApi($user->getId(), ['email' => 'usersnewemail@example.com']);

        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     */
    public function usersShouldBeAllowedToChangeTheirPassword(): void
    {
        $user = $this->createUserInDatabase('mini-me@example.com', '1million$$$');
        $this->login('mini-me@example.com', '1million$$$');

        $this->updateUserThroughApi($user->getId(), ['password' => '1billion$$$']);

        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     *
     * @testWith ["ROLE_ADMIN"]
     *           ["ROLE_SUPER_ADMIN"]
     */
    public function adminShouldBeAllowedToChangeSomeoneElsesPassword(string $role): void
    {
        $user = $this->createSampleUserInDatabase();
        $admin = $this->createUserInDatabase('administratus@example.com', 'jamogusve', [$role]);
        $this->login('administratus@example.com', 'jamogusve');

        $this->updateUserThroughApi($user->getId(), ['password' => 'tisiobicniuser']);

        $this->assertResponseStatusCodeSame(200);
    }
}
