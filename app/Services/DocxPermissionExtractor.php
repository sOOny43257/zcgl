<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;

class DocxPermissionExtractor
{
    public function extract(string $filePath): array
    {
        $phpWord = IOFactory::load($filePath);
        $sections = $phpWord->getSections();

        $headerText = '';
        $allRows = [];

        foreach ($sections as $section) {
            foreach ($section->getElements() as $element) {
                // Collect header text (before table) for department/date extraction
                if (method_exists($element, 'getText') && ! method_exists($element, 'getRows')) {
                    $text = trim($element->getText());
                    if ($text !== '') {
                        $headerText .= $text . "\n";
                    }
                }

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

        return $this->parseDocument($headerText, $allRows);
    }

    private function parseDocument(string $headerText, array $rows): array
    {
        $data = [
            'department' => '',
            'fill_date' => '',
            'items' => [],
        ];

        // Extract department and fill_date from header text
        if (preg_match('/填报部门[：:]\s*(.+?)(?:\s+填写日期|$)/u', $headerText, $m)) {
            $data['department'] = trim($m[1]);
        }
        if (preg_match('/填写日期[：:]\s*(.+)/u', $headerText, $m)) {
            $data['fill_date'] = preg_replace('/\s+/u', ' ', trim($m[1]));
        }

        // Find the header row to identify column positions
        $headerRowIndex = null;
        $colMap = [];

        foreach ($rows as $ri => $row) {
            $cells = $row->getCells();
            $cellTexts = [];
            foreach ($cells as $cell) {
                $cellTexts[] = $this->getCellText($cell);
            }

            // Check if this row contains the table header
            $hasName = false;
            $hasSystem = false;
            foreach ($cellTexts as $ci => $ct) {
                if (mb_strpos($ct, '姓名') !== false || mb_strpos($ct, '姓 名') !== false) {
                    $colMap['names'] = $ci;
                    $hasName = true;
                }
                if (mb_strpos($ct, '涉及业务系统') !== false) {
                    $colMap['business_system'] = $ci;
                    $hasSystem = true;
                }
                if (mb_strpos($ct, '原岗位') !== false) {
                    $colMap['original_position'] = $ci;
                }
                if (mb_strpos($ct, '增加岗位') !== false) {
                    $colMap['added_position'] = $ci;
                }
                if (mb_strpos($ct, '减少岗位') !== false) {
                    $colMap['removed_position'] = $ci;
                }
            }

            if ($hasName && $hasSystem) {
                $headerRowIndex = $ri;
                break;
            }
        }

        if ($headerRowIndex === null || empty($colMap)) {
            return $data;
        }

        // Extract data rows (rows after header, until we hit a footer row)
        for ($ri = $headerRowIndex + 1; $ri < count($rows); $ri++) {
            $row = $rows[$ri];
            $cells = $row->getCells();
            $cellTexts = [];
            foreach ($cells as $cell) {
                $cellTexts[] = $this->getCellText($cell);
            }

            // Stop if this looks like a footer row
            $firstCell = $cellTexts[0] ?? '';
            if (mb_strpos($firstCell, '申请部门') !== false
                || mb_strpos($firstCell, '主管') !== false
                || mb_strpos($firstCell, '经办人') !== false
                || mb_strpos($firstCell, '修改人') !== false
            ) {
                break;
            }

            // Skip empty rows
            $allEmpty = true;
            foreach ($cellTexts as $ct) {
                if ($ct !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            $item = [
                'names' => $this->cleanNames($cellTexts[$colMap['names']] ?? ''),
                'business_system' => trim($cellTexts[$colMap['business_system']] ?? ''),
                'original_position' => trim($cellTexts[$colMap['original_position']] ?? ''),
                'added_position' => trim($cellTexts[$colMap['added_position']] ?? ''),
                'removed_position' => trim($cellTexts[$colMap['removed_position']] ?? ''),
            ];

            $data['items'][] = $item;
        }

        return $data;
    }

    private function cleanNames(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        // Normalize separators: 、/，, to semicolons
        $raw = preg_replace('/[、，,]+/u', '；', $raw);
        // Split by whitespace, newlines, semicolons
        $parts = preg_split('/[\s;；]+/u', $raw);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static fn ($p) => $p !== '');
        $parts = array_values(array_unique($parts));

        return implode('；', $parts);
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
}
