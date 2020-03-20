<?php

namespace App\Tests\Controller;

use App\Tests\HelperLoginTrait;
use App\DataFixtures\UserFixtures;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserEditControllerTest extends WebTestCase
{
    use FixturesTrait;
    use HelperLoginTrait;

    /**
     * @var string
     */
    private $route;

    public function setUp()
    {
        $this->route = '/users/2/edit';
        $this->loadFixtures([UserFixtures::class]);
    }

    public function testRedirectToLogin()
    {
        $client = static::createClient();
        $client->request('GET', $this->route);
        $this->assertResponseRedirects('/login');
    }

    public function testAccessWithAdmin()
    {
        $client = $this->login('admin');

        $client->request('GET', $this->route);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h1', 'Modifier un utilisateur');
    }

    public function testDeniedAccessWithUser()
    {
        $client = $this->login('user');

        $client->request('GET', $this->route);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSuccessForm()
    {
        $client = $this->login('admin');

        $crawler = $client->request('GET', $this->route);
        $form = $crawler->selectButton('Modifier')->form([
            'user[username]' => 'Username',
            'user[password][first]' => 'password',
            'user[password][second]' => 'password',
            'user[email]' => 'email@email.com',
            'user[roles]' => ['ROLE_USER'],
        ]);
        $client->submit($form);

        $user = self::$container->get(UserRepository::class)->findOneByUsername('Username');
        $this->assertEquals('Username', $user->getUsername());

        $this->assertResponseRedirects('/users');
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('.alert.alert-success');
    }

    public function testFailedForm()
    {
        $client = $this->login('admin');

        $crawler = $client->request('GET', $this->route);
        $form = $crawler->selectButton('Modifier')->form([
            'user[username]' => 'aze',
            'user[password][first]' => 'password',
            'user[password][second]' => 'fzefz',
            'user[email]' => 'email@email.com',
            'user[roles]' => ['ROLE_USER'],
        ]);
        $client->submit($form);
        $this->assertSelectorExists('.form-error-message');
    }
}
