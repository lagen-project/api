<?php

namespace AppBundle\Manager;

use Cocur\Slugify\Slugify;
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
     * @var Slugify
     */
    private $slugify;

    /**
     * @param Filesystem $filesystem
     * @param string $projectsDir
     * @param Slugify $slugify
     */
    public function __construct(Filesystem $filesystem, $projectsDir, Slugify $slugify)
    {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
        $this->slugify = $slugify;
    }

    /**
     * @param string $projectSlug
     * @param string $featureName
     */
    public function createFeature($projectSlug, $featureName)
    {
        file_put_contents(
            sprintf(
                '%s/%s/%s.feature',
                $this->projectsDir,
                $projectSlug,
                $this->slugify->slugify($featureName)
            ),
            sprintf("Feature: $featureName\n")
        );
    }
}
