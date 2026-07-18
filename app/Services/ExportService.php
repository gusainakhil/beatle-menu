<?php

namespace App\Services;

class ExportService {
    /**
     * Stream rows to raw CSV format for browser download.
     */
    public static function toCsv(string $filename, array $headers, array $rows): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for proper Excel cell characters parsing
        fputs($output, "\xEF\xBB\xBF");
        
        fputcsv($output, $headers);
        
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
