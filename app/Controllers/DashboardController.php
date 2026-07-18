<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DateRange;
use App\Models\Order;
use App\Models\Notification;
use App\Models\Feedback;
use App\Services\TopItemsReportService;
use App\Services\WaiterPerformanceReportService;

class DashboardController extends Controller {
    public function index(Request $request): void {
        $this->middlewareAuth();

        $range = DateRange::fromRequest($request);
        $start = $range->getStartString();
        $end = $range->getEndString();

        $aggregates = Order::getAggregatesBetween($start, $end);
        $recentOrders = Order::getRecentOrders(10);
        
        $notifications = Notification::getUnread();
        $notificationsCount = Notification::getUnreadCount();

        $ratingsSummary = Feedback::getAverageRatingsBetween($start, $end);
        $recentFeedback = Feedback::getRecent(3);

        $topItemsService = new TopItemsReportService();
        $topItems = $topItemsService->getTopSelling($range, 5);

        $waiterService = new WaiterPerformanceReportService();
        $waiterPerformance = $waiterService->getWaiterPerformance($range);
        $topWaiters = array_slice($waiterPerformance, 0, 3);

        $this->view('dashboard/overview', [
            'range' => $range,
            'aggregates' => $aggregates,
            'recentOrders' => $recentOrders,
            'notifications' => $notifications,
            'notificationsCount' => $notificationsCount,
            'ratingsSummary' => $ratingsSummary,
            'recentFeedback' => $recentFeedback,
            'topItems' => $topItems,
            'topWaiters' => $topWaiters
        ]);
    }
}
