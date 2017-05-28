<?php

namespace AppBundle\Transformer;

use AppBundle\Model\Feature;
use AppBundle\Model\Scenario;
use AppBundle\Model\Step;
use AppBundle\Model\StepParameter;

class FeatureToStringTransformer
{
    /**
     * @var TableParameterToStringArrayTransformer
     */
    private $tableParameterTransformer;

    /**
     * @param TableParameterToStringArrayTransformer $tableParameterTransformer
     */
    public function __construct(TableParameterToStringArrayTransformer $tableParameterTransformer)
    {
        $this->tableParameterTransformer = $tableParameterTransformer;
    }

    /**
     * @param Feature $feature
     *
     * @return string
     */
    public function transform(Feature $feature)
    {
        $asArray = ['Feature: ' . $feature->getName()];
        foreach ($feature->getScenarios() as $scenario) {
            $asArray[] = '';
            $asArray[] = sprintf(
                '  %s: %s',
                $scenario->getType() === Scenario::TYPE_BACKGROUND ? 'Background' : 'Scenario',
                $scenario->getName()
            );
            foreach ($scenario->getSteps() as $step) {
                $asArray[] = sprintf('    %s %s', $this->transformType($step->getType()), $step->getSentence());
                if ($step->getParameter()) {
                    if ($step->getParameter()->getType() === StepParameter::TYPE_STRING) {
                        $asArray[] = '    """';
                        foreach (explode("\n", trim($step->getParameter()->getValue())) as $line) {
                            $asArray[] = sprintf('    %s', $line);
                        }
                        $asArray[] = '    """';
                    } elseif ($step->getParameter()->getType() === StepParameter::TYPE_TABLE) {
                        $asArray = array_merge(
                            $asArray,
                            $this->tableParameterTransformer->transform($step->getParameter()->getValue())
                        );
                    }
                }
            }
        }
        $asArray[] = '';

        return implode("\n", $asArray);
    }

    /**
     * @param int $type
     *
     * @return string
     */
    private function transformType($type)
    {
        $types = [
            Step::TYPE_GIVEN => 'Given',
            Step::TYPE_WHEN => 'When',
            Step::TYPE_THEN => 'Then',
            Step::TYPE_AND => 'And',
            Step::TYPE_BUT => 'But'
        ];

        return $types[$type];
    }
}
