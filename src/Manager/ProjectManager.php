<?php

namespace App\Manager;

use App\Exception\ProjectNotFoundException;
use App\Exception\ProjectNotInstallableException;
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
use Symfony\Component\Process\Exception\ProcessFailedException;

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
     * @var string
     */
    private $nodesDir;

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

    public function __construct(
        Filesystem $filesystem,
        string $projectsDir,
        string $deploysDir,
        string $nodesDir,
        Slugify $slugify,
        FeatureParser $featureParser,
        Git $git,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
        $this->deploysDir = $deploysDir;
        $this->nodesDir = $nodesDir;
        $this->slugify = $slugify;
        $this->featureParser = $featureParser;
        $this->git = $git;
        $this->logger = $logger;
    }

    public function getProjects(): array
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
     * @throws ProjectNotFoundException
     */
    public function getProject(string $projectSlug): array
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
     * @throws ProjectNotInstallableException
     */
    public function installProject(string $projectSlug): void
    {
        $projectConfig = $this->retrieveProjectConfig($projectSlug);

        if (!isset($projectConfig['gitRepository'])) {
            throw new ProjectNotInstallableException();
        }

        $this->createNodesDirsIfNotExists();

        $destination = sprintf(
            '%s/pending/%s',
            $this->nodesDir,
            \DateTime::createFromFormat('U.u', microtime(true))->format('YmdHisu')
        );

        $this->filesystem->dumpFile(
            $destination,
            json_encode([
                'project' => $projectSlug,
                'repository' => $projectConfig['gitRepository'],
                'branch' => isset($projectConfig['gitBranch']) ? $projectConfig['gitBranch'] : null,
                'status' => 'pending'
            ], JSON_PRETTY_PRINT)
        );
        $this->filesystem->dumpFile(
            sprintf('%s/%s/job', $this->projectsDir, $projectSlug),
            json_encode([
                'status' => 'pending',
                'result' => ''
            ])
        );
    }

    public function getProjectInstallStatus(string $projectSlug): array
    {
        $jobFilename = sprintf('%s/job', $this->getProjectDirectory($projectSlug));

        if (!$this->filesystem->exists($jobFilename)) {
            return [
                'status' => 'none'
            ];
        }

        return array_intersect_key(
            json_decode(file_get_contents($jobFilename), true),
            array_flip(['status', 'result'])
        );
    }

    public function createProject(string $projectName): void
    {
        $this->createRootDirIfNotExists();
        $slug = $this->slugify->slugify($projectName);
        $this->filesystem->mkdir(sprintf('%s/%s', $this->projectsDir, $slug));
        $this->saveProjectConfig($slug, [
            'name' => $projectName
        ]);
    }

    public function editProject(string $projectSlug, array $changes): void
    {
        $config = array_merge(
            $this->retrieveProjectConfig($projectSlug),
            $changes
        );

        $this->saveProjectConfig($projectSlug, $config);
    }

    public function deleteProject(string $projectSlug): void
    {
        $dirs = [
            sprintf('%s/%s', $this->projectsDir, $projectSlug),
            sprintf('%s/%s', $this->deploysDir, $projectSlug),
            sprintf('%s/%s', $this->nodesDir, $projectSlug)
        ];

        foreach ($dirs as $dir) {
            if ($this->filesystem->exists($dir)) {
                $this->filesystem->remove($dir);
            }
        }
    }

    private function createRootDirIfNotExists(): void
    {
        if (!$this->filesystem->exists($this->projectsDir)) {
            $this->filesystem->mkdir($this->projectsDir);
        }
    }

    private function retrieveProjectConfig(string $projectSlug): array
    {
        return json_decode(
            file_get_contents(sprintf('%s/%s/config.json', $this->projectsDir, $projectSlug)),
            true
        );
    }

    private function saveProjectConfig(string $projectSlug, array $config): void
    {
        $this->filesystem->dumpFile(
            sprintf('%s/%s/config.json', $this->projectsDir, $projectSlug),
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }

    public function retrieveProjectGitInfo(string $projectSlug): array
    {
        try {
            return $this->git->getLastCommitInfo($projectSlug);
        } catch (ProcessFailedException $e) {
            return [];
        } catch (ProjectNotInstalledException $e) {
            return [];
        }
    }

    public function retrieveSteps(string $projectSlug): array
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
     * @throws ProjectNotFoundException
     */
    private function getProjectDirectory(string $projectSlug): string
    {
        $dirName = sprintf('%s/%s', $this->projectsDir, $projectSlug);
        if (!$this->filesystem->exists($dirName)) {
            throw new ProjectNotFoundException();
        }

        return $dirName;
    }

    private function getFeaturesFinder(string $projectDirName): Finder
    {
        $finder = new Finder();
        $finder->files()->name('*.feature')->in($projectDirName);

        return $finder;
    }

    private function checkProjectAndRetrieveFeaturesFinder(string $projectSlug): Finder
    {
        return $this->getFeaturesFinder($this->getProjectDirectory($projectSlug));
    }

    private function createNodesDirsIfNotExists(): void
    {
        if (!$this->filesystem->exists($this->nodesDir)) {
            $this->filesystem->mkdir($this->nodesDir);
            $this->filesystem->mkdir(sprintf('%s/pending', $this->nodesDir));
            $this->filesystem->mkdir(sprintf('%s/ongoing', $this->nodesDir));
            $this->filesystem->mkdir(sprintf('%s/done', $this->nodesDir));
            $this->filesystem->mkdir(sprintf('%s/failed', $this->nodesDir));
        }
    }
}
