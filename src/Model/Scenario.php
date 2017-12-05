<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class Scenario
{
    const TYPE_BACKGROUND = 'background';
    const TYPE_SCENARIO = 'regular';

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $type;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @var Feature
     *
     * @Serializer\Type("App\Model\Feature")
     */
    private $feature;

    /**
     * @var Step[]
     *
     * @Serializer\Type("array<App\Model\Step>")
     */
    private $steps;

    /**
     * @var array
     *
     * @Serializer\Type("array")
     */
    private $examples;

    public function __construct()
    {
        $this->steps = [];
        $this->examples = [];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function setFeature(Feature $feature = null)
    {
        $this->feature = $feature;
    }

    /**
     * @return Step[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @param Step[] $steps
     */
    public function setSteps(array $steps = [])
    {
        $this->steps = $steps;

        foreach ($steps as $step) {
            $step->setScenario($this);
        }
    }

    public function addStep(Step $step)
    {
        $this->steps[] = $step;
        $step->setScenario($this);
    }

    public function removeStep(Step $step)
    {
        foreach ($this->steps as $id => $s) {
            if ($s->getContent() === $step->getContent()) {
                unset($this->steps[$id]);
            }
        }
    }

    public function getExamples(): array
    {
        return $this->examples;
    }

    public function setExamples(array $examples = [])
    {
        $this->examples = $examples;
    }

    public function isOutline(): bool
    {
        return count($this->examples) > 0;
    }
}
