<?php

namespace AppBundle\Model;

class Step
{
    const TYPE_GIVEN = 'Given';
    const TYPE_WHEN = 'When';
    const TYPE_THEN = 'Then';
    const TYPE_AND = 'And';
    const TYPE_BUT = 'But';

    /**
     * @var Scenario
     */
    private $scenario;

    /**
     * @var string
     */
    private $sentence;

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
    public function getSentence()
    {
        return $this->sentence;
    }

    /**
     * @param string $sentence
     */
    public function setSentence($sentence)
    {
        $this->sentence = $sentence;
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
