<?php

namespace AppBundle\Manager;

use AppBundle\Exception\ProjectNotFoundException;
use AppBundle\Parser\FeatureParser;
use Cocur\Slugify\Slugify;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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
     * @var Slugify
     */
    private $slugify;

    /**
     * @var FeatureParser
     */
    private $featureParser;

    /**
     * @param Filesystem $filesystem
     * @param $projectsDir
     * @param Slugify $slugify
     * @param FeatureParser $featureParser
     */
    public function __construct(
        Filesystem $filesystem,
        $projectsDir, Slugify
        $slugify,
        FeatureParser $featureParser
    ) {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
        $this->slugify = $slugify;
        $this->featureParser = $featureParser;
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

        return [
            'name' => $this->retrieveProjectConfig($projectSlug)['name'],
            'slug' => $projectSlug,
            'features' => $features
        ];
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
    private function retrieveProjectConfig($projectSlug) {
        return json_decode(
            file_get_contents(sprintf('%s/%s/config.json', $this->projectsDir, $projectSlug)),
            true
        );
    }

    /**
     * @param string $projectSlug
     * @param array $config
     */
    private function saveProjectConfig($projectSlug, array $config) {
        file_put_contents(
            sprintf('%s/%s/config.json', $this->projectsDir, $projectSlug),
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }
}
