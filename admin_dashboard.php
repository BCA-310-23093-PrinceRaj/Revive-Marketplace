<?php 
require_once 'config/db.php';
require_once 'includes/financial_report.php';
include 'includes/header.php'; 

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<section class='py-40 text-center px-6'>
            <h1 class='text-4xl font-bold text-red-500 mb-4'>Access Denied</h1>
            <p class='text-gray-400 mb-8'>You do not have permission to view this page.</p>
            <a href='index.php' class='neon-btn px-8 py-3 rounded-xl font-bold'>Back to Home</a>
          </section>";
    include 'includes/footer.php';
    exit();
}

$section = $_GET['section'] ?? 'dashboard';

// Search Logic
$search_query = $_GET['search'] ?? '';
$search_escaped = $conn->real_escape_string($search_query);
$search_sql = $search_query ? " AND (title LIKE '%$search_escaped%' OR brand LIKE '%$search_escaped%')" : "";
$user_search_sql = $search_query ? " WHERE (name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%')" : "";

// Global Stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'available'")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;
$financial_report = $section === 'reports'
    ? get_financial_report($conn, $_GET['from'] ?? null, $_GET['to'] ?? null)
    : null;

include 'includes/admin_sidebar.php';
?>

<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
    .swiper { width: 100%; height: 350px; border-radius: 2.5rem; overflow: hidden; margin-bottom: 4rem; }
    .swiper-slide { background: rgba(255,255,255,0.05); display: flex; flex-direction: column; justify-content: center; padding: 3rem; border: 1px solid rgba(255,255,255,0.1); }
    .swiper-pagination-bullet { background: #bcff00 !important; }
</style>



<main class="lg:ml-72 min-h-screen pb-20">
    <!-- Section Header -->
    <header class="p-8 border-b border-white/5 bg-black/40 backdrop-blur-md sticky top-0 z-40 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold tracking-tight uppercase">
                <?php 
                    echo match($section) {
                        'inventory' => 'Inventory <span class="neon-text italic">Master</span>',
                        'orders' => 'Order <span class="neon-text italic">Logistics</span>',
                        'reports' => 'Financial <span class="neon-text italic">Reports</span>',
                        'categories' => 'Category <span class="neon-text italic">Architect</span>',
                        'community' => 'Community <span class="neon-text italic">Oversight</span>',
                        'messages' => 'Communication <span class="neon-text italic">Hub</span>',
                        'disputes' => 'Dispute <span class="neon-text italic">Resolution</span>',
                        default => 'Command <span class="neon-text italic">Center</span>'
                    };
                ?>
            </h1>
            <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-1">
                <?php echo date('l, F d, Y'); ?> • SYSTEM SECURE
            </p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 rounded-full border border-[#bcff00]/20 flex items-center justify-center p-0.5">
                <div class="w-full h-full rounded-full bg-[#bcff00]/10 flex items-center justify-center font-bold text-[#bcff00] text-xs">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>

    <div class="p-8 lg:p-12 max-w-[1600px] mx-auto">
        <?php if($section === 'dashboard'): ?>
            <!-- Dashboard Content -->
            <div class="space-y-16">
                
                <!-- Platform Slider -->
                <div class="swiper mySwiper">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide relative overflow-hidden">
                            <div class="z-10">
                                <span class="bg-[#bcff00]/20 text-[#bcff00] px-4 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border border-[#bcff00]/30 mb-6 inline-block">System Intelligence</span>
                                <h2 class="text-4xl font-bold mb-4 tracking-tighter">Welcome to the <span class="neon-text">Command Center</span></h2>
                                <p class="text-gray-400 max-w-xl text-lg leading-relaxed">Your platform oversight is active. Monitor real-time transactions, manage community growth, and moderate content from this unified interface.</p>
                            </div>
                            <div class="absolute -right-20 -bottom-20 opacity-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-96 w-96" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                        </div>
                        <div class="swiper-slide relative overflow-hidden">
                            <div class="z-10">
                                <span class="bg-blue-500/20 text-blue-400 px-4 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border border-blue-500/30 mb-6 inline-block">Security Alert</span>
                                <h2 class="text-4xl font-bold mb-4 tracking-tighter">Zero-Trust <span class="text-blue-400">Environment</span></h2>
                                <p class="text-gray-400 max-w-xl text-lg leading-relaxed">System logs indicate stable performance. All administrative actions are being recorded for security audits and compliance tracking.</p>
                            </div>
                            <div class="absolute -right-20 -bottom-20 opacity-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-96 w-96" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white/5 border border-white/10 p-8 rounded-3xl relative overflow-hidden group">
                        <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Total Users</p>
                        <p class="text-4xl font-bold"><?php echo number_format($total_users); ?></p>
                        <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                    </div>
                    <div class="bg-white/5 border border-white/10 p-8 rounded-3xl relative overflow-hidden group">
                        <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Products Listed</p>
                        <p class="text-4xl font-bold text-[#bcff00]"><?php echo number_format($total_products); ?></p>
                        <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 2v4M8 2v4m-5 4h18M5 10v10a2 2 0 002 2h10a2 2 0 002-2V10M10 14h4m-2-2v4" /></svg>
                        </div>
                    </div>
                    <div class="bg-white/5 border border-white/10 p-8 rounded-3xl relative overflow-hidden group">
                        <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Orders Placed</p>
                        <p class="text-4xl font-bold text-blue-400"><?php echo number_format($total_orders); ?></p>
                        <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        </div>
                    </div>
                    <div class="bg-[#bcff00]/5 border border-[#bcff00]/20 p-8 rounded-3xl relative overflow-hidden group">
                        <p class="text-[#bcff00] uppercase tracking-widest text-[10px] font-bold mb-2">Total Revenue</p>
                        <p class="text-4xl font-bold">₹<?php echo number_format($total_revenue, 0); ?></p>
                        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:opacity-20 transition text-[#bcff00]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-white/5 border border-white/10 p-10 rounded-[2.5rem]">
                        <h3 class="text-xl font-bold mb-8">Platform Activity</h3>
                        <div class="h-[350px]">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white/5 border border-white/10 p-10 rounded-[2.5rem]">
                        <h3 class="text-xl font-bold mb-8">Growth Trend</h3>
                        <div class="h-[300px]">
                            <canvas id="userChart"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Platform Metrics Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white/5 border border-white/10 p-6 rounded-3xl flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#bcff00]/10 flex items-center justify-center text-[#bcff00]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7" /></svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">Server Status</p>
                            <p class="text-sm font-bold text-[#bcff00]">ONLINE • DB CONNECTED</p>
                        </div>
                    </div>
                    <div class="bg-white/5 border border-white/10 p-6 rounded-3xl flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">Security Layer</p>
                            <p class="text-sm font-bold">OTP 2FA + CSRF ACTIVE</p>
                        </div>
                    </div>
                    <div class="bg-white/5 border border-white/10 p-6 rounded-3xl flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">Session Started</p>
                            <p class="text-sm font-bold"><?php echo date('d M Y, h:i A'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Grid -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Latest Transactions -->
                    <div class="bg-white/5 border border-white/10 rounded-[2.5rem] overflow-hidden">
                        <div class="p-8 border-b border-white/5 flex justify-between items-center">
                            <h3 class="font-bold uppercase tracking-widest text-xs">Recent Transactions</h3>
                            <a href="admin_dashboard.php?section=inventory" class="text-[10px] text-[#bcff00] font-bold uppercase tracking-widest hover:underline">View All</a>
                        </div>
                        <div class="p-4">
                            <table class="w-full text-left">
                                <tbody class="divide-y divide-white/5">
                                    <?php 
                                    $recent_orders = $conn->query("SELECT o.*, u.name as buyer, p.title FROM orders o JOIN users u ON o.buyer_id = u.id JOIN products p ON o.product_id = p.id ORDER BY o.created_at DESC LIMIT 5");
                                    while($ro = $recent_orders->fetch_assoc()):
                                    ?>
                                    <tr class="hover:bg-white/[0.02] transition group">
                                        <td class="p-4">
                                            <p class="font-bold text-sm"><?php echo $ro['title']; ?></p>
                                            <p class="text-[10px] text-gray-500 italic">by <?php echo $ro['buyer']; ?></p>
                                        </td>
                                        <td class="p-4 text-right">
                                            <p class="font-bold text-sm">₹<?php echo number_format($ro['amount']); ?></p>
                                            <p class="text-[9px] text-[#bcff00] uppercase font-bold"><?php echo $ro['status']; ?></p>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- New Members -->
                    <div class="bg-white/5 border border-white/10 rounded-[2.5rem] overflow-hidden">
                        <div class="p-8 border-b border-white/5 flex justify-between items-center">
                            <h3 class="font-bold uppercase tracking-widest text-xs">Newest Members</h3>
                            <a href="admin_dashboard.php?section=community" class="text-[10px] text-[#bcff00] font-bold uppercase tracking-widest hover:underline">Manage Users</a>
                        </div>
                        <div class="p-4">
                            <table class="w-full text-left">
                                <tbody class="divide-y divide-white/5">
                                    <?php 
                                    $recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                                    while($ru = $recent_users->fetch_assoc()):
                                    ?>
                                    <tr class="hover:bg-white/[0.02] transition">
                                        <td class="p-4 flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center font-bold text-[10px]">
                                                <?php echo strtoupper(substr($ru['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-sm"><?php echo $ru['name']; ?></p>
                                                <p class="text-[10px] text-gray-500"><?php echo $ru['email']; ?></p>
                                            </div>
                                        </td>
                                        <td class="p-4 text-right">
                                            <p class="text-[9px] text-gray-500 uppercase font-bold tracking-widest"><?php echo date('M d', strtotime($ru['created_at'])); ?></p>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-2xl font-bold mb-8">Moderation <span class="neon-text">Queue</span></h2>
                    <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold border-b border-white/5">
                                <tr>
                                    <th class="p-6">Product</th>
                                    <th class="p-6">Seller</th>
                                    <th class="p-6">Price</th>
                                    <th class="p-6 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php
                                $pending = $conn->query("SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.status = 'pending' LIMIT 5");
                                while($p = $pending->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-white/[0.02] transition">
                                        <td class="p-6 flex items-center space-x-4">
                                            <img src="assets/img/products/<?php echo $p['images']; ?>" class="w-12 h-12 rounded-xl object-cover border border-white/10">
                                            <div>
                                                <p class="font-bold"><?php echo $p['title']; ?></p>
                                                <p class="text-[10px] text-gray-500 uppercase"><?php echo $p['brand']; ?></p>
                                            </div>
                                        </td>
                                        <td class="p-6 text-gray-400"><?php echo $p['seller_name']; ?></td>
                                        <td class="p-6 font-bold text-[#bcff00]">₹<?php echo number_format($p['price']); ?></td>
                                        <td class="p-6 text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <form action="actions/admin_action.php" method="POST">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="approve_product">
                                                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="bg-[#bcff00] text-black px-4 py-1.5 rounded-lg font-bold text-[10px] uppercase hover:bg-[#aaee00] transition">Approve</button>
                                                </form>
                                                <button type="button" onclick="openAdminConfirmModal('reject_product', <?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['title'])); ?>', 'Reject Product', 'This product will be rejected and the seller will be notified.')" class="bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20 px-4 py-1.5 rounded-lg font-bold text-[10px] uppercase transition">Reject</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif($section === 'orders'): ?>
            <!-- Orders Management Section -->
            <div class="space-y-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl font-bold tracking-tighter italic">Logistics <span class="neon-text">Queue</span></h2>
                </div>

                <div class="bg-white/5 border border-white/10 rounded-[2.5rem] overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white/[0.02] text-[10px] uppercase tracking-[0.2em] text-gray-500">
                                <th class="p-6">Order</th>
                                <th class="p-6">Buyer</th>
                                <th class="p-6">Product</th>
                                <th class="p-6">Amount</th>
                                <th class="p-6">Status</th>
                                <th class="p-6 text-right">Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php 
                            $orders = $conn->query("SELECT o.*, u.name as buyer, p.title FROM orders o JOIN users u ON o.buyer_id = u.id JOIN products p ON o.product_id = p.id ORDER BY o.created_at DESC");
                            while($o = $orders->fetch_assoc()):
                            ?>
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="p-6 font-bold text-sm">#REV-<?php echo $o['id']; ?></td>
                                <td class="p-6">
                                    <p class="font-bold text-sm"><?php echo $o['buyer']; ?></p>
                                    <p class="text-[10px] text-gray-500"><?php echo $o['phone']; ?></p>
                                </td>
                                <td class="p-6 font-bold text-sm text-gray-300">
                                    <p class="font-bold text-sm text-gray-300"><?php echo $o['title']; ?></p>
                                    <?php if ($o['tracking_number']): ?>
                                        <p class="text-[10px] text-gray-500 mt-1 font-semibold"><?php echo htmlspecialchars($o['carrier']); ?>: <span class="font-mono text-[#bcff00] font-bold"><?php echo htmlspecialchars($o['tracking_number']); ?></span></p>
                                    <?php endif; ?>
                                </td>
                                <td class="p-6 font-bold text-[#bcff00]">₹<?php echo number_format($o['amount']); ?></td>
                                <td class="p-6">
                                    <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-widest <?php echo $o['status'] === 'completed' ? 'bg-[#bcff00]/10 text-[#bcff00]' : 'bg-white/5 text-gray-400'; ?>">
                                        <?php echo $o['status']; ?>
                                    </span>
                                </td>
                                <td class="p-6 text-right">
                                    <div class="flex flex-col items-end space-y-2">
                                        <form action="actions/admin_action.php" method="POST" class="flex items-center justify-end space-x-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="update_order_status">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="bg-[#111] text-white border border-white/10 rounded-lg px-2 py-1 text-[10px] uppercase font-bold focus:outline-none focus:border-[#bcff00]">
                                                <option value="pending" class="bg-[#111] text-white" <?php echo $o['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="shipped" class="bg-[#111] text-white" <?php echo $o['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" class="bg-[#111] text-white" <?php echo $o['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="completed" class="bg-[#111] text-white" <?php echo $o['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </form>
                                        <button type="button" onclick="openAdminShippingModal(<?php echo $o['id']; ?>, '<?php echo htmlspecialchars($o['carrier'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($o['tracking_number'] ?? '', ENT_QUOTES); ?>')" class="text-[9px] text-[#bcff00] uppercase font-bold tracking-widest hover:underline">
                                            <?php echo $o['tracking_number'] ? 'Edit Tracker' : 'Add Tracker'; ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif($section === 'inventory'): ?>
            <!-- Inventory Management -->
            <div class="space-y-12" data-aos="fade-up">
                <div class="flex justify-between items-center">
                    <h2 class="text-3xl font-bold">Platform <span class="neon-text">Inventory</span></h2>
                    <form action="" method="GET" class="flex space-x-4">
                        <input type="hidden" name="section" value="inventory">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search products..." class="bg-white/5 border border-white/10 rounded-xl px-6 py-2 text-sm focus:outline-none focus:border-[#bcff00]">
                        <button type="submit" class="bg-[#bcff00] text-black px-4 py-2 rounded-xl font-bold text-xs">Filter</button>
                    </form>
                </div>

                <?php
                $pending_products = $conn->query("SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.status = 'pending' ORDER BY p.created_at ASC");
                if ($pending_products->num_rows > 0):
                ?>
                <!-- ⚠️ Pending Approval Section -->
                <div class="bg-yellow-500/5 border border-yellow-500/20 rounded-[2.5rem] overflow-hidden">
                    <div class="p-6 border-b border-yellow-500/10 flex items-center space-x-4">
                        <div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center text-yellow-400 animate-pulse">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-black text-yellow-400 uppercase tracking-widest text-xs">⚡ Pending Approval — <?php echo $pending_products->num_rows; ?> Product(s) Awaiting Review</h3>
                            <p class="text-gray-500 text-[11px] mt-0.5">These products are NOT yet visible on the shop. Approve to make them live, or Reject to remove them.</p>
                        </div>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-yellow-500/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold">
                            <tr>
                                <th class="p-5">Product</th>
                                <th class="p-5">Seller</th>
                                <th class="p-5">Price</th>
                                <th class="p-5">Submitted</th>
                                <th class="p-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-yellow-500/5">
                            <?php while($pp = $pending_products->fetch_assoc()): ?>
                            <tr class="hover:bg-yellow-500/[0.03] transition">
                                <td class="p-5">
                                    <p class="font-bold"><?php echo htmlspecialchars($pp['title']); ?></p>
                                    <p class="text-[10px] text-gray-500"><?php echo htmlspecialchars($pp['brand']); ?> • <?php echo htmlspecialchars($pp['size']); ?></p>
                                </td>
                                <td class="p-5 text-sm text-gray-300"><?php echo htmlspecialchars($pp['seller_name']); ?></td>
                                <td class="p-5 font-bold text-[#bcff00]">₹<?php echo number_format($pp['price']); ?></td>
                                <td class="p-5 text-[11px] text-gray-500"><?php echo date('d M, H:i', strtotime($pp['created_at'])); ?></td>
                                <td class="p-5 text-right">
                                    <div class="flex items-center justify-end space-x-3">
                                        <form action="actions/admin_action.php" method="POST" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="approve_product">
                                            <input type="hidden" name="product_id" value="<?php echo $pp['id']; ?>">
                                            <button type="submit" class="px-5 py-2 bg-[#bcff00] text-black text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-[#d4ff4d] transition">✓ Approve</button>
                                        </form>
                                        <button type="button" onclick="openAdminConfirmModal('reject_product', <?php echo $pp['id']; ?>, '<?php echo addslashes(htmlspecialchars($pp['title'])); ?>', 'Reject Product', 'This will permanently delete the product and notify the seller.')" class="px-5 py-2 bg-red-500/10 border border-red-500/30 text-red-400 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-red-500 hover:text-white transition">
                                            ✕ Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold border-b border-white/5">
                            <tr>
                                <th class="p-6">ID</th>
                                <th class="p-6">Product Item</th>
                                <th class="p-6">Status</th>
                                <th class="p-6">Price</th>
                                <th class="p-6 text-right">Management</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php
                            $all_p = $conn->query("SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE 1=1 $search_sql ORDER BY p.created_at DESC");
                            if($all_p->num_rows > 0):
                                while($p = $all_p->fetch_assoc()):
                            ?>
                                <tr class="hover:bg-white/[0.02] transition">
                                    <td class="p-6 text-gray-600 font-mono text-xs">#<?php echo $p['id']; ?></td>
                                    <td class="p-6">
                                        <p class="font-bold"><?php echo $p['title']; ?></p>
                                        <p class="text-[10px] text-gray-500 uppercase"><?php echo $p['brand']; ?> • by <?php echo $p['seller_name']; ?></p>
                                    </td>
                                    <td class="p-6">
                                        <span class="text-[9px] px-3 py-1 rounded-full border 
                                            <?php echo match($p['status']) {
                                                'available' => 'border-[#bcff00]/40 text-[#bcff00] bg-[#bcff00]/5',
                                                'sold' => 'border-blue-500/40 text-blue-500 bg-blue-500/5',
                                                'pending' => 'border-yellow-500/40 text-yellow-500 bg-yellow-500/5',
                                                default => 'border-white/10 text-gray-500'
                                            }; ?> font-bold uppercase">
                                            <?php echo $p['status']; ?>
                                        </span>
                                    </td>
                                    <td class="p-6 font-bold text-gray-200">₹<?php echo number_format($p['price']); ?></td>
                                    <td class="p-6 text-right">
                                        <div class="flex items-center justify-end space-x-4">
                                            <?php if ($p['status'] === 'pending'): ?>
                                            <form action="actions/admin_action.php" method="POST" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="approve_product">
                                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="text-[#bcff00] font-black text-[10px] uppercase tracking-widest hover:underline">Approve</button>
                                            </form>
                                            <?php endif; ?>
                                            <button type="button" onclick="openAdminConfirmModal('delete_product', <?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['title'])); ?>', 'Delete Product', 'This product and all its images will be permanently deleted.')" class="text-red-500/50 hover:text-red-500 transition font-bold text-[10px] uppercase">Destroy</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                                echo "<tr><td colspan='5' class='p-20 text-center text-gray-500 italic'>No matching products found in the inventory.</td></tr>";
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif($section === 'categories'): ?>
            <!-- Category Management -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12" data-aos="fade-up">
                <div class="lg:col-span-2 space-y-8">
                    <h2 class="text-3xl font-bold">Manage <span class="neon-text">Categories</span></h2>
                    <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold">
                                <tr>
                                    <th class="p-6">Category Name</th>
                                    <th class="p-6">Items Count</th>
                                    <th class="p-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php
                                $cats = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as p_count FROM categories c");
                                while($c = $cats->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-white/[0.02] transition">
                                        <td class="p-6 font-bold text-lg"><?php echo $c['name']; ?></td>
                                        <td class="p-6 text-gray-400 font-medium"><?php echo $c['p_count']; ?> products</td>
                                        <td class="p-6 text-right">
                                            <button type="button" onclick="openAdminConfirmModal('delete_category', <?php echo $c['id']; ?>, '<?php echo addslashes(htmlspecialchars($c['name'])); ?>', 'Delete Category', 'All products in this category may become uncategorized.')" class="text-red-500/50 hover:text-red-500 transition font-bold text-[10px] uppercase">Remove</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="space-y-8">
                    <h2 class="text-3xl font-bold">Add <span class="neon-text">New</span></h2>
                    <div class="bg-white/5 border border-white/10 p-8 rounded-[2.5rem]">
                        <form action="actions/admin_action.php" method="POST" class="space-y-6">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="add_category">
                            <div>
                                <label class="text-[10px] uppercase tracking-widest text-gray-500 font-bold mb-4 block">Category Label</label>
                                <input type="text" name="name" required placeholder="e.g. Streetwear" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00]">
                            </div>
                            <button type="submit" class="neon-btn w-full py-4 rounded-2xl font-bold">Add to Catalog</button>
                        </form>
                    </div>
                </div>
            </div>

        <?php elseif($section === 'messages'): ?>
            <!-- Message Inquiries -->
            <div class="space-y-8" data-aos="fade-up">
                <h2 class="text-3xl font-bold">Customer <span class="neon-text">Inquiries</span></h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php
                    $msgs = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
                    if($msgs->num_rows > 0):
                        while($m = $msgs->fetch_assoc()):
                    ?>
                        <div class="bg-white/5 border border-white/10 p-10 rounded-[2.5rem] relative group hover:border-[#bcff00]/20 transition">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <p class="font-bold text-xl"><?php echo $m['name']; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $m['email']; ?></p>
                                </div>
                                <span class="text-[10px] text-gray-600"><?php echo date('M d', strtotime($m['created_at'])); ?></span>
                            </div>
                            <p class="text-gray-400 text-sm leading-relaxed mb-10"><?php echo $m['message']; ?></p>
                            <div class="flex justify-between items-center">
                                <a href="mailto:<?php echo $m['email']; ?>" class="text-[#bcff00] font-bold text-[10px] uppercase tracking-widest">Reply via Email</a>
                                <form action="actions/admin_action.php" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="message_id" value="<?php echo $m['id']; ?>">
                                    <button type="submit" class="text-red-500/40 hover:text-red-500 transition font-bold text-[10px] uppercase tracking-widest">Archive</button>
                                </form>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                        echo "<p class='col-span-full text-center py-40 text-gray-600 italic'>No incoming messages at the moment.</p>";
                    endif;
                    ?>
                </div>
            </div>

        <?php elseif($section === 'community'): ?>
            <!-- Community Oversight -->
            <div class="space-y-8" data-aos="fade-up">
                <div class="flex justify-between items-center">
                    <h2 class="text-3xl font-bold">User <span class="neon-text">Registry</span></h2>
                    <form action="" method="GET" class="flex space-x-4">
                        <input type="hidden" name="section" value="community">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search users..." class="bg-white/5 border border-white/10 rounded-xl px-6 py-2 text-sm focus:outline-none focus:border-[#bcff00]">
                        <button type="submit" class="bg-[#bcff00] text-black px-4 py-2 rounded-xl font-bold text-xs">Search</button>
                    </form>
                </div>
                <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold border-b border-white/5">
                            <tr>
                                <th class="p-6">User Identity</th>
                                <th class="p-6">Role</th>
                                <th class="p-6 text-right">Moderation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php
                            $all_u = $conn->query("SELECT * FROM users $user_search_sql ORDER BY created_at DESC");
                            if($all_u->num_rows > 0):
                                while($u = $all_u->fetch_assoc()):
                            ?>
                                <tr class="hover:bg-white/[0.02] transition">
                                    <td class="p-6 flex items-center space-x-4">
                                        <div class="w-10 h-10 rounded-full bg-[#bcff00]/10 flex items-center justify-center font-bold text-[#bcff00]">
                                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold"><?php echo $u['name']; ?></p>
                                            <p class="text-[10px] text-gray-500"><?php echo $u['email']; ?></p>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <span class="text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full <?php echo $u['role'] == 'admin' ? 'bg-purple-500/10 text-purple-500' : 'bg-gray-500/10 text-gray-400'; ?>">
                                            <?php echo $u['role']; ?>
                                        </span>
                                    </td>
                                    <td class="p-6 text-right">
                                        <?php if($u['role'] !== 'admin'): ?>
                                            <div class="flex items-center justify-end space-x-4">
                                                <span class="text-[9px] px-2 py-0.5 rounded-full border <?php echo $u['status'] == 'banned' ? 'border-red-500/50 text-red-500 bg-red-500/5' : 'border-[#bcff00]/50 text-[#bcff00] bg-[#bcff00]/5'; ?>">
                                                    <?php echo ucfirst($u['status']); ?>
                                                </span>
                                                <form action="actions/admin_action.php" method="POST">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="ban_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="text-white/40 hover:text-white transition font-bold text-[10px] uppercase">
                                                        <?php echo $u['status'] == 'banned' ? 'Restore' : 'Ban'; ?>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                                echo "<tr><td colspan='3' class='p-20 text-center text-gray-500 italic'>No users found matching your search.</td></tr>";
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif($section === 'messages'): ?>
            <!-- Inbox & Subscribers -->
            <div class="space-y-12" data-aos="fade-up">
                <!-- Direct Messages -->
                <div>
                    <h2 class="text-3xl font-bold mb-6">Direct <span class="neon-text">Inquiries</span></h2>
                    <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold border-b border-white/5">
                                <tr>
                                    <th class="p-6">Sender Details</th>
                                    <th class="p-6">Message</th>
                                    <th class="p-6 text-right">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php
                                $messages = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
                                if($messages->num_rows > 0):
                                    while($msg = $messages->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-white/[0.02] transition">
                                        <td class="p-6">
                                            <p class="font-bold"><?php echo htmlspecialchars($msg['name']); ?></p>
                                            <p class="text-[10px] text-[#bcff00]"><?php echo htmlspecialchars($msg['email']); ?></p>
                                        </td>
                                        <td class="p-6">
                                            <p class="text-gray-400 text-sm italic">"<?php echo nl2br(htmlspecialchars($msg['message'])); ?>"</p>
                                        </td>
                                        <td class="p-6 text-right">
                                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest"><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></p>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                    echo "<tr><td colspan='3' class='p-20 text-center text-gray-500 italic'>No new messages.</td></tr>";
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Newsletter Subscribers -->
                <div>
                    <h2 class="text-3xl font-bold mb-6">Newsletter <span class="neon-text">Subscribers</span></h2>
                    <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold border-b border-white/5">
                                <tr>
                                    <th class="p-6">Email Address</th>
                                    <th class="p-6 text-right">Subscribed On</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php
                                $subscribers = $conn->query("SELECT * FROM subscribers ORDER BY created_at DESC");
                                if($subscribers->num_rows > 0):
                                    while($sub = $subscribers->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-white/[0.02] transition">
                                        <td class="p-6">
                                            <p class="font-bold text-[#bcff00]"><?php echo htmlspecialchars($sub['email']); ?></p>
                                        </td>
                                        <td class="p-6 text-right">
                                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest"><?php echo date('M d, Y', strtotime($sub['created_at'])); ?></p>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                    echo "<tr><td colspan='2' class='p-20 text-center text-gray-500 italic'>No subscribers yet.</td></tr>";
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif($section === 'reports'): ?>
            <?php $report_summary = $financial_report['summary']; ?>
            <div class="space-y-8" data-aos="fade-up">
                <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-6">
                    <div>
                        <h2 class="text-3xl font-bold">Profit & Loss <span class="neon-text">Summary</span></h2>
                        <p class="text-sm text-gray-500 mt-2">Revenue, losses, order performance, and seller activity.</p>
                    </div>
                    <div class="flex flex-col md:flex-row gap-3">
                        <form method="GET" class="flex flex-col sm:flex-row gap-3">
                            <input type="hidden" name="section" value="reports">
                            <input type="date" name="from" value="<?php echo htmlspecialchars($financial_report['from']); ?>" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#bcff00]">
                            <input type="date" name="to" value="<?php echo htmlspecialchars($financial_report['to']); ?>" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#bcff00]">
                            <button type="submit" class="bg-white/10 border border-white/10 px-5 py-2 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-white/15 transition">Apply</button>
                        </form>
                        <a href="admin_report_pdf.php?from=<?php echo urlencode($financial_report['from']); ?>&to=<?php echo urlencode($financial_report['to']); ?>" class="bg-[#bcff00] text-black px-5 py-2 rounded-xl font-bold text-xs uppercase tracking-widest flex items-center justify-center">Download PDF</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div class="bg-[#bcff00]/5 border border-[#bcff00]/20 p-7 rounded-3xl">
                        <p class="text-[#bcff00] uppercase tracking-widest text-[10px] font-bold mb-2">Completed Revenue</p>
                        <p class="text-3xl font-bold">&#8377;<?php echo number_format($report_summary['revenue'], 2); ?></p>
                        <p class="text-[10px] text-gray-500 mt-2"><?php echo number_format($report_summary['completed_orders']); ?> completed orders</p>
                    </div>
                    <div class="bg-white/5 border border-white/10 p-7 rounded-3xl">
                        <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Gross Sales</p>
                        <p class="text-3xl font-bold">&#8377;<?php echo number_format($report_summary['gross_sales'], 2); ?></p>
                        <p class="text-[10px] text-gray-500 mt-2">Discounts: &#8377;<?php echo number_format($report_summary['discounts'], 2); ?></p>
                    </div>
                    <div class="bg-red-500/5 border border-red-500/20 p-7 rounded-3xl">
                        <p class="text-red-400 uppercase tracking-widest text-[10px] font-bold mb-2">Loss Value</p>
                        <p class="text-3xl font-bold text-red-400">&#8377;<?php echo number_format($report_summary['losses'], 2); ?></p>
                        <p class="text-[10px] text-gray-500 mt-2"><?php echo number_format($report_summary['loss_orders']); ?> cancelled/refunded orders</p>
                    </div>
                    <div class="<?php echo $report_summary['net_result'] >= 0 ? 'bg-blue-500/5 border-blue-500/20' : 'bg-red-500/5 border-red-500/20'; ?> border p-7 rounded-3xl">
                        <p class="text-blue-400 uppercase tracking-widest text-[10px] font-bold mb-2">Estimated Profit / Loss</p>
                        <p class="text-3xl font-bold">&#8377;<?php echo number_format($report_summary['net_result'], 2); ?></p>
                        <p class="text-[10px] text-gray-500 mt-2">Average completed order: &#8377;<?php echo number_format($report_summary['average_order'], 2); ?></p>
                    </div>
                </div>

                <div class="bg-white/5 border border-white/10 p-6 rounded-3xl">
                    <p class="text-xs text-gray-400 leading-relaxed"><span class="font-bold text-white">Report definitions:</span> Revenue is completed order amount minus discounts. Loss value includes cancelled orders and orders with resolved refunded disputes. Estimated profit/loss is revenue minus loss value. Product costs, seller payouts, taxes, and operating expenses are not stored in the current database, so this is an operational estimate rather than accounting profit.</p>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                        <div class="p-7 border-b border-white/5"><h3 class="text-xl font-bold">Order Status Breakdown</h3></div>
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500">
                                <tr><th class="p-5">Status</th><th class="p-5">Orders</th><th class="p-5 text-right">Value</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach($financial_report['statuses'] as $status_row): ?>
                                <tr>
                                    <td class="p-5 font-bold uppercase text-xs"><?php echo htmlspecialchars($status_row['status']); ?></td>
                                    <td class="p-5 text-gray-400"><?php echo number_format($status_row['order_count']); ?></td>
                                    <td class="p-5 text-right font-bold">&#8377;<?php echo number_format($status_row['total_value'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                        <div class="p-7 border-b border-white/5"><h3 class="text-xl font-bold">Top Sellers</h3></div>
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500">
                                <tr><th class="p-5">Seller</th><th class="p-5">Completed</th><th class="p-5 text-right">Revenue</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if($financial_report['sellers']): foreach($financial_report['sellers'] as $seller_row): ?>
                                <tr>
                                    <td class="p-5 font-bold"><?php echo htmlspecialchars($seller_row['seller_name']); ?></td>
                                    <td class="p-5 text-gray-400"><?php echo number_format($seller_row['completed_orders']); ?></td>
                                    <td class="p-5 text-right font-bold text-[#bcff00]">&#8377;<?php echo number_format($seller_row['revenue'], 2); ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="3" class="p-12 text-center text-gray-500 italic">No completed sales in this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                    <div class="p-7 border-b border-white/5 flex justify-between items-center">
                        <h3 class="text-xl font-bold">Transaction Details</h3>
                        <p class="text-[10px] text-gray-500 uppercase tracking-widest"><?php echo number_format($report_summary['total_orders']); ?> orders</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500">
                                <tr><th class="p-5">Order</th><th class="p-5">Date</th><th class="p-5">Product</th><th class="p-5">Buyer / Seller</th><th class="p-5">Status</th><th class="p-5 text-right">Net Amount</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if($financial_report['transactions']): foreach($financial_report['transactions'] as $transaction): ?>
                                <tr class="hover:bg-white/[0.02] transition">
                                    <td class="p-5 font-bold text-sm">#REV-<?php echo $transaction['id']; ?></td>
                                    <td class="p-5 text-xs text-gray-500"><?php echo date('d M Y', strtotime($transaction['created_at'])); ?></td>
                                    <td class="p-5 font-bold text-sm"><?php echo htmlspecialchars($transaction['product_title']); ?></td>
                                    <td class="p-5"><p class="text-sm"><?php echo htmlspecialchars($transaction['buyer_name']); ?></p><p class="text-[10px] text-gray-500">Seller: <?php echo htmlspecialchars($transaction['seller_name']); ?></p></td>
                                    <td class="p-5"><span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-widest <?php echo $transaction['is_loss'] ? 'bg-red-500/10 text-red-400' : 'bg-white/5 text-gray-400'; ?>"><?php echo htmlspecialchars($transaction['status']); ?><?php echo $transaction['is_loss'] ? ' / loss' : ''; ?></span></td>
                                    <td class="p-5 text-right font-bold">&#8377;<?php echo number_format($transaction['amount'] - $transaction['discount_applied'], 2); ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="6" class="p-20 text-center text-gray-500 italic">No orders found for this date range.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif($section === 'disputes'): ?>
            <!-- Disputes Management -->
            <div class="space-y-8" data-aos="fade-up">
                <h2 class="text-3xl font-bold">Dispute <span class="neon-text">Queue</span></h2>
                <div class="glass-nav rounded-[2.5rem] border border-white/10 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold border-b border-white/5">
                            <tr>
                                <th class="p-6">Order ID</th>
                                <th class="p-6">Buyer Details</th>
                                <th class="p-6">Reason & Description</th>
                                <th class="p-6">Date</th>
                                <th class="p-6">Status</th>
                                <th class="p-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php
                            $disputes = $conn->query("SELECT d.*, u.name as buyer_name, u.email as buyer_email FROM disputes d JOIN users u ON d.buyer_id = u.id ORDER BY d.created_at DESC");
                            if($disputes->num_rows > 0):
                                while($d = $disputes->fetch_assoc()):
                            ?>
                                    <tr class="hover:bg-white/[0.02] transition">
                                        <td class="p-6 font-bold text-sm">#REV-<?php echo $d['order_id']; ?></td>
                                        <td class="p-6">
                                            <p class="font-bold text-sm"><?php echo $d['buyer_name']; ?></p>
                                            <p class="text-[10px] text-gray-500"><?php echo $d['buyer_email']; ?></p>
                                        </td>
                                        <td class="p-6 max-w-xs">
                                            <p class="font-bold text-sm text-[#bcff00]"><?php echo htmlspecialchars($d['reason']); ?></p>
                                            <p class="text-xs text-gray-400 mt-1 italic leading-relaxed">"<?php echo htmlspecialchars($d['details']); ?>"</p>
                                        </td>
                                        <td class="p-6 text-xs text-gray-500"><?php echo date('d M Y', strtotime($d['created_at'])); ?></td>
                                        <td class="p-6">
                                            <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-widest 
                                                <?php echo match($d['status']) {
                                                    'open' => 'bg-red-500/10 text-red-500 border border-red-500/20',
                                                    'under_review' => 'bg-yellow-500/10 text-yellow-500 border border-yellow-500/20',
                                                    'resolved_refunded' => 'bg-[#bcff00]/10 text-[#bcff00] border border-[#bcff00]/20',
                                                    'rejected' => 'bg-white/5 text-gray-400 border border-white/10'
                                                }; ?>">
                                                <?php echo str_replace('_', ' ', $d['status']); ?>
                                            </span>
                                        </td>
                                        <td class="p-6 text-right">
                                            <form action="actions/dispute_action.php" method="POST" class="inline-block">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="update_dispute">
                                                <input type="hidden" name="dispute_id" value="<?php echo $d['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="bg-[#111] text-white border border-white/10 rounded-lg px-2 py-1 text-[10px] uppercase font-bold focus:outline-none focus:border-[#bcff00]">
                                                    <option value="open" class="bg-[#111] text-white" <?php echo $d['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                                    <option value="under_review" class="bg-[#111] text-white" <?php echo $d['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                                    <option value="resolved_refunded" class="bg-[#111] text-white" <?php echo $d['status'] === 'resolved_refunded' ? 'selected' : ''; ?>>Resolved (Refunded)</option>
                                                    <option value="rejected" class="bg-[#111] text-white" <?php echo $d['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                            <?php 
                                endwhile;
                            else:
                                echo "<tr><td colspan='6' class='p-20 text-center text-gray-500 italic'>No active disputes in the queue.</td></tr>";
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>


<?php if($section === 'dashboard'): ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    fetch('actions/admin_data_action.php?action=get_sales_chart')
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: data.values,
                        borderColor: '#bcff00',
                        backgroundColor: 'rgba(188, 255, 0, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#bcff00',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#666', font: { size: 10 } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: '#666', font: { size: 10 } }
                        }
                    }
                }
            });
        });

    // User Chart
    fetch('actions/admin_data_action.php?action=get_user_growth')
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('userChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'New Users',
                        data: data.values,
                        backgroundColor: '#bcff00',
                        borderRadius: 10,
                        barThickness: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#666', font: { size: 10 } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: '#666', font: { size: 10 } }
                        }
                    }
                }
            });
        });
});
</script>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const swiper = new Swiper(".mySwiper", {
            autoplay: { delay: 5000, disableOnInteraction: false },
            pagination: { el: ".swiper-pagination", clickable: true },
            loop: true
        });
    });
</script>
<?php endif; ?>

<!-- Toast System -->
<div id="admin-toast" class="toast"></div>

<script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('admin-toast');
        toast.textContent = message;
        toast.className = `toast show ${type}`;
        setTimeout(() => {
            toast.className = 'toast';
        }, 5000);
    }

    <?php if(isset($_GET['success'])): ?>
        showToast("<?php echo htmlspecialchars($_GET['success']); ?>".replace(/_/g, ' ').toUpperCase(), 'success');
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        showToast("<?php echo htmlspecialchars($_GET['error']); ?>".replace(/_/g, ' ').toUpperCase(), 'error');
    <?php endif; ?>
</script>

<!-- Admin Shipping Tracker Modal -->
<div id="admin-shipping-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeAdminShippingModal()"></div>
    <div class="bg-[#0a0a0a] border border-white/10 p-8 rounded-[2.5rem] w-full max-w-md relative z-10 shadow-2xl" data-aos="zoom-in">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold">Shipping <span class="neon-text">Details</span></h3>
            <button onclick="closeAdminShippingModal()" class="text-gray-500 hover:text-white text-2xl">&times;</button>
        </div>
        <form action="actions/admin_action.php" method="POST" class="space-y-6">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_shipping">
            <input type="hidden" name="order_id" id="admin-shipping-order-id" value="">
            
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Carrier</label>
                <select name="carrier" id="admin-shipping-carrier" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white">
                    <option value="FedEx" class="bg-black text-white">FedEx</option>
                    <option value="Blue Dart" class="bg-black text-white">Blue Dart</option>
                    <option value="DHL" class="bg-black text-white">DHL</option>
                    <option value="Delhivery" class="bg-black text-white">Delhivery</option>
                    <option value="India Post" class="bg-black text-white">India Post</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Tracking Number</label>
                <input type="text" name="tracking_number" id="admin-shipping-tracking-number" required placeholder="e.g. 1234567890" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white text-sm">
            </div>
            
            <button type="submit" class="neon-btn w-full font-bold py-4 rounded-xl mt-4">Save Shipping Details</button>
        </form>
    </div>
</div>

<script>
function openAdminShippingModal(orderId, carrier, trackingNumber) {
    document.getElementById('admin-shipping-order-id').value = orderId;
    if (carrier) {
        document.getElementById('admin-shipping-carrier').value = carrier;
    }
    if (trackingNumber) {
        document.getElementById('admin-shipping-tracking-number').value = trackingNumber;
    } else {
        document.getElementById('admin-shipping-tracking-number').value = '';
    }
    document.getElementById('admin-shipping-modal').classList.remove('hidden');
}

function closeAdminShippingModal() {
    document.getElementById('admin-shipping-modal').classList.add('hidden');
}
</script>

<!-- ===== ADMIN CONFIRM MODAL ===== -->
<div id="admin-confirm-modal" class="hidden fixed inset-0 z-[300] bg-black/80 backdrop-blur-sm flex items-center justify-center px-4">
    <div class="bg-[#0f0f0f] border border-white/10 rounded-3xl p-8 w-full max-w-md shadow-2xl">
        <div class="w-16 h-16 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 id="admin-modal-title" class="text-2xl font-bold text-center mb-2"></h3>
        <p id="admin-modal-item" class="text-white font-bold text-center mb-3 truncate px-4"></p>
        <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-4 mb-8 text-center">
            <p id="admin-modal-desc" class="text-red-400 text-xs font-semibold"></p>
        </div>
        <div class="flex gap-4">
            <button onclick="closeAdminConfirmModal()" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold py-4 rounded-2xl transition">Cancel</button>
            <button onclick="submitAdminAction()" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-red-500/20">Confirm</button>
        </div>
        <!-- Hidden form -->
        <form id="admin-action-form" action="actions/admin_action.php" method="POST" class="hidden">
            <?php echo csrf_field(); ?>
            <input type="hidden" id="admin-action-type" name="action">
            <input type="hidden" id="admin-action-product-id" name="product_id">
            <input type="hidden" id="admin-action-category-id" name="category_id">
        </form>
    </div>
</div>

<script>
function openAdminConfirmModal(action, id, itemName, title, desc) {
    document.getElementById('admin-modal-title').innerText = title;
    document.getElementById('admin-modal-item').innerText = '"' + itemName + '"';
    document.getElementById('admin-modal-desc').innerText = '⚠️ ' + desc + ' This cannot be undone.';
    document.getElementById('admin-action-type').value = action;
    // Set appropriate ID field
    if (action === 'delete_category') {
        document.getElementById('admin-action-category-id').value = id;
        document.getElementById('admin-action-product-id').value = '';
    } else {
        document.getElementById('admin-action-product-id').value = id;
        document.getElementById('admin-action-category-id').value = '';
    }
    document.getElementById('admin-confirm-modal').classList.remove('hidden');
}
function closeAdminConfirmModal() {
    document.getElementById('admin-confirm-modal').classList.add('hidden');
}
function submitAdminAction() {
    document.getElementById('admin-action-form').submit();
}
</script>

<?php include 'includes/footer.php'; ?>
