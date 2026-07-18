<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DateRange;
use App\Models\Order;
use App\Services\SalesReportService;
use App\Services\ExportService;

class SalesReportController extends Controller {
    private SalesReportService $service;

    public function __construct() {
        $this->service = new SalesReportService();
    }

    public function index(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $summary = $this->service->getSummary($range);
        $orders = $this->service->getOrderItemsSummary($range);

        $this->view('reports/sales', [
            'range' => $range,
            'summary' => $summary,
            'orders' => $orders
        ]);
    }

    public function data(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $interval = $request->input('interval', 'daily');

        $salesTrend = $this->service->getSalesTrend($range, $interval);
        
        $start = $range->getStartString();
        $end = $range->getEndString();
        $aggregates = Order::getAggregatesBetween($start, $end);

        $this->json([
            'trend' => $salesTrend,
            'status_breakdown' => [
                'completed' => (int)($aggregates['completed_orders'] ?? 0),
                'pending' => (int)($aggregates['pending_orders'] ?? 0),
                'preparing' => (int)($aggregates['preparing_orders'] ?? 0),
                'cancelled' => (int)($aggregates['cancelled_orders'] ?? 0)
            ]
        ]);
    }

    public function export(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $orders = $this->service->getOrderItemsSummary($range);

        $filename = 'sales_report_' . $range->getType() . '_' . date('Ymd_His') . '.csv';
        $headers = ['Order Number', 'Date', 'Status', 'Payment Status', 'Subtotal', 'GST', 'Total', 'Table/Room', 'Waiter'];
        
        $rows = [];
        foreach ($orders as $order) {
            $rows[] = [
                $order['order_number'],
                $order['created_at'],
                ucfirst($order['status']),
                strtoupper($order['payment_method']),
                $order['subtotal'],
                $order['gst_amount'],
                $order['total'],
                $order['service_unit'] ?? 'Direct Takeaway',
                $order['waiter'] ?? 'Self Service'
            ];
        }

        ExportService::toCsv($filename, $headers, $rows);
    }
}
