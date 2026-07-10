<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;

class DocxProcessVoidExtractor
{
    public function extract(string $filePath): array
    {
        $phpWord = IOFactory::load($filePath);
        $sections = $phpWord->getSections();

        // Collect all rows from ALL tables across ALL sections
        $allRows = [];
        foreach ($sections as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getRows')) {
                    foreach ($element->getRows() as $row) {
                        $allRows[] = $row;
                    }
                }
            }
        }

        if (empty($allRows)) {
            return [];
        }

        return $this->parseTable($allRows);
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
            'department_chief_sign' => '',
        ];

        $keywords = [
            'department' => ['科所名称'],
            'flow_start_time' => ['起流时间'],
            'company_name' => ['企业名称'],
            'tax_no' => ['税号'],
            'process_name' => ['流程名称'],
            'termination_reason' => ['终止原因'],
        ];

        // Signature label keywords — order matters: longer/more specific first
        $signatureMap = [
            'department_chief_sign' => '科所长签字',
            'submitter_sign'        => '提请人签字',
        ];

        foreach ($rows as $row) {
            $cells = $row->getCells();
            $cellTexts = [];

            foreach ($cells as $cell) {
                $cellTexts[] = $this->getCellText($cell);
            }

            // Pass 1: extract signature values from cells containing label keywords
            foreach ($cellTexts as $cellText) {
                foreach ($signatureMap as $field => $labelKeyword) {
                    if ($data[$field] !== '') {
                        continue; // already found
                    }
                    if (mb_strpos($cellText, $labelKeyword) !== false) {
                        $extracted = $this->extractSignatureValue($cellText, $labelKeyword);
                        if ($extracted !== '') {
                            $data[$field] = $extracted;
                        }
                    }
                }
            }

            // Pass 2: standard label/value pair extraction
            $deduped = $this->dedupeAdjacent($cellTexts);
            $pairs = $this->splitToPairs($deduped);

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

    /**
     * Extract the name/value portion from a signature cell.
     * Strips the label keyword and common label fragments, then returns what remains.
     *
     * Examples:
     *   "提请人签字/日期 | | 郭彤"       => "郭彤"
     *   "科所长签字/日期/科所章 | | 孙国枢" => "孙国枢"
     *   "提请人签字/日期\n郭彤"            => "郭彤"
     */
    private function extractSignatureValue(string $cellText, string $labelKeyword): string
    {
        // Match label keyword + all slash-separated label fragments (日期/科所章/签字)
        // plus trailing pipe/space separators
        $suffixes = '(?:[\/]*(?:日期|科所章|签字))*';
        $trailing = '[\/\s|｜]*';
        $pattern = '/' . preg_quote($labelKeyword, '/') . $suffixes . $trailing . '/u';
        $cleaned = preg_replace($pattern, '', $cellText);

        // Split by common separators: |, newline, multiple spaces
        $parts = preg_split('/[\s|｜]+/u', trim($cleaned));
        $parts = array_filter($parts, static fn ($p) => $p !== '');

        // Return the first meaningful remaining part (the name)
        if (! empty($parts)) {
            return trim(array_values($parts)[0]);
        }

        return '';
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
