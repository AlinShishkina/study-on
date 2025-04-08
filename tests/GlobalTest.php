<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

abstract class GlobalTest extends WebTestCase
{
    /** @var AbstractBrowser */
    protected static $client;

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    protected static $container;

    /**
     * @param AbstractBrowser|null $newClient
     *
     * @return AbstractBrowser|null
     */
    protected static function getClient(?AbstractBrowser $newClient = null): ?AbstractBrowser
    {
        if (null === static::$client || $newClient) {
            static::$client = static::createClient();
            static::$container = static::$client->getContainer(); // Инициализация контейнера
        }

        // core is loaded (for tests without calling of getClient(true))
        static::$client->getKernel()->boot();

        return static::$client;
    }

    /**
     * Получить EntityManager.
     *
     * @return EntityManagerInterface
     */
    protected static function getEntityManager(): EntityManagerInterface
    {
        return static::$container->get('doctrine')->getManager(); // Получение EntityManager через контейнер
    }

    protected function setUp(): void
    {
        static::getClient(); // Инициализация клиента
        $this->loadFixtures($this->getFixtures()); // Загрузка фикстур
    }

    final protected function tearDown(): void
    {
        parent::tearDown();
        static::$client = null; // Очистка клиента
    }

    /**
     * Получить массив фикстур для загрузки.
     *
     * @return array
     */
    protected function getFixtures(): array
    {
        return [];
    }

    /**
     * Загрузка фикстур в базу данных.
     *
     * @param array $fixtures
     */
    protected function loadFixtures(array $fixtures = []): void
    {
        $loader = new Loader();

        foreach ($fixtures as $fixture) {
            if (!\is_object($fixture)) {
                $fixture = new $fixture();
            }

            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer(static::$container); // Установка контейнера в фикстуры
            }

            $loader->addFixture($fixture);
        }

        $em = static::getEntityManager(); // Получаем EntityManager
        $purger = new ORMPurger($em); // Очистка данных
        $executor = new ORMExecutor($em, $purger); // Выполнение фикстур
        $executor->execute($loader->getFixtures());
    }

    public function assertResponseOk(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isSuccessful', $message, $type);
    }

    public function assertResponseRedirect(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isRedirect', $message, $type);
    }

    public function assertResponseNotFound(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isNotFound', $message, $type);
    }

    public function assertResponseCode(int $expectedCode, ?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, $expectedCode, $message, $type);
    }

    private function failOnResponseStatusCheck(
        Response $response = null,
                 $func = null,
        ?string $message = null,
        string $type = 'text/html'
    ) {
        if (null === $func) {
            $func = 'isOk';
        }

        if (null === $response && self::$client) {
            $response = self::$client->getResponse();
        }

        if (!method_exists($response, $func)) {
            throw new \InvalidArgumentException(
                sprintf("Unknown method %s for %s", $func, get_class($response))
            );
        }

        if (!$response->{$func}()) {
            $this->fail(sprintf('Failed asserting that response is %s. %s', $func, $message ?? ''));
        }
    }
}
