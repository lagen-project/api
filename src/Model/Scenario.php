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

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Feature
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * @param Feature $feature
     */
    public function setFeature($feature)
    {
        $this->feature = $feature;
    }

    /**
     * @return Step[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param Step[] $steps
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;

        foreach ($steps as $step) {
            $step->setScenario($this);
        }
    }

    /**
     * @param Step $step
     */
    public function addStep(Step $step)
    {
        $this->steps[] = $step;
        $step->setScenario($this);
    }

    /**
     * @param Step $step
     */
    public function removeStep(Step $step)
    {
        foreach ($this->steps as $id => $s) {
            if ($s->getContent() === $step->getContent()) {
                unset($this->steps[$id]);
            }
        }
    }

    /**
     * @return array
     */
    public function getExamples()
    {
        return $this->examples;
    }

    /**
     * @param array $examples
     */
    public function setExamples($examples)
    {
        $this->examples = $examples;
    }

    /**
     * @return bool
     */
    public function isOutline()
    {
        return count($this->examples) > 0;
    }
}
