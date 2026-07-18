<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DateRange;
use App\Services\WaiterPerformanceReportService;

class WaiterPerformanceController extends Controller {
    private WaiterPerformanceReportService $service;

    public function __construct() {
        $this->service = new WaiterPerformanceReportService();
    }

    public function index(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $waiters = $this->service->getWaiterPerformance($range);

        $this->view('reports/waiter-performance', [
            'range' => $range,
            'waiters' => $waiters
        ]);
    }

    public function data(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $waiters = $this->service->getWaiterPerformance($range);

        $this->json([
            'waiters' => $waiters
        ]);
    }
}
