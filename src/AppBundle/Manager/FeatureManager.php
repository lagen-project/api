<?php

namespace AppBundle\Manager;

use AppBundle\Model\Feature;
use AppBundle\Parser\FeatureParser;
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
     * @var FeatureParser
     */
    private $featureParser;

    /**
     * @param Filesystem $filesystem
     * @param string $projectsDir
     * @param Slugify $slugify
     * @param FeatureParser $featureParser
     */
    public function __construct(
        Filesystem $filesystem,
        $projectsDir,
        Slugify $slugify,
        FeatureParser $featureParser
    ) {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
        $this->slugify = $slugify;
        $this->featureParser = $featureParser;
    }

    /**
     * @param string $projectSlug
     * @param string $featureSlug
     *
     * @return Feature
     */
    public function getFeature($projectSlug, $featureSlug)
    {
        return $this->featureParser->parse(
            sprintf('%s/%s/%s', $this->projectsDir, $projectSlug, $featureSlug)
        );
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
