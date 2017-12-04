<?php

namespace App\Manager;

use App\Exception\ProjectNotFoundException;
use App\Exception\ProjectNotInstallableException;
use App\Exception\ProjectConfigurationNotFoundException;
use App\Exception\ProjectNotInstalledException;
use App\Model\Scenario;
use App\Model\Step;
use App\Parser\FeatureParser;
use App\Utils\ArrayUtils;
use App\Utils\Git;
use Cocur\Slugify\Slugify;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class ProjectManager
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $projectsDir;

    /**
     * @var string
     */
    private $deploysDir;

    /**
     * @var Slugify
     */
    private $slugify;

    /**
     * @var FeatureParser
     */
    private $featureParser;

    /**
     * @var Git
     */
    private $git;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Filesystem $filesystem
     * @param string $projectsDir
     * @param string $deploysDir
     * @param Slugify $slugify
     * @param FeatureParser $featureParser
     * @param Git $git
     * @param LoggerInterface $logger
     */
    public function __construct(
        Filesystem $filesystem,
        $projectsDir,
        $deploysDir,
        Slugify $slugify,
        FeatureParser $featureParser,
        Git $git,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
        $this->deploysDir = $deploysDir;
        $this->slugify = $slugify;
        $this->featureParser = $featureParser;
        $this->git = $git;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getProjects()
    {
        $this->createRootDirIfNotExists();
        $projects = [];
        $finder = new Finder();
        $finder->directories()->in($this->projectsDir);
        foreach ($finder as $directory) {
            $projects[] = [
                'slug' => $directory->getBasename(),
                'name' => $this->retrieveProjectConfig($directory->getBasename())['name']
            ];
        }

        return $projects;
    }

    /**
     * @param string $projectSlug
     *
     * @return array
     *
     * @throws ProjectNotFoundException
     */
    public function getProject($projectSlug)
    {
        $finder = $this->checkProjectAndRetrieveFeaturesFinder($projectSlug);

        $features = [];
        foreach ($finder as $feature) {
            $features[] = [
                'slug' => $feature->getBasename(),
                'name' => $this->featureParser->parse($feature->getPathname())->getName()
            ];
        }

        $projectConfig = $this->retrieveProjectConfig($projectSlug);

        return [
            'name' => isset($projectConfig['name']) ? $projectConfig['name'] : '',
            'gitRepository' => isset($projectConfig['gitRepository']) ? $projectConfig['gitRepository'] : '',
            'gitBranch' => isset($projectConfig['gitBranch']) ? $projectConfig['gitBranch'] : '',
            'slug' => $projectSlug,
            'features' => $features,
            'gitInfo' => $this->retrieveProjectGitInfo($projectSlug)
        ];
    }

    /**
     * @param string $projectSlug
     *
     * @return StreamedResponse
     *
     * @throws ProcessFailedException
     * @throws ProjectNotInstallableException
     */
    public function installProject($projectSlug)
    {
        $projectConfig = $this->retrieveProjectConfig($projectSlug);

        if (!isset($projectConfig['gitRepository'])) {
            return null;
        }

        $this->git->cloneRepository($projectConfig['gitRepository'], $projectSlug);

        if (isset($projectConfig['gitBranch'])) {
            $this->git->changeBranch($projectConfig['gitBranch'], $projectSlug);
        }

        $lagenConfig = $this->retrieveProjectLagenConfig($projectSlug);
        if (!isset($lagenConfig['install'])) {
            throw new ProjectNotInstallableException();
        }

        $response = new StreamedResponse();
        $response->headers->add(['Content-type' => 'application/json']);
        $installCmd = sprintf(
            'cd %s/%s && %s',
            $this->deploysDir,
            $projectSlug,
            is_array($lagenConfig['install']) ? implode(' ; ', $lagenConfig['install']) : $lagenConfig['install']
        );
        $this->logger->info(sprintf('Now running install command : %s', $installCmd));
        $process = new Process($installCmd);
        $process->setTimeout(0);
        $process->start();
        $response->setCallback(function () use ($process) {
            $process->wait(function ($type, $buffer) {
                echo $buffer;
                flush();
                ob_flush();
            });
        });

        return $response;
    }

    /**
     * @param string $projectName
     */
    public function createProject($projectName)
    {
        $this->createRootDirIfNotExists();
        $slug = $this->slugify->slugify($projectName);
        $this->filesystem->mkdir(sprintf('%s/%s', $this->projectsDir, $slug));
        $this->saveProjectConfig($slug, [
            'name' => $projectName
        ]);
    }

    /**
     * @param string $projectSlug
     * @param array $changes
     */
    public function editProject($projectSlug, array $changes)
    {
        $config = array_merge(
            $this->retrieveProjectConfig($projectSlug),
            $changes
        );

        $this->saveProjectConfig($projectSlug, $config);
    }

    private function createRootDirIfNotExists()
    {
        if (!$this->filesystem->exists($this->projectsDir)) {
            $this->filesystem->mkdir($this->projectsDir);
        }
    }

    /**
     * @param string $projectSlug
     *
     * @return array
     */
    private function retrieveProjectConfig($projectSlug)
    {
        return json_decode(
            file_get_contents(sprintf('%s/%s/config.json', $this->projectsDir, $projectSlug)),
            true
        );
    }

    /**
     * @param string $projectSlug
     *
     * @return array
     *
     * @throws ProjectConfigurationNotFoundException
     */
    public function retrieveProjectLagenConfig($projectSlug)
    {
        $file = sprintf('%s/%s/.lagen.yml', $this->deploysDir, $projectSlug);

        if (!$this->filesystem->exists($file)) {
            throw new ProjectConfigurationNotFoundException();
        }

        return Yaml::parse(file_get_contents($file));
    }

    /**
     * @param string $projectSlug
     * @param array $config
     */
    private function saveProjectConfig($projectSlug, array $config)
    {
        file_put_contents(
            sprintf('%s/%s/config.json', $this->projectsDir, $projectSlug),
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }

    /**
     * @param string $projectSlug
     *
     * @return array|null
     */
    public function retrieveProjectGitInfo($projectSlug)
    {
        try {
            return $this->git->getLastCommitInfo($projectSlug);
        } catch (ProcessFailedException $e) {
            return null;
        } catch (ProjectNotInstalledException $e) {
            return null;
        }
    }

    /**
     * @param string $projectSlug
     *
     * @return array
     */
    public function retrieveSteps($projectSlug)
    {
        $finder = $this->checkProjectAndRetrieveFeaturesFinder($projectSlug);

        $sentences = array_unique(ArrayUtils::flatten(array_map(function(\SplFileInfo $feature) {
            return array_map(function(Scenario $scenario) {
                return array_map(
                    function(Step $step) {
                        return $step->getSentence();
                    }, $scenario->getSteps());
            }, $this->featureParser->parse($feature->getPathname())->getScenarios());
        }, iterator_to_array($finder))));

        sort($sentences);

        return $sentences;
    }

    /**
     * @param string $projectSlug
     *
     * @return string
     *
     * @throws ProjectNotFoundException
     */
    private function getProjectDirectory($projectSlug)
    {
        $dirName = sprintf('%s/%s', $this->projectsDir, $projectSlug);
        if (!$this->filesystem->exists($dirName)) {
            throw new ProjectNotFoundException();
        }

        return $dirName;
    }

    /**
     * @param string $projectDirName
     *
     * @return Finder
     */
    private function getFeaturesFinder($projectDirName)
    {
        $finder = new Finder();
        $finder->files()->name('*.feature')->in($projectDirName);

        return $finder;
    }

    /**
     * @param string $projectSlug
     *
     * @return Finder
     */
    private function checkProjectAndRetrieveFeaturesFinder($projectSlug)
    {
        return $this->getFeaturesFinder($this->getProjectDirectory($projectSlug));
    }
}
