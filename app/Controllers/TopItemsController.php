<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DateRange;
use App\Services\TopItemsReportService;
use App\Services\ExportService;

class TopItemsController extends Controller {
    private TopItemsReportService $service;

    public function __construct() {
        $this->service = new TopItemsReportService();
    }

    public function index(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $topItems = $this->service->getTopSelling($range, 10);

        $this->view('reports/top-items', [
            'range' => $range,
            'topItems' => $topItems
        ]);
    }

    public function data(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $topItems = $this->service->getTopSelling($range, 10);

        $this->json([
            'items' => $topItems
        ]);
    }

    public function export(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $topItems = $this->service->getTopSelling($range, 50);

        $filename = 'top_items_report_' . $range->getType() . '_' . date('Ymd_His') . '.csv';
        $headers = ['Item Name', 'Category', 'Quantity Sold', 'Revenue Generated'];
        
        $rows = [];
        foreach ($topItems as $item) {
            $rows[] = [
                $item['item_name'],
                $item['category_name'],
                $item['quantity_sold'],
                $item['revenue_generated']
            ];
        }

        ExportService::toCsv($filename, $headers, $rows);
    }
}
