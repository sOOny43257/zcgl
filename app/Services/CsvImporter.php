<?php

namespace App\Services;

class CsvImporter
{
    private $handle;
    private array $headers = [];
    private array $errors = [];
    private int $imported = 0;
    private int $skipped = 0;

    public function import($filePath, array $requiredColumns, callable $rowHandler): array
    {
        $this->handle = fopen($filePath, 'r');
        $this->errors = [];
        $this->imported = 0;
        $this->skipped = 0;

        // 跳过 BOM
        $bom = fread($this->handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($this->handle);
        }

        $this->headers = fgetcsv($this->handle);
        if (!$this->headers) {
            fclose($this->handle);
            return $this->result('CSV文件为空或格式不正确');
        }

        // 建立列名→索引映射
        $map = [];
        foreach ($this->headers as $i => $h) {
            $map[trim(strtolower($h))] = $i;
        }

        // 检查必填列
        $missing = array_diff($requiredColumns, array_keys($map));
        if (!empty($missing)) {
            fclose($this->handle);
            return $this->result('CSV缺少必填列: ' . implode(', ', $missing));
        }

        $rowNum = 1;
        while (($row = fgetcsv($this->handle)) !== false) {
            $rowNum++;
            try {
                $data = [];
                foreach ($map as $col => $idx) {
                    $data[$col] = trim($row[$idx] ?? '');
                }

                $result = $rowHandler($data, $rowNum);
                if ($result === true) {
                    $this->imported++;
                } elseif ($result === false) {
                    $this->skipped++;
                } else {
                    // 返回了错误消息
                    $this->errors[] = $result;
                    $this->skipped++;
                }
            } catch (\Exception $e) {
                $this->errors[] = "第{$rowNum}行: " . $e->getMessage();
                $this->skipped++;
            }
        }

        fclose($this->handle);
        return $this->result();
    }

    private function result(?string $error = null): array
    {
        $msg = "导入完成：成功 {$this->imported} 条，跳过 {$this->skipped} 条";
        if ($error) {
            $msg = $error;
        } elseif (!empty($this->errors)) {
            $msg .= '。' . implode('；', array_slice($this->errors, 0, 10));
        }

        return [
            'success' => empty($error),
            'message' => $msg,
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
