<?php

namespace AppBundle\Manager;

use AppBundle\Exception\ProjectConfigurationNotFoundException;
use AppBundle\Exception\ProjectNotFoundException;
use AppBundle\Exception\ProjectNotInstallableException;
use AppBundle\Exception\ProjectNotInstalledException;
use AppBundle\Parser\FeatureParser;
use AppBundle\Utils\Git;
use Cocur\Slugify\Slugify;
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
     * @param Filesystem $filesystem
     * @param string $projectsDir
     * @param string $deploysDir
     * @param Slugify $slugify
     * @param FeatureParser $featureParser
     * @param Git $git
     */
    public function __construct(
        Filesystem $filesystem,
        $projectsDir,
        $deploysDir,
        Slugify $slugify,
        FeatureParser $featureParser,
        Git $git
    ) {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
        $this->deploysDir = $deploysDir;
        $this->slugify = $slugify;
        $this->featureParser = $featureParser;
        $this->git = $git;
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
        $dirName = sprintf('%s/%s', $this->projectsDir, $projectSlug);
        if (!$this->filesystem->exists($dirName)) {
            throw new ProjectNotFoundException();
        }

        $features = [];
        $finder = new Finder();
        $finder->files()->name('*.feature')->in($dirName);

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

        $lagenConfig = $this->retrieveProjectLagenConfig($projectSlug);
        if (!isset($lagenConfig['install'])) {
            throw new ProjectNotInstallableException();
        }

        $response = new StreamedResponse();
        $response->headers->add(['Content-type' => 'application/json']);
        $process = new Process(sprintf(
            'cd %s/%s && %s',
            $this->deploysDir,
            $projectSlug,
            $lagenConfig['install']
        ));
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
}
