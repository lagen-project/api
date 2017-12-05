<?php

namespace App\Parser;

use App\Model\Feature;
use App\Model\Scenario;

class TestResultParser
{
    public function parse(string $resultLine, Feature $feature): array
    {
        $index = 0;
        $hasBg = $feature->getScenarios() && $feature->getScenarios()[0]->getType() === Scenario::TYPE_BACKGROUND;
        $bgResult = [];
        $regularResults = [];

        foreach ($feature->getScenarios() as $scenario) {
            if ($hasBg) {
                if ($scenario->getType() === Scenario::TYPE_BACKGROUND) {
                    continue;
                }
                for ($i = 0 ; $i < count($feature->getScenarios()[0]->getSteps()) ; $i++) {
                    $bgResult[$i] = $this->convertResult($resultLine[$index]);
                    $index++;
                }
            }

            $scenarioResult = [];
            for ($i = 0 ; $i < $this->countResultAnalysis($scenario) ; $i++) {
                $scenarioResult[] = $this->convertResult($resultLine[$index]);
                $index++;
            }

            $regularResults[] = $scenarioResult;
        }

        return $bgResult ? array_merge([$bgResult], $regularResults) : $regularResults;
    }

    private function convertResult(string $char): array
    {
        return [
            'success' => $char === '.',
            'reason' => $char !== '.' ? $char : null
        ];
    }

    private function countResultAnalysis(Scenario $scenario): int
    {
        return $scenario->isOutline() ?
            array_reduce($scenario->getExamples(), function($carry, $item) { return $carry + count($item); }) :
            count($scenario->getSteps());
    }
}
