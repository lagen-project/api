<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class Feature
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * @var Scenario[]
     *
     * @Serializer\Type("array<App\Model\Scenario>")
     */
    private $scenarios;

    /**
     * @var bool
     *
     * @Serializer\Type("boolean")
     */
    private $runnable;

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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
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

    /**
     * @return bool
     */
    public function isRunnable()
    {
        return $this->runnable;
    }

    /**
     * @param bool $runnable
     */
    public function setRunnable($runnable)
    {
        $this->runnable = $runnable;
    }
}
