<?php

namespace AppBundle\Model;

class Scenario
{
    const TYPE_BACKGROUND = 1;
    const TYPE_SCENARIO = 2;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Feature
     */
    private $feature;

    /**
     * @var Step[]
     */
    private $steps;

    public function __construct()
    {
        $this->steps = [];
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
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
}
