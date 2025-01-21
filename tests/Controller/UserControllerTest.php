<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/user';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(User::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%s/new', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'user[email]' => 'test@test.com',
            'user[roles]' => ['ROLE_USER'],
            'user[password]' => 'Testing',
            'user[verified]' => true,
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $fixture = new User();
        $fixture->setEmail('test@test.com');
        $fixture->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $fixture->setPassword('testing');
        $fixture->setVerified(true);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s/%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $fixture = new User();
        $fixture->setEmail('test@test.com');
        $fixture->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $fixture->setPassword('testing');
        $fixture->setVerified(true);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s/%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'user[email]' => 'test+1@test.com',
            'user[roles]' => ['ROLE_USER', 'ROLE_ADMIN'],
            'user[password]' => 'Something New',
            'user[verified]' => true,
        ]);

        self::assertResponseRedirects('/user');

        $fixture = $this->repository->findAll();

        self::assertSame('test+1@test.com', $fixture[0]->getEmail());
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $fixture[0]->getRoles());
        self::assertSame('Something New', $fixture[0]->getPassword());
        self::assertSame(true, $fixture[0]->isVerified());
    }

    public function testRemove(): void
    {
        $fixture = new User();
        $fixture->setEmail('test+1@test.com');
        $fixture->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $fixture->setPassword('Value');
        $fixture->setVerified(true);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s/%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/user');
        self::assertSame(0, $this->repository->count([]));
    }
}
