<?php

namespace AppBundle\Manager;

use AppBundle\Exception\ProjectNotFoundException;
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
     * @param Filesystem $filesystem
     * @param $projectsDir
     * @param Slugify $slugify
     */
    public function __construct(Filesystem $filesystem, $projectsDir, Slugify $slugify)
    {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
        $this->slugify = $slugify;
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
                'slug' => $feature->getBasename()
            ];
        }

        return [
            'name' => $this->retrieveProjectConfig($projectSlug)['name'],
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
        $projectDir = sprintf('%s/%s', $this->projectsDir, $slug);
        $configFilename = sprintf('%s/config.json', $projectDir);

        $this->filesystem->mkdir($projectDir);
        file_put_contents($configFilename, <<<JSON
{
    "name": "$projectName"
}
JSON
);
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
        $iterator = (new Finder())
            ->files()
            ->name('config.json')
            ->in(sprintf('%s/%s', $this->projectsDir, $projectSlug))
            ->getIterator();
        $iterator->rewind();

        return json_decode($iterator->current()->getContents(), true);
    }
}
