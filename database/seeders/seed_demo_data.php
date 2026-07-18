<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Database;
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->safeLoad();
}

function uuid(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$db = Database::getInstance();

echo "Starting schema2 demo data seeding...\n";

$db->exec("SET FOREIGN_KEY_CHECKS = 0;");
foreach ([
    'report_snapshot',
    'activity_log',
    'notification',
    'feedback',
    'order_item',
    'orders',
    'attendance',
    'qr_code',
    'table_room',
    'menu_item',
    'category',
    'app_user',
    'tax_config',
    'business_settings',
    'platform_setting',
    'business',
] as $table) {
    $db->exec("TRUNCATE TABLE {$table};");
}
$db->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "Tables truncated.\n";

$businessId = uuid();
$adminId = uuid();

$stmt = $db->prepare("
    INSERT INTO business (
        id, business_type, business_name, owner_name, phone_number, whatsapp_number,
        email, tagline, description, opening_time, closing_time, weekly_off,
        address, city, state, country, pin_code, latitude, longitude, currency,
        language, timezone, gps_radius_meters, status
    ) VALUES (?, 'restaurant', ?, ?, ?, ?, ?, ?, ?, '10:00:00', '23:00:00', 'none', ?, ?, ?, ?, ?, ?, ?, 'INR', 'en', 'Asia/Kolkata', 100, 'active')
");
$stmt->execute([
    $businessId,
    'Beetle Bistro',
    'Akhil Golu',
    '+91 98765 43210',
    '+91 98765 43210',
    'owner@beetlebistro.com',
    'Smart dining, sharper analytics',
    'Demo restaurant for Beetle Analytics Smart Menu System.',
    '123 Gourmet Street',
    'Bengaluru',
    'Karnataka',
    'India',
    '560001',
    12.971599,
    77.594566,
]);

$passwordHash = password_hash('password', PASSWORD_DEFAULT);
$stmt = $db->prepare("
    INSERT INTO app_user (
        id, business_id, role, name, username, password_hash, phone, email, joining_date, status
    ) VALUES (?, ?, 'admin', ?, ?, ?, ?, ?, CURDATE(), 'active')
");
$stmt->execute([$adminId, $businessId, 'Akhil Golu', 'owner', $passwordHash, '+91 98765 43210', 'owner@beetlebistro.com']);

$stmt = $db->prepare("
    INSERT INTO business_settings (
        id, business_id, tax_percentage, service_charge_percentage,
        number_of_tables, number_of_rooms, notification_prefs
    ) VALUES (?, ?, 5.00, 0.00, 12, 3, ?)
");
$stmt->execute([uuid(), $businessId, json_encode(['new_order' => true, 'feedback_received' => true])]);

$taxStmt = $db->prepare("INSERT INTO tax_config (id, business_id, tax_name, percentage, is_active) VALUES (?, ?, ?, ?, 1)");
$taxStmt->execute([uuid(), $businessId, 'Food GST', 5.00]);
$taxStmt->execute([uuid(), $businessId, 'Beverage GST', 18.00]);

echo "Business and admin login created: owner@beetlebistro.com / password\n";

$categories = [
    ['name' => 'Appetizers', 'order' => 1],
    ['name' => 'Main Course', 'order' => 2],
    ['name' => 'Desserts', 'order' => 3],
    ['name' => 'Beverages', 'order' => 4],
];

$categoryIds = [];
$stmt = $db->prepare("INSERT INTO category (id, business_id, name, display_order, is_active, is_hidden) VALUES (?, ?, ?, ?, 1, 0)");
foreach ($categories as $cat) {
    $id = uuid();
    $stmt->execute([$id, $businessId, $cat['name'], $cat['order']]);
    $categoryIds[$cat['name']] = $id;
}
echo "Categories created.\n";

$menuItems = [
    ['category' => 'Appetizers', 'name' => 'Garlic Bread', 'price' => 159.00, 'desc' => 'Toasted bread with garlic butter and herbs.', 'diet' => 'veg'],
    ['category' => 'Appetizers', 'name' => 'Chicken Wings', 'price' => 329.00, 'desc' => 'Crispy wings tossed in house sauce.', 'diet' => 'nonveg'],
    ['category' => 'Appetizers', 'name' => 'Spring Rolls', 'price' => 249.00, 'desc' => 'Vegetable rolls served with sweet chili dip.', 'diet' => 'veg'],
    ['category' => 'Appetizers', 'name' => 'Bruschetta', 'price' => 279.00, 'desc' => 'Tomato, basil, olive oil, and toasted bread.', 'diet' => 'veg'],
    ['category' => 'Main Course', 'name' => 'Margherita Pizza', 'price' => 449.00, 'desc' => 'Classic pizza with mozzarella, tomatoes, and basil.', 'diet' => 'veg'],
    ['category' => 'Main Course', 'name' => 'Penne Arrabbiata', 'price' => 399.00, 'desc' => 'Pasta tossed in spicy tomato sauce.', 'diet' => 'veg'],
    ['category' => 'Main Course', 'name' => 'Ribeye Steak', 'price' => 899.00, 'desc' => 'Grilled steak served with potato mash.', 'diet' => 'nonveg'],
    ['category' => 'Main Course', 'name' => 'Grilled Salmon', 'price' => 799.00, 'desc' => 'Grilled salmon with lemon butter.', 'diet' => 'nonveg'],
    ['category' => 'Main Course', 'name' => 'Veggie Burger', 'price' => 349.00, 'desc' => 'Vegetable patty, lettuce, cheese, and sauce.', 'diet' => 'veg'],
    ['category' => 'Desserts', 'name' => 'Chocolate Lava Cake', 'price' => 249.00, 'desc' => 'Warm cake with molten chocolate.', 'diet' => 'egg'],
    ['category' => 'Desserts', 'name' => 'Apple Pie', 'price' => 219.00, 'desc' => 'Classic apple pie served warm.', 'diet' => 'egg'],
    ['category' => 'Desserts', 'name' => 'Cheesecake', 'price' => 279.00, 'desc' => 'Rich cheesecake with strawberry compote.', 'diet' => 'egg'],
    ['category' => 'Desserts', 'name' => 'Tiramisu', 'price' => 299.00, 'desc' => 'Espresso-soaked dessert with mascarpone.', 'diet' => 'egg'],
    ['category' => 'Beverages', 'name' => 'Craft Beer', 'price' => 299.00, 'desc' => 'Local pale ale.', 'diet' => 'veg'],
    ['category' => 'Beverages', 'name' => 'Red Wine (Glass)', 'price' => 449.00, 'desc' => 'Premium Cabernet Sauvignon.', 'diet' => 'veg'],
    ['category' => 'Beverages', 'name' => 'Fresh Lemonade', 'price' => 149.00, 'desc' => 'Fresh lemon and mint.', 'diet' => 'veg'],
    ['category' => 'Beverages', 'name' => 'Double Espresso', 'price' => 129.00, 'desc' => 'Dark rich espresso shot.', 'diet' => 'veg'],
];

$menuItemIds = [];
$stmt = $db->prepare("
    INSERT INTO menu_item (
        id, business_id, category_id, name, description, price, dietary_type,
        spicy_level, prep_time_minutes, gallery_urls, is_available, display_order, is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'none', ?, ?, 1, ?, 1)
");
$displayOrder = 1;
foreach ($menuItems as $item) {
    $id = uuid();
    $stmt->execute([
        $id,
        $businessId,
        $categoryIds[$item['category']],
        $item['name'],
        $item['desc'],
        $item['price'],
        $item['diet'],
        rand(8, 24),
        json_encode([]),
        $displayOrder++,
    ]);
    $menuItemIds[] = [
        'id' => $id,
        'category' => $item['category'],
        'price' => $item['price'],
        'name' => $item['name'],
    ];
}
echo "Menu items created.\n";

$waiters = [
    ['name' => 'Alex Mercer', 'email' => 'alex@beetlebistro.com', 'phone' => '+91 90001 00001'],
    ['name' => 'Sarah Connor', 'email' => 'sarah@beetlebistro.com', 'phone' => '+91 90002 00002'],
    ['name' => 'Michael Scott', 'email' => 'michael@beetlebistro.com', 'phone' => '+91 90003 00003'],
    ['name' => 'Emily Watson', 'email' => 'emily@beetlebistro.com', 'phone' => '+91 90004 00004'],
    ['name' => 'David Miller', 'email' => 'david@beetlebistro.com', 'phone' => '+91 90005 00005'],
];
$waiterIds = [];
$stmt = $db->prepare("
    INSERT INTO app_user (
        id, business_id, role, name, employee_id, username, phone, email, joining_date, status
    ) VALUES (?, ?, 'waiter', ?, ?, ?, ?, ?, CURDATE(), 'active')
");
$employee = 101;
foreach ($waiters as $waiter) {
    $id = uuid();
    $stmt->execute([$id, $businessId, $waiter['name'], 'W' . $employee, strtolower(strtok($waiter['name'], ' ')), $waiter['phone'], $waiter['email']]);
    $waiterIds[] = $id;
    $employee++;
}
echo "Waiters created.\n";

$tableRooms = [];
$stmt = $db->prepare("INSERT INTO table_room (id, business_id, type, number_label, status) VALUES (?, ?, ?, ?, 'available')");
for ($i = 1; $i <= 12; $i++) {
    $id = uuid();
    $stmt->execute([$id, $businessId, 'table', "Table {$i}"]);
    $tableRooms[] = ['id' => $id, 'label' => "Table {$i}", 'type' => 'table'];
}
for ($i = 101; $i <= 103; $i++) {
    $id = uuid();
    $stmt->execute([$id, $businessId, 'room', "Room {$i}"]);
    $tableRooms[] = ['id' => $id, 'label' => "Room {$i}", 'type' => 'room'];
}
echo "Tables and rooms created.\n";

$stmt = $db->prepare("INSERT INTO qr_code (id, business_id, table_room_id, encrypted_token, qr_image_url, is_active) VALUES (?, ?, ?, ?, ?, 1)");
foreach ($tableRooms as $unit) {
    $token = 'QR-' . strtoupper(bin2hex(random_bytes(8)));
    $stmt->execute([uuid(), $businessId, $unit['id'], $token, '/qr/' . strtolower($token) . '.png']);
}
echo "QR codes created.\n";

echo "Generating orders...\n";
$orderStmt = $db->prepare("
    INSERT INTO orders (
        id, business_id, table_room_id, assigned_waiter_id, status, subtotal,
        tax_amount, service_charge_amount, total_amount, is_paid, paid_at,
        customer_instructions, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$itemStmt = $db->prepare("
    INSERT INTO order_item (id, order_id, menu_item_id, quantity, unit_price, special_instructions, item_status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$feedbackStmt = $db->prepare("
    INSERT INTO feedback (
        id, order_id, business_id, overall_rating, food_rating, service_rating,
        staff_rating, cleanliness_rating, comment, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$commentsList = [
    5 => ['Fantastic food and fast service!', 'Highly recommended, will come back!', 'Perfect meal and pleasant staff.'],
    4 => ['Very good food. Waiter was polite.', 'Enjoyable experience. Service took slightly long.', 'Clean place and decent prices.'],
    3 => ['Average taste, but fine.', 'Food was okay, but waiter was in a rush.', 'Nice ambience, food could be warmer.'],
    2 => ['Disappointing meal. Steak was overcooked.', 'Service was very slow.', 'Wrong order delivered first.'],
    1 => ['Cold food and poor service.', 'Will not recommend. Extremely delayed service.', 'Bad experience overall.'],
];

$startDate = new DateTime('-90 days');
$endDate = new DateTime('now');
$orderCount = 0;

while ($startDate <= $endDate) {
    $dayOfWeek = (int)$startDate->format('N');
    $numOrders = ($dayOfWeek >= 5) ? rand(2, 6) : rand(1, 4);

    for ($o = 0; $o < $numOrders; $o++) {
        $orderCount++;
        $unit = $tableRooms[array_rand($tableRooms)];
        $waiterId = $waiterIds[array_rand($waiterIds)];

        $randStatus = rand(1, 100);
        if ($randStatus <= 88) {
            $status = 'completed';
        } elseif ($randStatus <= 93) {
            $status = 'preparing';
        } elseif ($randStatus <= 97) {
            $status = 'pending';
        } else {
            $status = 'cancelled';
        }

        $itemsCount = rand(1, 4);
        $selectedItems = [];
        $subtotal = 0.0;
        $taxAmount = 0.0;

        for ($i = 0; $i < $itemsCount; $i++) {
            $item = $menuItemIds[array_rand($menuItemIds)];
            $quantity = rand(1, 2);
            $itemSubtotal = $item['price'] * $quantity;
            $subtotal += $itemSubtotal;
            $taxAmount += $itemSubtotal * (($item['category'] === 'Beverages') ? 0.18 : 0.05);

            $selectedItems[] = [
                'id' => $item['id'],
                'quantity' => $quantity,
                'price' => $item['price'],
            ];
        }

        $subtotal = round($subtotal, 2);
        $taxAmount = round($taxAmount, 2);
        $serviceCharge = (rand(1, 100) <= 20) ? round($subtotal * 0.05, 2) : 0.00;
        $total = round($subtotal + $taxAmount + $serviceCharge, 2);

        $orderDate = clone $startDate;
        $hour = (rand(0, 1) === 0) ? rand(12, 14) : rand(18, 22);
        $orderDate->setTime($hour, rand(0, 59), rand(0, 59));
        $dateStr = $orderDate->format('Y-m-d H:i:s');

        $orderId = uuid();
        $paid = $status === 'completed' ? 1 : 0;
        $orderStmt->execute([
            $orderId,
            $businessId,
            $unit['id'],
            $waiterId,
            $status,
            $subtotal,
            $taxAmount,
            $serviceCharge,
            $total,
            $paid,
            $paid ? $dateStr : null,
            rand(1, 10) === 1 ? 'Extra spicy where possible' : null,
            $dateStr,
            $dateStr,
        ]);

        foreach ($selectedItems as $sItem) {
            $itemStmt->execute([
                uuid(),
                $orderId,
                $sItem['id'],
                $sItem['quantity'],
                $sItem['price'],
                rand(1, 10) === 1 ? 'No onion' : null,
                in_array($status, ['completed', 'served'], true) ? 'served' : $status,
                $dateStr,
                $dateStr,
            ]);
        }

        if ($status === 'completed' && rand(1, 100) <= 60) {
            $randRating = rand(1, 100);
            if ($randRating <= 45) {
                $rating = 5;
            } elseif ($randRating <= 75) {
                $rating = 4;
            } elseif ($randRating <= 90) {
                $rating = 3;
            } elseif ($randRating <= 96) {
                $rating = 2;
            } else {
                $rating = 1;
            }

            $feedbackStmt->execute([
                uuid(),
                $orderId,
                $businessId,
                $rating,
                min(5, max(1, $rating + rand(-1, 1))),
                min(5, max(1, $rating + rand(-1, 1))),
                min(5, max(1, $rating + rand(-1, 1))),
                min(5, max(1, $rating + rand(-1, 1))),
                $commentsList[$rating][array_rand($commentsList[$rating])],
                $dateStr,
            ]);
        }
    }

    $startDate->modify('+1 day');
}

echo "Total orders generated: {$orderCount}\n";

$logStmt = $db->prepare("
    INSERT INTO activity_log (id, business_id, actor_user_id, action, entity_type, before_state, ip_address, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ([new DateTime('-2 hours'), new DateTime('-1 day'), new DateTime('-3 days'), new DateTime('-7 days')] as $logDate) {
    $logStmt->execute([
        uuid(),
        $businessId,
        $adminId,
        'login',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X)',
        json_encode(['description' => 'Admin logged in.']),
        '127.0.0.1',
        $logDate->format('Y-m-d H:i:s'),
    ]);
}
$logStmt->execute([uuid(), $businessId, $adminId, 'export_data', 'Mozilla/5.0 (Macintosh; Intel Mac OS X)', json_encode(['description' => 'Exported a report.']), '127.0.0.1', date('Y-m-d H:i:s')]);

$notifStmt = $db->prepare("
    INSERT INTO notification (id, business_id, recipient_user_id, type, title, body, is_read, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$notifStmt->execute([uuid(), $businessId, $adminId, 'feedback_received', 'New Feedback Received', 'A customer left a 5-star rating with comments.', 0, date('Y-m-d H:i:s', strtotime('-10 minutes'))]);
$notifStmt->execute([uuid(), $businessId, $adminId, 'attendance_reminder', 'Attendance Reminder', 'Review waiter attendance for today.', 0, date('Y-m-d H:i:s', strtotime('-1 day'))]);
$notifStmt->execute([uuid(), $businessId, $adminId, 'payment_confirmed', 'Payment Confirmed', 'A completed table payment was confirmed.', 1, date('Y-m-d H:i:s', strtotime('-5 days'))]);

echo "Notifications and activity logs seeded.\n";
echo "Schema2 demo data seeding completed successfully!\n";
