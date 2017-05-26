<?php

namespace AppBundle\Manager;

use Symfony\Component\Filesystem\Filesystem;

class FeatureManager
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
}
