<?php

use AppBundle\Entity\User;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;

class ApiContext extends ContainerAwareContext
{
    const BEHAT_ROOT_DIR = __DIR__ . '/../../tests';

    /**
     * @var Response
     */
    private $response;

    /**
     * @var string
     */
    private $token;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @BeforeScenario
     */
    public function beforeScenario()
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->em->getConnection()->executeQuery('DELETE FROM user');
        $this->em->getConnection()->executeQuery('VACUUM');
        $this->token = null;
    }

    /**
     * @Given I have no projects installed
     */
    public function iHaveNoProjectsInstalled()
    {
        exec(sprintf('rm -rf %s/projects/*', self::BEHAT_ROOT_DIR));
    }

    /**
     * @When I send a :method request to :uri with the following body:
     * @When I send a :method request to :uri with the following params:
     * @When I send a :method request to :uri
     *
     * @param string $method
     * @param string $uri
     * @param PyStringNode|TableNode|null $bodyOrParams
     */
    public function iSendARequest($method, $uri, $bodyOrParams = null)
    {
        $body = $bodyOrParams instanceof PyStringNode ? $bodyOrParams->getRaw() : null;
        $params = $bodyOrParams instanceof TableNode ? $bodyOrParams->getHash()[0] : [];

        $this->response = $this->sendRequest($method, $uri, $params, $body);
    }

    /**
     * @Then I should have a directory :subDir inside the :rootDir directory
     *
     * @param string $subDir
     * @param string $rootDir
     *
     * @throws \Exception
     */
    public function iShouldHaveADirectoryInsideTheDirectory($subDir, $rootDir)
    {
        $dir = sprintf('%s/%s/%s', self::BEHAT_ROOT_DIR, $rootDir, $subDir);

        if (!is_dir($dir)) {
            throw new \Exception(sprintf('No directory %s found', $dir));
        }
    }

    /**
     * @Then the configuration file of the :project project should have the following values:
     *
     * @param string $project
     * @param PyStringNode $values
     *
     * @throws \Exception
     */
    public function theConfigurationFileShouldHaveValues($project, PyStringNode $values)
    {
        $expected = json_encode(json_decode($values->getRaw()));
        $filename = sprintf('%s/projects/%s/config.json', self::BEHAT_ROOT_DIR, $project);
        $actual = json_encode(json_decode(file_get_contents($filename)));

        if ($expected !== $actual) {
            throw new \Exception(sprintf(
                'Configurations are not the same ! Expected %s, got %s',
                $expected,
                $actual
            ));
        }
    }

    /**
     * @Then I should have the following response:
     *
     * @param PyStringNode|null $response
     *
     * @throws Exception
     */
    public function iShouldHaveTheResponse(PyStringNode $response = null)
    {
        $expected = $response ? json_encode(json_decode($response->getRaw())) : null;
        $actual = $this->response ? json_encode(json_decode($this->response->getContent())) : null;

        if ($actual !== $expected) {
            throw new \Exception(sprintf('Expected response %s, got %s', $expected, $actual));
        }
    }

    /**
     * @Then I should have a :status response containing the :key key
     *
     * @param string $status
     * @param string $key
     *
     * @throws Exception
     */
    public function iShouldHaveAResponseContainingTheKey($status, $key)
    {
        $content = json_decode($this->response->getContent(), true);
        if (!isset($content[$key]) || $this->response->getStatusCode() != $status) {
            throw new \Exception(sprintf(
                'Expected %d response with key %s, got %d response : %s',
                $status,
                $key,
                $this->response->getStatusCode(),
                json_encode($content)
            ));
        }
    }

    /**
     * @Then I should have a :status response
     *
     * @param string $status
     *
     * @throws Exception
     */
    public function iShouldHaveAResponse($status)
    {
        if ($this->response->getStatusCode() != $status) {
            throw new \Exception(sprintf(
                'Expected %d response got %d response',
                $status,
                $this->response->getStatusCode()
            ));
        }
    }

    /**
     * @Given I have this user in database
     */
    public function iHaveThisUserInDatabase(TableNode $table)
    {
        $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder(User::class);
        foreach ($table->getHash() as $hash) {
            $user = new User();

            $user->setUsername($hash['username']);
            $user->setPassword($encoder->encodePassword($hash['password'], ''));
            $user->setRoles(array_map(function($role) {
                return sprintf('ROLE_%s', mb_strtoupper($role));
            }, explode(',', $hash['roles'])));

            $this->em->persist($user);
        }

        $this->em->flush();
    }

    /**
     * @Given I'm logging in with username :username and password :password
     *
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     */
    public function imLoggingInWithUsernameAndPassword($username, $password)
    {
        $loginResponse = $this->sendRequest('POST', 'login_check', [
            '_username' => $username,
            '_password' => $password
        ]);

        if (!$loginResponse->getStatusCode() === Response::HTTP_OK) {
            throw new \Exception('Failed to login');
        }

        $this->token = json_decode($loginResponse->getContent())->token;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $params
     * @param string $body
     *
     * @return Response
     */
    private function sendRequest($method, $uri, array $params = [], $body = null)
    {
        $client = $this->getContainer()->get('test.client');
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT'  => 'application/json',
        ];

        if ($this->token) {
            $server['HTTP_Authorization'] = sprintf('Bearer %s', $this->token);
        }

        $client->restart();
        $client->request($method, $uri, $params, [], $server, $body);

        return  $client->getResponse();
    }
}
