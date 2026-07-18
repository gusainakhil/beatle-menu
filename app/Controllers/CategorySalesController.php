<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DateRange;
use App\Services\CategorySalesReportService;

class CategorySalesController extends Controller {
    private CategorySalesReportService $service;

    public function __construct() {
        $this->service = new CategorySalesReportService();
    }

    public function index(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $categorySales = $this->service->getCategorySales($range);

        $this->view('reports/category-sales', [
            'range' => $range,
            'categorySales' => $categorySales
        ]);
    }

    public function data(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $categorySales = $this->service->getCategorySales($range);

        $this->json([
            'categories' => $categorySales
        ]);
    }
}
