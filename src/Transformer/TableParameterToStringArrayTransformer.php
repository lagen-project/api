<?php

namespace App\Transformer;

class TableParameterToStringArrayTransformer
{
    public function transform(array $table): array
    {
        $out = [];
        $columnsLengths = $this->getMaxColumnLengths($table);

        foreach ($table as $row) {
            $str = '      |';
            foreach ($row as $cellId => $cell) {
                $str .= sprintf(
                    ' %s |',
                    str_pad($cell, $columnsLengths[$cellId])
                );
            }

            $out[] = $str;
        }

        return $out;
    }

    private function getMaxColumnLengths(array $table): array
    {
        $lengths = [];
        foreach ($table[0] as $columnId => $column) {
            $maxLength = 0;
            foreach ($table as $row) {
                $columnLength = strlen($row[$columnId]);
                $maxLength = $columnLength > $maxLength ? $columnLength : $maxLength;
            }

            $lengths[$columnId] = $maxLength;
        }

        return $lengths;
    }
}
