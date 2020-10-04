<?php

namespace App\Tests\Functional\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestUtilities;

class BlogPostTest extends ApiTestCase
{
    use ApiTestUtilities;

    /**
     * @test
     */
    public function anonymousUsersShouldBeAllowedToViewAListOfBlogPosts(): void
    {
        $this->client->request('GET', '/api/blog_posts');
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     */
    public function anonymousUsersShouldBeAllowedToViewIndividualBlogPosts(): void
    {
        $blogPost = $this->createSampleBlogPostInDatabase();
        $this->client->request('GET', sprintf('/api/blog_posts/%d', $blogPost->getId()));
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     */
    public function anonymousUsersShouldNotBeAllowedToCreateBlogPosts(): void
    {
        $this->createBlogPostThroughApi('Mwahaha', 'All your base is belong to us', 'api/users/hacker.wannabe');
        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @test
     *
     * @dataProvider listOfAllUsersDataProvider
     */
    public function onlyBloggersShouldBeAllowedToCreateBlogPosts(array $roles, int $expectedResponseCode): void
    {
        $user = $this->createUserInDatabase('kaiser.franz@example.com', 'lotharwho?', $roles);
        $this->login('kaiser.franz@example.com', 'lotharwho?');

        $this->createBlogPostThroughApi(
            'Deutsche Fussballbund',
            'Joachim Loew should step down',
            sprintf('api/users/%d', $user->getId())
        );

        $this->assertResponseStatusCodeSame($expectedResponseCode);
    }

    public function listOfAllUsersDataProvider(): array
    {
        return [
            // role hierarchy ensures admins are also bloggers
            [['ROLE_SUPER_ADMIN'], 201],
            [['ROLE_ADMIN'], 201],
            [['ROLE_BLOGGER'], 201],
            [['ROLE_USER'], 403],
        ];
    }

    /**
     * @test
     */
    public function blogPostValuesShouldBeTrimmedOnCreation(): void
    {
        $user = $this->createAndLoginSampleBlogger();

        $this->createBlogPostThroughApi(
            '  My issues with typing',
            ' I tend to lean on the space bar, also I sometimes accidentally hit tab   ',
            sprintf('api/users/%d', $user->getId())
        );

        $this->assertJsonContains([
            'title' => 'My issues with typing',
            'content' => 'I tend to lean on the space bar, also I sometimes accidentally hit tab',
        ]);
    }

    /**
     * @test
     */
    public function bloggersShouldBeAllowedToDeleteTheirOwnBlogPosts(): void
    {
        $owner = $this->createUserInDatabase('ikillyou@example.com', 'youdie', ['ROLE_BLOGGER']);
        $blogPost = $this->createBlogPostInDatabase('Morituri', 'Those about to die', $owner);

        $this->login('ikillyou@example.com', 'youdie');
        $this->deleteBlogPostThroughApi($blogPost->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    /**
     * @test
     */
    public function bloggersShouldNotBeAllowedToDeleteOthersBlogPosts(): void
    {
        $owner = $this->createSampleUserInDatabase();
        $blogPost = $this->createBlogPostInDatabase('Morituri', 'Those about to die', $owner);
        $notOwner = $this->createUserInDatabase('notmine@example.com', 'neverwas', ['ROLE_BLOGGER']);

        $this->login('notmine@example.com', 'neverwas');
        $this->deleteBlogPostThroughApi($blogPost->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @test
     *
     * @testWith ["ROLE_ADMIN"]
     *           ["ROLE_SUPER_ADMIN"]
     */
    public function adminsShouldBeAllowedToDeleteAnyonesBlogPosts(string $role): void
    {
        $owner = $this->createSampleUserInDatabase();
        $blogPost = $this->createBlogPostInDatabase('Morituri', 'Those about to die', $owner);
        $admin = $this->createUserInDatabase('ad.min.song@example.com', 'kor34', [$role]);

        $this->login('ad.min.song@example.com', 'kor34');
        $this->deleteBlogPostThroughApi($blogPost->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    /**
     * @test
     */
    public function bloggersShouldBeAllowedToUpdateTheirOwnBlogPosts(): void
    {
        $owner = $this->createUserInDatabase('premisljator@example.com', 'nisamsiguran', ['ROLE_BLOGGER']);
        $blogPost = $this->createBlogPostInDatabase('Glup naslov', 'Tekst je neloš', $owner);

        $this->login('premisljator@example.com', 'nisamsiguran');
        $this->updateBlogPostThroughApi($blogPost->getId(), ['title' => 'Dobar naslov']);

        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     */
    public function bloggersShouldNotBeAllowedToUpdateOthersBlogPosts(): void
    {
        $owner = $this->createSampleUserInDatabase();
        $blogPost = $this->createBlogPostInDatabase('Ocean floor', 'I know stuff about this topic', $owner);
        $notOwner = $this->createUserInDatabase('smartass@example.com', 'superior', ['ROLE_BLOGGER']);

        $this->login('smartass@example.com', 'superior');
        $this->updateBlogPostThroughApi($blogPost->getId(), ['content' => 'I know better than you']);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @test
     *
     * @testWith ["ROLE_ADMIN"]
     *           ["ROLE_SUPER_ADMIN"]
     */
    public function adminsShouldBeAllowedToUpdateAnyonesBlogPosts(string $role): void
    {
        $owner = $this->createSampleUserInDatabase();
        $blogPost = $this->createBlogPostInDatabase('Živciraju me ljudi', 'Gomila beštimi', $owner);
        $admin = $this->createUserInDatabase('ad.min.song@example.com', 'kor34', [$role]);

        $this->login('ad.min.song@example.com', 'kor34');
        $this->updateBlogPostThroughApi($blogPost->getId(), ['content' => 'Ne smi se beštimat']);

        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @test
     */
    public function blogPostValuesShouldBeTrimmedOnEditing(): void
    {
        $user = $this->createAndLoginSampleBlogger();
        $blogPost = $this->createBlogPostInDatabase('A very clean title', 'And a perfectly formatted content', $user);

        $this->updateBlogPostThroughApi(
            $blogPost->getId(),
            ['title' => ' I became spacebar happy   ', 'content' => '   A badly mangled update   ']
        );

        $this->assertJsonContains([
            'title' => 'I became spacebar happy',
            'content' => 'A badly mangled update',
        ]);
    }
}
