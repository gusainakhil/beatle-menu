<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DateRange;
use App\Core\Paginator;
use App\Services\FeedbackReportService;

class FeedbackController extends Controller {
    private FeedbackReportService $service;

    public function __construct() {
        $this->service = new FeedbackReportService();
    }

    public function index(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        
        $page = (int)$request->input('page', 1);
        $limit = 10;
        $totalItems = $this->service->getFeedbackCount($range);
        
        $paginator = new Paginator($totalItems, $limit, $page);
        $offset = $paginator->getOffset();

        $summary = $this->service->getSummary($range);
        $feedbackList = $this->service->getFeedbackList($range, $limit, $offset);

        $this->view('reports/feedback', [
            'range' => $range,
            'summary' => $summary,
            'feedbackList' => $feedbackList,
            'paginator' => $paginator
        ]);
    }

    public function data(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $trend = $this->service->getRatingTrend($range);

        $this->json([
            'trend' => $trend
        ]);
    }
}
