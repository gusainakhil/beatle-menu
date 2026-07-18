<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DateRange;
use App\Services\GstReportService;
use App\Services\ExportService;

class GstReportController extends Controller {
    private GstReportService $service;

    public function __construct() {
        $this->service = new GstReportService();
    }

    public function index(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $data = $this->service->getGstSummary($range);
        $monthly = $this->service->getMonthlyGstReport($range);

        $this->view('reports/gst', [
            'range' => $range,
            'summary' => $data['summary'],
            'breakdown' => $data['breakdown'],
            'monthly' => $monthly
        ]);
    }

    public function data(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $data = $this->service->getGstSummary($range);
        $monthly = $this->service->getMonthlyGstReport($range);

        $this->json([
            'summary' => $data['summary'],
            'breakdown' => $data['breakdown'],
            'monthly' => $monthly
        ]);
    }

    public function export(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $monthly = $this->service->getMonthlyGstReport($range);

        $filename = 'gst_report_' . $range->getType() . '_' . date('Ymd_His') . '.csv';
        $headers = ['Month/Period', 'Taxable Subtotal ($)', 'GST Amount Collected ($)', 'Gross Total Sales ($)', 'Orders Handled'];
        
        $rows = [];
        foreach ($monthly as $row) {
            $rows[] = [
                $row['month'],
                $row['taxable'],
                $row['gst'],
                $row['gross'],
                $row['orders_count']
            ];
        }

        ExportService::toCsv($filename, $headers, $rows);
    }
}
