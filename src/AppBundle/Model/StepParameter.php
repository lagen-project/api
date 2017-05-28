<?php

namespace AppBundle\Model;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\JsonDeserializationVisitor;

class StepParameter
{
    const TYPE_STRING = 'string';
    const TYPE_TABLE = 'table';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|array
     */
    private $value;

    /**
     * @var Step
     *
     * @Serializer\Type("AppBundle\Model\Step")
     */
    private $step;

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
     * @return array|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param array|string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return Step
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param Step $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * @Serializer\HandlerCallback(direction="deserialization", format="json")
     *
     * @param JsonDeserializationVisitor $visitor
     * @param array $data
     */
    public function deserialize(JsonDeserializationVisitor $visitor, array $data)
    {
        $this->type = $data['type'];
        $this->value = $data['value'];
    }
}
