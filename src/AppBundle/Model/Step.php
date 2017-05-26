<?php

namespace AppBundle\Model;

class Step
{
    const TYPE_GIVEN = 0;
    const TYPE_WHEN = 1;
    const TYPE_THEN = 2;
    const TYPE_AND = 3;
    const TYPE_BUT = 4;

    /**
     * @var Scenario
     */
    private $scenario;

    /**
     * @var string
     */
    private $content;

    /**
     * @var StepParameter
     */
    private $parameter;

    /**
     * @var int
     */
    private $type;

    /**
     * @return Scenario
     */
    public function getScenario()
    {
        return $this->scenario;
    }

    /**
     * @param Scenario $scenario
     */
    public function setScenario($scenario)
    {
        $this->scenario = $scenario;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return StepParameter
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * @param StepParameter $parameter
     */
    public function setParameter(StepParameter $parameter)
    {
        $this->parameter = $parameter;
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
}
