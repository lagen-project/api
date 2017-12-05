<?php

namespace App\Transformer;

class ExamplesToStringArrayTransformer extends TableParameterToStringArrayTransformer
{
    public function transform(array $table): array
    {
        return parent::transform(array_merge(
            [array_keys($table[0])],
            array_map(function($row) { return array_values($row); }, $table)
        ));
    }
}
