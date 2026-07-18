<?php

namespace App\Services;

use App\Core\DateRange;
use App\Core\Cache;

class GstReportService extends ReportService {
    public function getGstSummary(DateRange $range): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $cacheKey = "gst_summary_" . $this->getBusinessId() . "_" . md5($start . $end);
        $cached = Cache::get($cacheKey, 300);
        if ($cached !== null) {
            return $cached;
        }

        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(subtotal), 0.00) as total_taxable,
                COALESCE(SUM(tax_amount), 0.00) as total_gst,
                COALESCE(SUM(total_amount), 0.00) as total_gross
            FROM orders 
            WHERE business_id = ? AND status = 'completed' AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        $summary = $stmt->fetch() ?: [
            'total_taxable' => 0.00,
            'total_gst' => 0.00,
            'total_gross' => 0.00
        ];

        $stmt = $this->db->prepare("
            SELECT 
                ROUND((tax_amount / NULLIF(subtotal, 0)) * 100) as tax_rate,
                SUM(subtotal) as taxable_amount,
                SUM(tax_amount) as gst_collected,
                COUNT(*) as orders_count
            FROM orders
            WHERE business_id = ? AND status = 'completed' AND created_at BETWEEN ? AND ? AND tax_amount > 0
            GROUP BY tax_rate
            ORDER BY tax_rate ASC
        ");
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        $ratesBreakdown = $stmt->fetchAll();

        $formattedBreakdown = [];
        foreach ($ratesBreakdown as $row) {
            $rate = (int)$row['tax_rate'];
            if ($rate > 3 && $rate < 7) {
                $rateName = "5% GST (Standard Food)";
            } elseif ($rate > 15 && $rate < 20) {
                $rateName = "18% GST (Beverages & Dine-in Service)";
            } else {
                $rateName = $rate . "% GST";
            }

            $formattedBreakdown[] = [
                'rate_name' => $rateName,
                'rate_percent' => $rate,
                'taxable' => (float)$row['taxable_amount'],
                'gst' => (float)$row['gst_collected'],
                'orders' => (int)$row['orders_count']
            ];
        }

        $result = [
            'summary' => $summary,
            'breakdown' => $formattedBreakdown
        ];

        Cache::set($cacheKey, $result);
        return $result;
    }

    public function getMonthlyGstReport(DateRange $range): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(subtotal) as taxable,
                SUM(tax_amount) as gst,
                SUM(total_amount) as gross,
                COUNT(*) as orders_count
            FROM orders 
            WHERE business_id = ? AND status = 'completed' AND created_at BETWEEN ? AND ?
            GROUP BY month 
            ORDER BY month DESC
        ");
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        return $stmt->fetchAll();
    }
}
