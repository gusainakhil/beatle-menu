<?php

return [
    'GET' => [
        '/' => 'DashboardController@index',
        '/login' => 'AuthController@showLogin',
        '/logout' => 'AuthController@logout',
        
        '/reports/sales' => 'SalesReportController@index',
        '/reports/sales/data' => 'SalesReportController@data',
        '/reports/sales/export' => 'SalesReportController@export',
        
        '/reports/top-items' => 'TopItemsController@index',
        '/reports/top-items/data' => 'TopItemsController@data',
        '/reports/top-items/export' => 'TopItemsController@export',
        
        '/reports/category-sales' => 'CategorySalesController@index',
        '/reports/category-sales/data' => 'CategorySalesController@data',
        
        '/reports/feedback' => 'FeedbackController@index',
        '/reports/feedback/data' => 'FeedbackController@data',
        
        '/reports/waiter' => 'WaiterPerformanceController@index',
        '/reports/waiter/data' => 'WaiterPerformanceController@data',
        
        '/reports/gst' => 'GstReportController@index',
        '/reports/gst/data' => 'GstReportController@data',
        '/reports/gst/export' => 'GstReportController@export',

        '/qr-codes' => 'QrCodeController@index',
        
        '/settings' => 'SettingsController@index',

        '/api/home' => 'ApiController@home',
        '/api/orders' => 'ApiController@orders',
        '/api/order-detail' => 'ApiController@orderDetail',
        '/api/menu-items' => 'ApiController@menuItems',
        '/api/tables' => 'ApiController@tables',
    ],
    'POST' => [
        '/login' => 'AuthController@login',
        '/api/register' => 'ApiController@register',
        '/api/login' => 'ApiController@login',
        '/api/create-order' => 'ApiController@createOrder',
        '/api/update-order-status' => 'ApiController@updateOrderStatus',
        '/api/add-order-items' => 'ApiController@addOrderItems',
        '/api/update-order-item-status' => 'ApiController@updateOrderItemStatus',
        '/qr-codes/generate' => 'QrCodeController@generate',
        '/qr-codes/generate-missing' => 'QrCodeController@generateMissing',
        '/qr-codes/revoke' => 'QrCodeController@revoke',
        '/settings' => 'SettingsController@update',
    ],
    'OPTIONS' => [
        '/api/register' => 'ApiController@options',
        '/api/login' => 'ApiController@options',
        '/api/home' => 'ApiController@options',
        '/api/orders' => 'ApiController@options',
        '/api/order-detail' => 'ApiController@options',
        '/api/create-order' => 'ApiController@options',
        '/api/update-order-status' => 'ApiController@options',
        '/api/add-order-items' => 'ApiController@options',
        '/api/update-order-item-status' => 'ApiController@options',
        '/api/menu-items' => 'ApiController@options',
        '/api/tables' => 'ApiController@options',
    ],
];
