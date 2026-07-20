<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\AppUser;
use App\Models\Business;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\QrCode;
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
        $orderId = $this->resolveOrderIdFromRequest($data);

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
        $orderId = $this->resolveOrderIdFromRequest($data);

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

        $items = array_map(fn(array $item): array => $this->formatMenuItem($item), MenuItem::getApiMenuItems($categoryId, $available));

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

    public function menuCategories(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $categories = array_map(function (array $category): array {
            return [
                'id' => $category['id'],
                'name' => $category['name'],
                'display_order' => (int)$category['display_order'],
                'is_hidden' => (bool)$category['is_hidden'],
                'icon' => $category['icon'] ?? 'utensils',
                'created_at' => $category['created_at'] ?? null,
                'updated_at' => $category['updated_at'] ?? null,
            ];
        }, Category::getApiCategories());

        $this->json([
            'success' => true,
            'data' => $categories,
            'meta' => [
                'count' => count($categories),
            ],
        ]);
    }

    public function menuItemDetail(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $menuItemId = isset($data['menu_item_id']) ? trim((string)$data['menu_item_id']) : '';

        if ($menuItemId === '') {
            $this->json([
                'success' => false,
                'message' => 'The menu_item_id field is required.',
            ], 422);
        }

        $item = MenuItem::getApiMenuItemDetail($menuItemId);
        if (!$item) {
            $this->json([
                'success' => false,
                'message' => 'Menu item not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'data' => $this->formatMenuItem($item),
        ]);
    }

    public function createMenuItem(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $this->withUploadedMenuImage($request->all());
        if (empty($data['name'])) {
            $this->json([
                'success' => false,
                'message' => 'The name field is required.',
            ], 422);
        }

        try {
            $item = MenuItem::createApiMenuItem($data);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $this->json([
            'success' => true,
            'message' => 'Menu item created successfully.',
            'data' => $this->formatMenuItem($item),
        ], 201);
    }

    public function updateMenuItem(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $this->withUploadedMenuImage($request->all());
        $menuItemId = isset($data['menu_item_id']) ? trim((string)$data['menu_item_id']) : '';

        if ($menuItemId === '' || empty($data['name'])) {
            $this->json([
                'success' => false,
                'message' => 'The menu_item_id and name fields are required.',
            ], 422);
        }

        try {
            $item = MenuItem::updateApiMenuItem($menuItemId, $data);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (!$item) {
            $this->json([
                'success' => false,
                'message' => 'Menu item not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Menu item updated successfully.',
            'data' => $this->formatMenuItem($item),
        ]);
    }

    public function updateMenuItemAvailability(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $this->withUploadedMenuImage($request->all());
        $menuItemId = isset($data['menu_item_id']) ? trim((string)$data['menu_item_id']) : '';
        $available = filter_var($data['is_available'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($menuItemId === '' || $available === null) {
            $this->json([
                'success' => false,
                'message' => 'The menu_item_id and is_available fields are required.',
            ], 422);
        }

        $item = MenuItem::updateApiAvailability($menuItemId, $available, $data['image_url'] ?? null);
        if (!$item) {
            $this->json([
                'success' => false,
                'message' => 'Menu item not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Menu item availability updated successfully.',
            'data' => $this->formatMenuItem($item),
        ]);
    }

    public function deleteMenuItem(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $menuItemId = isset($data['menu_item_id']) ? trim((string)$data['menu_item_id']) : '';

        if ($menuItemId === '') {
            $this->json([
                'success' => false,
                'message' => 'The menu_item_id field is required.',
            ], 422);
        }

        if (!MenuItem::deleteApiMenuItem($menuItemId)) {
            $this->json([
                'success' => false,
                'message' => 'Menu item not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Menu item deleted successfully.',
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
        $allowedTypes = ['table', 'room'];
        $type = isset($data['type']) && in_array((string)$data['type'], $allowedTypes, true)
            ? (string)$data['type']
            : 'table';

        $tables = array_map(fn(array $table): array => $this->formatTableRoom($table), ServiceUnit::getApiTables($status, $type));

        $this->json([
            'success' => true,
            'data' => $tables,
            'meta' => [
                'count' => count($tables),
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    public function tableRooms(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $allowedStatuses = ['available', 'occupied', 'disabled'];
        $allowedTypes = ['table', 'room'];
        $status = isset($data['status']) && in_array((string)$data['status'], $allowedStatuses, true)
            ? (string)$data['status']
            : null;
        $type = isset($data['type']) && in_array((string)$data['type'], $allowedTypes, true)
            ? (string)$data['type']
            : null;

        $rows = array_map(fn(array $row): array => $this->formatTableRoom($row), ServiceUnit::getApiTables($status, $type));

        $this->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'count' => count($rows),
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    public function tableRoomDetail(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $id = isset($data['table_room_id']) ? trim((string)$data['table_room_id']) : '';

        if ($id === '') {
            $this->json([
                'success' => false,
                'message' => 'The table_room_id field is required.',
            ], 422);
        }

        $row = ServiceUnit::getApiDetail($id);
        if (!$row) {
            $this->json([
                'success' => false,
                'message' => 'Table/room not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'data' => $this->formatTableRoom($row),
        ]);
    }

    public function createTableRoom(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        try {
            $row = ServiceUnit::createApi($request->all());
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'This table/room number already exists.',
                ], 409);
            }
            throw $e;
        }

        $this->json([
            'success' => true,
            'message' => 'Table/room created successfully.',
            'data' => $this->formatTableRoom($row),
        ], 201);
    }

    public function updateTableRoom(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $id = isset($data['table_room_id']) ? trim((string)$data['table_room_id']) : '';

        if ($id === '') {
            $this->json([
                'success' => false,
                'message' => 'The table_room_id field is required.',
            ], 422);
        }

        try {
            $row = ServiceUnit::updateApi($id, $data);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'This table/room number already exists.',
                ], 409);
            }
            throw $e;
        }

        if (!$row) {
            $this->json([
                'success' => false,
                'message' => 'Table/room not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Table/room updated successfully.',
            'data' => $this->formatTableRoom($row),
        ]);
    }

    public function updateTableRoomStatus(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $id = isset($data['table_room_id']) ? trim((string)$data['table_room_id']) : '';
        $status = isset($data['status']) ? trim((string)$data['status']) : '';

        if ($id === '' || $status === '') {
            $this->json([
                'success' => false,
                'message' => 'The table_room_id and status fields are required.',
            ], 422);
        }

        try {
            $row = ServiceUnit::updateApiStatus($id, $status);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (!$row) {
            $this->json([
                'success' => false,
                'message' => 'Table/room not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Table/room status updated successfully.',
            'data' => $this->formatTableRoom($row),
        ]);
    }

    public function deleteTableRoom(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $id = isset($data['table_room_id']) ? trim((string)$data['table_room_id']) : '';

        if ($id === '') {
            $this->json([
                'success' => false,
                'message' => 'The table_room_id field is required.',
            ], 422);
        }

        try {
            $deleted = ServiceUnit::deleteApi($id);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'This table/room is linked with existing records and cannot be deleted.',
                ], 409);
            }
            throw $e;
        }

        if (!$deleted) {
            $this->json([
                'success' => false,
                'message' => 'Table/room not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'Table/room deleted successfully.',
        ]);
    }

    public function updateQrImage(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAuth($request);

        $data = $request->all();
        $qrId = isset($data['qr_id']) ? trim((string)$data['qr_id']) : '';

        if ($qrId === '' && !empty($data['table_room_id'])) {
            $qr = QrCode::findActiveByTableRoom((string)$data['table_room_id']);
            $qrId = $qr['id'] ?? '';
        }

        if ($qrId === '') {
            $this->json([
                'success' => false,
                'message' => 'The qr_id or table_room_id field is required.',
            ], 422);
        }

        $file = $_FILES['image'] ?? $_FILES['qr_image'] ?? null;
        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->json([
                'success' => false,
                'message' => 'The image field is required.',
            ], 422);
        }

        $qr = QrCode::updateImage($qrId, $this->storeUploadedImage($file, 'qr-codes'));
        if (!$qr) {
            $this->json([
                'success' => false,
                'message' => 'QR code not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'QR image uploaded successfully.',
            'data' => $this->formatQrCode($qr),
        ]);
    }

    public function appUsers(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAdmin($request);

        $data = $request->all();
        $role = isset($data['role']) ? (string)$data['role'] : null;
        $status = isset($data['status']) ? (string)$data['status'] : null;

        try {
            $users = array_map(fn(array $user): array => $this->formatAppUser($user), AppUser::getApiUsers($role, $status));
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $this->json([
            'success' => true,
            'data' => $users,
            'meta' => [
                'count' => count($users),
                'role' => $role,
                'status' => $status,
            ],
        ]);
    }

    public function appUserDetail(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAdmin($request);

        $data = $request->all();
        $appUserId = isset($data['app_user_id']) ? trim((string)$data['app_user_id']) : '';

        if ($appUserId === '') {
            $this->json([
                'success' => false,
                'message' => 'The app_user_id field is required.',
            ], 422);
        }

        $user = AppUser::getApiDetail($appUserId);
        if (!$user) {
            $this->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'data' => $this->formatAppUser($user),
        ]);
    }

    public function createAppUser(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAdmin($request);

        try {
            $user = AppUser::createApi($this->withUploadedUserPhoto($request->all()));
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'Email or username already exists.',
                ], 409);
            }
            throw $e;
        }

        $this->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $this->formatAppUser($user),
        ], 201);
    }

    public function updateAppUser(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAdmin($request);

        $data = $this->withUploadedUserPhoto($request->all());
        $appUserId = isset($data['app_user_id']) ? trim((string)$data['app_user_id']) : '';

        if ($appUserId === '') {
            $this->json([
                'success' => false,
                'message' => 'The app_user_id field is required.',
            ], 422);
        }

        try {
            $user = AppUser::updateApi($appUserId, $data);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'Email or username already exists.',
                ], 409);
            }
            throw $e;
        }

        if (!$user) {
            $this->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $this->formatAppUser($user),
        ]);
    }

    public function updateAppUserStatus(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAdmin($request);

        $data = $request->all();
        $appUserId = isset($data['app_user_id']) ? trim((string)$data['app_user_id']) : '';
        $status = isset($data['status']) ? trim((string)$data['status']) : '';

        if ($appUserId === '' || $status === '') {
            $this->json([
                'success' => false,
                'message' => 'The app_user_id and status fields are required.',
            ], 422);
        }

        try {
            $user = AppUser::updateApiStatus($appUserId, $status);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (!$user) {
            $this->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'data' => $this->formatAppUser($user),
        ]);
    }

    public function deleteAppUser(Request $request): void {
        $this->apiHeaders();
        $this->requireApiAdmin($request);

        $data = $request->all();
        $appUserId = isset($data['app_user_id']) ? trim((string)$data['app_user_id']) : '';

        if ($appUserId === '') {
            $this->json([
                'success' => false,
                'message' => 'The app_user_id field is required.',
            ], 422);
        }

        if ((string)Session::get('user_id') === $appUserId) {
            $this->json([
                'success' => false,
                'message' => 'You cannot delete your own user account.',
            ], 422);
        }

        if (!AppUser::deleteApi($appUserId)) {
            $this->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $this->json([
            'success' => true,
            'message' => 'User deleted successfully.',
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

    private function requireApiAdmin(Request $request): void {
        $this->requireApiAuth($request);

        if (Session::get('user_type') !== 'admin') {
            $this->json([
                'success' => false,
                'message' => 'Only admin users can manage app users.',
            ], 403);
        }
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

    private function formatMenuItem(array $item): array {
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
            'type' => $item['dietary_type'] === 'nonveg' ? 'non-veg' : $item['dietary_type'],
            'spicy_level' => $item['spicy_level'],
            'prep_time_minutes' => $item['prep_time_minutes'] !== null ? (int)$item['prep_time_minutes'] : null,
            'cooking_time' => $item['prep_time_minutes'] !== null ? (int)$item['prep_time_minutes'] . ' mins' : null,
            'image_url' => $item['image_url'],
            'gallery_urls' => $this->decodeJsonArray($item['gallery_urls'] ?? null),
            'variants' => [
                [
                    'id' => $item['id'],
                    'label' => 'Regular',
                    'price' => $effectivePrice,
                    'price_label' => $this->formatRupees($effectivePrice),
                ],
            ],
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
    }

    private function formatTableRoom(array $row): array {
        $activeOrderTotal = $row['active_order_total'] !== null ? (float)$row['active_order_total'] : null;
        $title = ucfirst((string)$row['type']) . ' ' . $row['number_label'];

        return [
            'id' => $row['id'],
            'table_room_id' => $row['id'],
            'type' => $row['type'],
            'number_label' => $row['number_label'],
            'name' => $title,
            'status' => $row['status'],
            'is_available' => $row['status'] === 'available',
            'is_occupied' => $row['status'] === 'occupied',
            'is_disabled' => $row['status'] === 'disabled',
            'is_active' => $row['status'] === 'occupied' || $row['active_order_id'] !== null,
            'active_order_id' => $row['active_order_id'],
            'active_order_number' => $row['active_order_number'],
            'active_order_status' => $row['active_order_status'],
            'active_order_total' => $activeOrderTotal,
            'active_order_total_label' => $activeOrderTotal !== null ? $this->formatRupees($activeOrderTotal) : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    private function formatQrCode(array $qr): array {
        return [
            'id' => $qr['id'],
            'qr_id' => $qr['id'],
            'business_id' => $qr['business_id'],
            'table_room_id' => $qr['table_room_id'],
            'encrypted_token' => $qr['encrypted_token'],
            'qr_image_url' => $qr['qr_image_url'],
            'is_active' => (bool)$qr['is_active'],
            'revoked_at' => $qr['revoked_at'],
            'service_unit_name' => $qr['service_unit_name'] ?? null,
            'service_unit_type' => $qr['service_unit_type'] ?? null,
            'service_unit_status' => $qr['service_unit_status'] ?? null,
            'created_at' => $qr['created_at'],
        ];
    }

    private function formatAppUser(array $user): array {
        return [
            'id' => $user['id'],
            'app_user_id' => $user['id'],
            'business_id' => $user['business_id'],
            'role' => $user['role'],
            'user_type' => $user['role'],
            'name' => $user['name'],
            'avatar' => $user['avatar'] ?? '',
            'employee_id' => $user['employee_id'],
            'username' => $user['username'],
            'phone' => $user['phone'],
            'email' => $user['email'],
            'address' => $user['address'],
            'photo_url' => $user['photo_url'],
            'joining_date' => $user['joining_date'],
            'status' => $user['status'],
            'is_active' => $user['status'] === 'active',
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
        ];
    }

    private function withUploadedMenuImage(array $data): array {
        $file = $_FILES['image'] ?? $_FILES['menu_image'] ?? null;

        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $data;
        }

        $data['image_url'] = $this->storeMenuImage($file);
        return $data;
    }

    private function withUploadedUserPhoto(array $data): array {
        $file = $_FILES['photo'] ?? $_FILES['profile_image'] ?? $_FILES['image'] ?? null;

        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $data;
        }

        $data['photo_url'] = $this->storeUploadedImage($file, 'app-users');
        return $data;
    }

    private function storeMenuImage(array $file): string {
        return $this->storeUploadedImage($file, 'menu-items');
    }

    private function storeUploadedImage(array $file, string $folder): string {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->json([
                'success' => false,
                'message' => 'Image upload failed.',
            ], 422);
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            $this->json([
                'success' => false,
                'message' => 'Image size must be 5MB or less.',
            ], 422);
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid uploaded image.',
            ], 422);
        }

        $mimeType = mime_content_type($tmpName);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mimeType])) {
            $this->json([
                'success' => false,
                'message' => 'Only JPG, PNG, and WEBP images are allowed.',
            ], 422);
        }

        $uploadDir = __DIR__ . '/../../public/uploads/' . $folder;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            $this->json([
                'success' => false,
                'message' => 'Unable to create upload directory.',
            ], 500);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            $this->json([
                'success' => false,
                'message' => 'Unable to save uploaded image.',
            ], 500);
        }

        return '/uploads/' . $folder . '/' . $filename;
    }

    private function resolveOrderIdFromRequest(array $data): string {
        $orderId = isset($data['order_id']) ? trim((string)$data['order_id']) : '';

        if ($orderId !== '' && !str_starts_with(strtoupper($orderId), 'ORD-')) {
            return $orderId;
        }

        $orderNumber = isset($data['order_number']) ? trim((string)$data['order_number']) : '';
        if ($orderNumber === '' && str_starts_with(strtoupper($orderId), 'ORD-')) {
            $orderNumber = $orderId;
        }

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
