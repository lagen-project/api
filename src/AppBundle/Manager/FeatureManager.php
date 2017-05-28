<?php

namespace AppBundle\Manager;

use AppBundle\Model\Feature;
use AppBundle\Parser\FeatureParser;
use AppBundle\Transformer\FeatureToStringTransformer;
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
     * @var FeatureToStringTransformer
     */
    private $featureToStringTransformer;

    /**
     * @param Filesystem $filesystem
     * @param string $projectsDir
     * @param Slugify $slugify
     * @param FeatureParser $featureParser
     * @param FeatureToStringTransformer $featureToStringTransformer
     */
    public function __construct(
        Filesystem $filesystem,
        $projectsDir,
        Slugify $slugify,
        FeatureParser $featureParser,
        FeatureToStringTransformer $featureToStringTransformer
    ) {
        $this->filesystem = $filesystem;
        $this->projectsDir = $projectsDir;
        $this->slugify = $slugify;
        $this->featureParser = $featureParser;
        $this->featureToStringTransformer = $featureToStringTransformer;
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

    /**
     * @param string $projectSlug
     * @param string $featureSlug
     * @param Feature $feature
     */
    public function editFeature($projectSlug, $featureSlug, Feature $feature)
    {
        file_put_contents(
            sprintf(
                '%s/%s/%s',
                $this->projectsDir,
                $projectSlug,
                $featureSlug
            ),
            $this->featureToStringTransformer->transform($feature)
        );
    }
}
