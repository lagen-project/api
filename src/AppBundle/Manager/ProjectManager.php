<?php

namespace AppBundle\Manager;

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
     * @param Filesystem $filesystem
     * @param $projectsDir
     */
    public function __construct(Filesystem $filesystem, $projectsDir)
    {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
    }

    /**
     * @return array
     */
    public function getProjects()
    {
        if (!$this->filesystem->exists($this->projectsDir)) {
            $this->filesystem->mkdir($this->projectsDir);
        }

        $projects = [];
        $finder = new Finder();
        $finder->directories()->in($this->projectsDir);
        foreach ($finder as $directory) {
            $configFinder = new Finder();
            $configFinder->name('config.json')->in(sprintf('%s/%s', $this->projectsDir, $directory->getBasename()));
            $name = '';
            foreach ($configFinder as $configFile) {
                $config = json_decode($configFile->getContents(), true);
                $name = $config['name'];
                break;
            }
            $projects[] = [
                'slug' => $directory->getBasename(),
                'name' => $name
            ];
        }

        return $projects;
    }
}
