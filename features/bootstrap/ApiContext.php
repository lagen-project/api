<?php

use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\HttpFoundation\Response;

class ApiContext extends ContainerAwareContext
{
    const BEHAT_ROOT_DIR = __DIR__ . '/../../tests';

    /**
     * @var Response
     */
    private $response;

    /**
     * @Given I have no projects installed
     */
    public function iHaveNoProjectsInstalled()
    {
        exec(sprintf('rm -rf %s/projects/*', self::BEHAT_ROOT_DIR));
    }

    /**
     * @When I send a :method request to :uri with the following body:
     * @When I send a :method request to :uri
     *
     * @param string $method
     * @param string $uri
     * @param PyStringNode|null $string
     */
    public function iSendARequest($method, $uri, PyStringNode $string = null)
    {
        $body = $string ? $string->getRaw() : null;

        $client = $this->getContainer()->get('test.client');
        $server = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT'  => 'application/json',
        );

        $client->restart();
        $client->request($method, $uri, [], [], $server, $body);
        $this->response = $client->getResponse();
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
}
