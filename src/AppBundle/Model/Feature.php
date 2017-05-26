<?php

namespace AppBundle\Model;

class Feature
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Scenario[]
     */
    private $scenarios;

    public function __construct()
    {
        $this->scenarios = [];
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
     * @return Scenario[]
     */
    public function getScenarios()
    {
        return $this->scenarios;
    }

    /**
     * @param Scenario[] $scenarios
     */
    public function setScenarios($scenarios)
    {
        $this->scenarios = $scenarios;

        foreach ($scenarios as $scenario) {
            $scenario->setFeature($this);
        }
    }

    /**
     * @param Scenario $scenario
     */
    public function addScenario(Scenario $scenario)
    {
        $this->scenarios[] = $scenario;
        $scenario->setFeature($this);
    }

    /**
     * @param Scenario $scenario
     */
    public function removeScenario(Scenario $scenario)
    {
        foreach ($this->scenarios as $id => $s) {
            if ($s->getName() === $scenario->getName()) {
                unset($this->scenarios[$id]);
            }
        }
    }
}
