<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServiceUnit;
use PDOException;

class ApiController extends Controller {
    private const BUSINESS_TYPES = ['restaurant', 'hotel', 'villa', 'cafe', 'bar', 'resort', 'cloud_kitchen'];
    private const LOGIN_USER_TYPES = ['admin', 'waiter'];

    public function options(Request $request): void {
        $this->apiHeaders();
        http_response_code(204);
        exit;
    }

    public function login(Request $request): void {
        $this->apiHeaders();

        $data = $request->all();
        $validator = new Validator();
        $isValid = $validator->validate($data, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$isValid) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->getErrors(),
            ], 422);
        }

        $userType = isset($data['user_type']) ? (string)$data['user_type'] : null;
        if ($userType !== null && !in_array($userType, self::LOGIN_USER_TYPES, true)) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'user_type' => ['The user type must be admin or waiter.'],
                ],
            ], 422);
        }

        $business = Business::findLoginByEmail((string)$data['email'], $userType);
        if (!$business || !password_verify((string)$data['password'], $business['password_hash'])) {
            $this->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        Session::regenerate();
        Session::set('business_id', $business['id']);
        Session::set('user_id', $business['user_id']);
        Session::set('user_type', $business['user_type']);
        Session::set('business_name', $business['name']);
        Session::set('owner_email', $business['email']);

        ActivityLog::log($business['id'], $business['user_id'], 'api_login', 'Admin logged in through API.');

        $this->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'business_id' => $business['id'],
                'admin_user_id' => $business['user_id'],
                'user_id' => $business['user_id'],
                'user_name' => $business['user_name'],
                'user_type' => $business['user_type'],
                'role' => $business['user_type'],
                'business_name' => $business['name'],
                'email' => $business['email'],
            ],
        ]);
    }

    public function home(Request $request): void {
        $this->apiHeaders();

        $this->requireApiAuth($request);

        $today = new \DateTimeImmutable('today');
        $weekStart = $today->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd = $weekStart->modify('+6 days')->setTime(23, 59, 59);
        $monthStart = $today->modify('first day of this month')->setTime(0, 0, 0);
        $monthEnd = $today->modify('last day of this month')->setTime(23, 59, 59);

        $orderStats = Order::getHomeOrderStats();
        $tableStats = ServiceUnit::getTableStats();
        $weekRevenue = Order::getRevenueBetween($weekStart->format('Y-m-d H:i:s'), $weekEnd->format('Y-m-d H:i:s'));
        $monthRevenue = Order::getRevenueBetween($monthStart->format('Y-m-d H:i:s'), $monthEnd->format('Y-m-d H:i:s'));
        $totalRevenue = Order::getTotalCompletedRevenue();

        $recentOrders = array_map(function (array $order): array {
            return [
                'id' => $order['display_id'],
                'order_id' => $order['id'],
                'table' => $order['service_unit_name'] ? 'Table ' . $order['service_unit_name'] : 'Table',
                'items' => $order['items'] ?: 'No items',
                'total' => $this->formatRupees((float)$order['total_amount']),
                'total_amount' => (float)$order['total_amount'],
                'status' => $this->formatOrderStatus((string)$order['status']),
                'status_key' => (string)$order['status'],
                'time' => date('g:i A', strtotime((string)$order['created_at'])),
            ];
        }, Order::getHomeRecentOrders(3));

        $this->json([
            'success' => true,
            'data' => [
                'order_stats' => [
                    ['id' => 'pending', 'title' => 'Pending', 'value' => (string)$orderStats['pending'], 'count' => $orderStats['pending'], 'color' => '#FF9800'],
                    ['id' => 'preparing', 'title' => 'Preparing', 'value' => (string)$orderStats['preparing'], 'count' => $orderStats['preparing'], 'color' => '#2196F3'],
                    ['id' => 'served', 'title' => 'Served', 'value' => (string)$orderStats['served'], 'count' => $orderStats['served'], 'color' => '#4CAF50'],
                ],
                'revenue_stats' => [
                    ['id' => 'week', 'title' => 'This Week', 'value' => $this->formatRupees($weekRevenue), 'amount' => $weekRevenue, 'color' => '#9C27B0'],
                    ['id' => 'month', 'title' => 'This Month', 'value' => $this->formatRupees($monthRevenue), 'amount' => $monthRevenue, 'color' => '#E91E63'],
                    ['id' => 'total', 'title' => 'Total', 'value' => $this->formatRupees($totalRevenue), 'amount' => $totalRevenue, 'color' => '#009688'],
                ],
                'table_stats' => [
                    ['id' => 'active', 'title' => 'Active Tables', 'value' => (string)$tableStats['active'], 'count' => $tableStats['active'], 'color' => '#FF5722'],
                    ['id' => 'total', 'title' => 'Total Tables', 'value' => (string)$tableStats['total'], 'count' => $tableStats['total'], 'color' => '#607D8B'],
                ],
                'recent_orders' => $recentOrders,
            ],
        ]);
    }

    public function orders(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $allowedStatuses = ['pending', 'accepted', 'preparing', 'ready', 'served', 'completed', 'cancelled'];
        $status = isset($data['status']) && in_array((string)$data['status'], $allowedStatuses, true)
            ? (string)$data['status']
            : null;
        $limit = min(max((int)($data['limit'] ?? 20), 1), 100);
        $offset = max((int)($data['offset'] ?? 0), 0);

        $orders = array_map(function (array $order): array {
            $items = array_map(function (array $item): array {
                $lineTotal = (float)$item['unit_price'] * (int)$item['quantity'];

                return [
                    'id' => $item['id'],
                    'menu_item_id' => $item['menu_item_id'],
                    'name' => $item['item_name'],
                    'quantity' => (int)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'unit_price_label' => $this->formatRupees((float)$item['unit_price']),
                    'line_total' => $lineTotal,
                    'line_total_label' => $this->formatRupees($lineTotal),
                    'status' => $item['item_status'],
                    'special_instructions' => $item['special_instructions'],
                ];
            }, OrderItem::getItemsForOrder((string)$order['id']));

            return [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'table_room_id' => $order['table_room_id'],
                'table' => $order['table_label'] ? 'Table ' . $order['table_label'] : null,
                'table_label' => $order['table_label'],
                'waiter_id' => $order['assigned_waiter_id'],
                'waiter_name' => $order['waiter_name'],
                'status' => $order['status'],
                'status_label' => $this->formatOrderStatus((string)$order['status']),
                'subtotal' => (float)$order['subtotal'],
                'subtotal_label' => $this->formatRupees((float)$order['subtotal']),
                'tax_amount' => (float)$order['tax_amount'],
                'tax_amount_label' => $this->formatRupees((float)$order['tax_amount']),
                'service_charge_amount' => (float)$order['service_charge_amount'],
                'service_charge_label' => $this->formatRupees((float)$order['service_charge_amount']),
                'total_amount' => (float)$order['total_amount'],
                'total' => $this->formatRupees((float)$order['total_amount']),
                'is_paid' => (bool)$order['is_paid'],
                'paid_at' => $order['paid_at'],
                'customer_instructions' => $order['customer_instructions'],
                'items_summary' => $order['items_summary'],
                'items' => $items,
                'created_at' => $order['created_at'],
                'updated_at' => $order['updated_at'],
            ];
        }, Order::getApiOrders($status, $limit, $offset));

        $this->json([
            'success' => true,
            'data' => $orders,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($orders),
                'total' => Order::getApiOrdersCount($status),
                'status' => $status,
            ],
        ]);
    }

    public function orderDetail(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $orderId = isset($data['order_id']) ? trim((string)$data['order_id']) : '';

        if ($orderId === '') {
            $this->json([
                'success' => false,
                'message' => 'The order_id field is required.',
            ], 422);
        }

        $order = Order::getApiOrderDetail($orderId);
        if (!$order) {
            $this->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'data' => $this->formatApiOrder($order),
        ]);
    }

    public function createOrder(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();

        if (empty($data['table_room_id'])) {
            $this->json([
                'success' => false,
                'message' => 'The table_room_id field is required.',
            ], 422);
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            $this->json([
                'success' => false,
                'message' => 'The items field is required.',
            ], 422);
        }

        try {
            $order = Order::createApiOrder($data);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $this->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $this->formatApiOrder($order),
        ], 201);
    }

    public function updateOrderStatus(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $orderId = $this->resolveOrderIdFromRequest($data);
        $status = isset($data['status']) ? trim((string)$data['status']) : '';
        $allowedStatuses = ['pending', 'accepted', 'preparing', 'ready', 'served', 'completed', 'cancelled'];

        if ($orderId === '' || $status === '') {
            $this->json([
                'success' => false,
                'message' => 'The order_id and status fields are required.',
            ], 422);
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid order status.',
            ], 422);
        }

        $order = Order::updateApiOrderStatus($orderId, $status);
        if (!$order) {
            $this->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $this->formatApiOrder($order),
        ]);
    }

    public function addOrderItems(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $orderId = isset($data['order_id']) ? trim((string)$data['order_id']) : '';

        if ($orderId === '' || empty($data['items']) || !is_array($data['items'])) {
            $this->json([
                'success' => false,
                'message' => 'The order_id and items fields are required.',
            ], 422);
        }

        try {
            $order = Order::addApiOrderItems($orderId, $data['items']);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (!$order) {
            $this->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Order items added successfully.',
            'data' => $this->formatApiOrder($order),
        ]);
    }

    public function updateOrderItemStatus(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $orderItemId = isset($data['order_item_id']) ? trim((string)$data['order_item_id']) : '';
        $status = isset($data['status']) ? trim((string)$data['status']) : '';
        $allowedStatuses = ['pending', 'preparing', 'ready', 'served', 'cancelled'];

        if ($orderItemId === '' || $status === '') {
            $this->json([
                'success' => false,
                'message' => 'The order_item_id and status fields are required.',
            ], 422);
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid order item status.',
            ], 422);
        }

        if (!Order::updateApiOrderItemStatus($orderItemId, $status)) {
            $this->json([
                'success' => false,
                'message' => 'Order item not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Order item status updated successfully.',
        ]);
    }

    public function menuItems(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $categoryId = isset($data['category_id']) ? (string)$data['category_id'] : null;
        $available = null;
        if (isset($data['available'])) {
            $available = filter_var($data['available'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $items = array_map(function (array $item): array {
            $price = (float)$item['price'];
            $discountPrice = $item['discount_price'] !== null ? (float)$item['discount_price'] : null;
            $effectivePrice = $discountPrice ?? $price;

            return [
                'id' => $item['id'],
                'category_id' => $item['category_id'],
                'category_name' => $item['category_name'],
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => $price,
                'price_label' => $this->formatRupees($price),
                'discount_price' => $discountPrice,
                'discount_price_label' => $discountPrice !== null ? $this->formatRupees($discountPrice) : null,
                'effective_price' => $effectivePrice,
                'effective_price_label' => $this->formatRupees($effectivePrice),
                'dietary_type' => $item['dietary_type'],
                'spicy_level' => $item['spicy_level'],
                'prep_time_minutes' => $item['prep_time_minutes'] !== null ? (int)$item['prep_time_minutes'] : null,
                'image_url' => $item['image_url'],
                'gallery_urls' => $this->decodeJsonArray($item['gallery_urls'] ?? null),
                'is_recommended' => (bool)$item['is_recommended'],
                'is_best_seller' => (bool)$item['is_best_seller'],
                'is_todays_special' => (bool)$item['is_todays_special'],
                'is_available' => (bool)$item['is_available'],
                'display_order' => (int)$item['display_order'],
                'sku' => $item['sku'],
                'barcode' => $item['barcode'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ];
        }, MenuItem::getApiMenuItems($categoryId, $available));

        $this->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'count' => count($items),
                'category_id' => $categoryId,
                'available' => $available,
            ],
        ]);
    }

    public function tables(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $allowedStatuses = ['available', 'occupied', 'disabled'];
        $status = isset($data['status']) && in_array((string)$data['status'], $allowedStatuses, true)
            ? (string)$data['status']
            : null;

        $tables = array_map(function (array $table): array {
            $activeOrderTotal = $table['active_order_total'] !== null ? (float)$table['active_order_total'] : null;

            return [
                'id' => $table['id'],
                'type' => $table['type'],
                'number_label' => $table['number_label'],
                'name' => 'Table ' . $table['number_label'],
                'status' => $table['status'],
                'is_active' => $table['status'] === 'occupied' || $table['active_order_id'] !== null,
                'active_order_id' => $table['active_order_id'],
                'active_order_number' => $table['active_order_number'],
                'active_order_status' => $table['active_order_status'],
                'active_order_total' => $activeOrderTotal,
                'active_order_total_label' => $activeOrderTotal !== null ? $this->formatRupees($activeOrderTotal) : null,
                'created_at' => $table['created_at'],
                'updated_at' => $table['updated_at'],
            ];
        }, ServiceUnit::getApiTables($status));

        $this->json([
            'success' => true,
            'data' => $tables,
            'meta' => [
                'count' => count($tables),
                'status' => $status,
            ],
        ]);
    }

    public function register(Request $request): void {
        $this->apiHeaders();

        $data = $request->all();
        $validator = new Validator();
        $isValid = $validator->validate($data, [
            'business_name' => 'required|min:3',
            'owner_name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'phone_number' => 'required|min:8',
        ]);

        if (!empty($data['business_type']) && !in_array($data['business_type'], self::BUSINESS_TYPES, true)) {
            $errors = $validator->getErrors();
            $errors['business_type'][] = 'The business type is invalid.';
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        if (!$isValid) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->getErrors(),
            ], 422);
        }

        try {
            $registered = Business::register($data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'A business or admin user with this email already exists.',
                    'errors' => [
                        'email' => ['The email has already been registered.'],
                    ],
                ], 409);
            }

            throw $e;
        }

        $this->json([
            'success' => true,
            'message' => 'Business registered successfully.',
            'data' => $registered,
        ], 201);
    }

    private function apiHeaders(): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    }

    private function requireApiAuth(Request $request): void {
        $data = $request->all();
        $userId = isset($data['user_id']) ? trim((string)$data['user_id']) : '';

        if ($userId === '') {
            $this->json([
                'success' => false,
                'message' => 'The user_id field is required.',
                'errors' => [
                    'user_id' => ['Please send user_id with this API request.'],
                ],
            ], 422);
        }

        $context = Business::findApiUserContext($userId);
        if (!$context) {
            $this->json([
                'success' => false,
                'message' => 'Invalid or inactive user_id.',
            ], 401);
        }

        Session::set('business_id', $context['business_id']);
        Session::set('user_id', $context['user_id']);
        Session::set('user_type', $context['user_type']);
        Session::set('business_name', $context['business_name']);
        Session::set('owner_email', $context['business_email']);
    }

    private function formatRupees(float $amount): string {
        $amountString = (string)round($amount);
        $lastThree = substr($amountString, -3);
        $remaining = substr($amountString, 0, -3);

        if ($remaining !== '') {
            $lastThree = ',' . $lastThree;
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
        }

        return '₹' . $remaining . $lastThree;
    }

    private function formatOrderStatus(string $status): string {
        return match ($status) {
            'pending' => 'Pending',
            'accepted', 'preparing', 'ready' => 'Preparing',
            'served', 'completed' => 'Served',
            'cancelled' => 'Cancelled',
            default => ucfirst($status),
        };
    }

    private function formatApiOrder(array $order): array {
        $items = array_map(function (array $item): array {
            $lineTotal = (float)$item['unit_price'] * (int)$item['quantity'];

            return [
                'id' => $item['id'],
                'menu_item_id' => $item['menu_item_id'],
                'name' => $item['item_name'],
                'quantity' => (int)$item['quantity'],
                'unit_price' => (float)$item['unit_price'],
                'unit_price_label' => $this->formatRupees((float)$item['unit_price']),
                'line_total' => $lineTotal,
                'line_total_label' => $this->formatRupees($lineTotal),
                'status' => $item['item_status'],
                'special_instructions' => $item['special_instructions'],
            ];
        }, OrderItem::getItemsForOrder((string)$order['id']));

        return [
            'id' => $order['id'],
            'order_number' => $order['order_number'] ?? null,
            'table_room_id' => $order['table_room_id'] ?? null,
            'table' => !empty($order['table_label']) ? 'Table ' . $order['table_label'] : null,
            'table_label' => $order['table_label'] ?? null,
            'waiter_id' => $order['assigned_waiter_id'] ?? null,
            'waiter_name' => $order['waiter_name'] ?? null,
            'status' => $order['status'],
            'status_label' => $this->formatOrderStatus((string)$order['status']),
            'subtotal' => (float)$order['subtotal'],
            'subtotal_label' => $this->formatRupees((float)$order['subtotal']),
            'tax_amount' => (float)$order['tax_amount'],
            'tax_amount_label' => $this->formatRupees((float)$order['tax_amount']),
            'service_charge_amount' => (float)$order['service_charge_amount'],
            'service_charge_label' => $this->formatRupees((float)$order['service_charge_amount']),
            'total_amount' => (float)$order['total_amount'],
            'total' => $this->formatRupees((float)$order['total_amount']),
            'is_paid' => (bool)$order['is_paid'],
            'paid_at' => $order['paid_at'],
            'customer_instructions' => $order['customer_instructions'],
            'items' => $items,
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
        ];
    }

    private function resolveOrderIdFromRequest(array $data): string {
        $orderId = isset($data['order_id']) ? trim((string)$data['order_id']) : '';

        if ($orderId !== '') {
            return $orderId;
        }

        $orderNumber = isset($data['order_number']) ? trim((string)$data['order_number']) : '';
        if ($orderNumber === '' && isset($data['order_item_id'])) {
            $maybeOrderNumber = trim((string)$data['order_item_id']);
            if (str_starts_with(strtoupper($maybeOrderNumber), 'ORD-')) {
                $orderNumber = $maybeOrderNumber;
            }
        }

        if ($orderNumber === '') {
            return '';
        }

        return Order::findApiOrderIdByNumber($orderNumber) ?? '';
    }

    private function decodeJsonArray(mixed $value): array {
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
