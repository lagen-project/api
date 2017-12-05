<?php

namespace App\Model;

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
     * @Serializer\Type("App\Model\Step")
     */
    private $step;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
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

    public function getStep(): Step
    {
        return $this->step;
    }

    public function setStep(Step $step = null): void
    {
        $this->step = $step;
    }

    /**
     * @Serializer\HandlerCallback(direction="deserialization", format="json")
     */
    public function deserialize(JsonDeserializationVisitor $visitor, array $data): void
    {
        $this->type = $data['type'];
        $this->value = $data['value'];
    }
}
