<?php

namespace App\Tests\Controller;

use App\Tests\HelperLoginTrait;
use App\DataFixtures\TaskFixtures;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\Response;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskDeleteControllerTest extends WebTestCase
{
    use FixturesTrait;
    use HelperLoginTrait;

    /**
     * @var string
     */
    private $route;

    public function setUp()
    {
        $this->route = '/tasks/1/delete';
        $this->loadFixtures([TaskFixtures::class]);
    }

    public function testRedirectToLogin()
    {
        $client = static::createClient();
        $client->request('GET', $this->route);
        $this->assertResponseRedirects('/login');
    }

    public function testAccessWithGoodAuthor()
    {
        $client = $this->login('user');

        $client->request('GET', $this->route);
        $this->assertResponseRedirects('/tasks');
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('.alert.alert-success');
        
        $task = self::$container->get(TaskRepository::class)->findOneByTitle('Un titre rédigé par l\'user 1...');
        $this->assertEmpty($task);
    }

    public function testDeniedAccessWithBadAuthor()
    {
        $client = $this->login('user2');

        $client->request('GET', $this->route);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $task = self::$container->get(TaskRepository::class)->findOneByTitle('Un titre rédigé par l\'user 1...');
        $this->assertNotEmpty($task);
    }
}
