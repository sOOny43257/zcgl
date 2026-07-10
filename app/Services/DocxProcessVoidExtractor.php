<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;

class DocxProcessVoidExtractor
{
    public function extract(string $filePath): array
    {
        $phpWord = IOFactory::load($filePath);
        $sections = $phpWord->getSections();

        foreach ($sections as $section) {
            $elements = $section->getElements();

            foreach ($elements as $element) {
                if (method_exists($element, 'getRows')) {
                    $rows = $element->getRows();

                    if (! empty($rows)) {
                        return $this->parseTable($rows);
                    }
                }
            }
        }

        return [];
    }

    private function parseTable(array $rows): array
    {
        $data = [
            'department' => '',
            'flow_start_time' => '',
            'company_name' => '',
            'tax_no' => '',
            'process_name' => '',
            'termination_reason' => '',
            'submitter_sign' => '',
        ];

        $keywords = [
            'department' => ['科所名称'],
            'flow_start_time' => ['起流时间'],
            'company_name' => ['企业名称'],
            'tax_no' => ['税号'],
            'process_name' => ['流程名称'],
            'termination_reason' => ['终止原因'],
            'submitter_sign' => ['提请人签字'],
        ];

        foreach ($rows as $row) {
            $cells = $row->getCells();
            $cellTexts = [];

            foreach ($cells as $cell) {
                $cellTexts[] = $this->getCellText($cell);
            }

            $cellTexts = $this->dedupeAdjacent($cellTexts);
            $pairs = $this->splitToPairs($cellTexts);

            foreach ($pairs as $pair) {
                $label = $pair['label'];
                $value = $pair['value'];

                foreach ($keywords as $field => $keys) {
                    if ($this->labelMatches($label, $keys) && $data[$field] === '') {
                        $data[$field] = $this->cleanValue($value);
                    }
                }
            }
        }

        return $data;
    }

    private function getCellText($cell): string
    {
        $parts = [];
        foreach ($cell->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                $parts[] = $element->getText();
            } elseif (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $sub) {
                    if (method_exists($sub, 'getText')) {
                        $parts[] = $sub->getText();
                    }
                }
            }
        }

        $text = trim(implode(' ', $parts));
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    private function dedupeAdjacent(array $values): array
    {
        $result = [];
        $previous = null;

        foreach ($values as $value) {
            if ($value === '' || $value === $previous) {
                continue;
            }

            $result[] = $value;
            $previous = $value;
        }

        return $result;
    }

    private function splitToPairs(array $cellTexts): array
    {
        $pairs = [];
        $count = count($cellTexts);

        for ($i = 0; $i < $count; $i++) {
            $label = $cellTexts[$i] ?? '';
            $value = $cellTexts[$i + 1] ?? '';

            if ($label !== '') {
                $pairs[] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }
        }

        return $pairs;
    }

    private function labelMatches(string $label, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && mb_strpos($label, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function cleanValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $parts = preg_split('/[\r\n|]+/', $value);
        $parts = array_map('trim', $parts);
        $parts = array_values(array_unique($parts));
        $parts = array_filter($parts, static fn ($part) => $part !== '');

        return implode('；', $parts);
    }
}
