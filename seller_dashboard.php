<?php 
require_once 'config/db.php';

// Auth check BEFORE any HTML output
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Mark all orders as viewed when visiting dashboard
$conn->query("UPDATE orders o JOIN products p ON o.product_id = p.id SET o.viewed = 1 WHERE p.seller_id = $user_id AND o.viewed = 0");

// Fetch seller stats
$total_listings = $conn->query("SELECT COUNT(*) as count FROM products WHERE seller_id = $user_id")->fetch_assoc()['count'];
$total_sold = $conn->query("SELECT COUNT(*) as count FROM products WHERE seller_id = $user_id AND status = 'sold'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(o.amount - o.discount_applied) as total FROM orders o JOIN products p ON o.product_id = p.id WHERE p.seller_id = $user_id AND o.status NOT IN ('cancelled', 'pending')")->fetch_assoc()['total'] ?? 0;
$monthly_revenue = $conn->query("SELECT SUM(o.amount - o.discount_applied) as total FROM orders o JOIN products p ON o.product_id = p.id WHERE p.seller_id = $user_id AND o.status NOT IN ('cancelled', 'pending') AND MONTH(o.created_at) = MONTH(CURRENT_DATE()) AND YEAR(o.created_at) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'] ?? 0;
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders o JOIN products p ON o.product_id = p.id WHERE p.seller_id = $user_id AND o.status = 'pending'")->fetch_assoc()['count'];
$delivered_orders = $conn->query("SELECT COUNT(*) as count FROM orders o JOIN products p ON o.product_id = p.id WHERE p.seller_id = $user_id AND o.status IN ('shipped', 'delivered', 'completed')")->fetch_assoc()['count'];

// Calculate new order notifications for the alert
$notif_count = $conn->query("SELECT COUNT(*) as count FROM orders o JOIN products p ON o.product_id = p.id WHERE p.seller_id = $user_id AND o.viewed = 0")->fetch_assoc()['count'];
?>

<section class="py-20 px-6 max-w-7xl mx-auto">
    <!-- New Order Notification Alert -->
    <?php if($notif_count > 0): ?>
    <div class="mb-12 bg-[#bcff00] text-black p-6 rounded-[2rem] flex items-center justify-between shadow-2xl shadow-[#bcff00]/20" data-aos="zoom-in">
        <div class="flex items-center space-x-6">
            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold italic">Cha-Ching! You have new sales!</h3>
                <p class="text-black/70 font-medium">Someone just bought your items. Check the "Recent Sales" section below.</p>
            </div>
        </div>
        <button onclick="document.getElementById('sales-section').scrollIntoView({behavior:'smooth'})" class="bg-black text-white px-8 py-3 rounded-full font-bold text-sm hover:scale-105 transition">View All Sales</button>
    </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-16 gap-8">
        <div data-aos="fade-right">
            <h1 class="text-5xl font-bold tracking-tight mb-2">Seller <span class="neon-text">Dashboard</span></h1>
            <p class="text-gray-400">Welcome back, <?php echo $user_name; ?>. Here is how your shop is performing.</p>
        </div>
        <a href="add_product.php" class="neon-btn font-bold px-8 py-4 rounded-2xl flex items-center space-x-2" data-aos="fade-left">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span>List New Item</span>
        </a>
    </div>

    <!-- Advanced Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 mb-20" data-aos="fade-up">
        <div class="bg-white/5 border border-white/10 p-6 rounded-3xl">
            <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Listings</p>
            <p class="text-3xl font-bold"><?php echo $total_listings; ?></p>
        </div>
        <div class="bg-white/5 border border-white/10 p-6 rounded-3xl">
            <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Items Sold</p>
            <p class="text-3xl font-bold text-purple-500"><?php echo $total_sold; ?></p>
        </div>
        <div class="bg-white/5 border border-white/10 p-6 rounded-3xl">
            <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Pending</p>
            <p class="text-3xl font-bold text-orange-500"><?php echo $pending_orders; ?></p>
        </div>
        <div class="bg-white/5 border border-white/10 p-6 rounded-3xl">
            <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Delivered</p>
            <p class="text-3xl font-bold text-green-500"><?php echo $delivered_orders; ?></p>
        </div>
        <div class="bg-white/5 border border-white/10 p-6 rounded-3xl">
            <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-2">Monthly Rev</p>
            <p class="text-3xl font-bold text-[#bcff00]">₹<?php echo number_format($monthly_revenue, 0); ?></p>
        </div>
        <div class="bg-[#bcff00]/10 border border-[#bcff00]/20 p-6 rounded-3xl">
            <p class="text-[#bcff00]/70 uppercase tracking-widest text-[10px] font-bold mb-2">Total Rev</p>
            <p class="text-3xl font-bold text-[#bcff00]">₹<?php echo number_format($total_revenue, 0); ?></p>
        </div>
    </div>

    <!-- Customer Interaction Widget -->
    <div data-aos="fade-up" class="mb-20">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold">Recent Messages</h2>
            <a href="chat.php" class="text-[#bcff00] hover:underline font-bold text-sm">View All Chats &rarr;</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $recent_msgs = $conn->query("
                SELECT c.*, u.name as sender_name 
                FROM chats c 
                JOIN users u ON c.sender_id = u.id 
                WHERE c.receiver_id = $user_id 
                AND c.id IN (SELECT MAX(id) FROM chats WHERE receiver_id = $user_id GROUP BY sender_id) 
                ORDER BY c.created_at DESC LIMIT 3
            ");
            if($recent_msgs->num_rows > 0):
                while($msg = $recent_msgs->fetch_assoc()):
            ?>
                <div class="bg-white/5 border border-white/10 p-6 rounded-3xl hover:bg-white/10 transition flex flex-col">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="w-10 h-10 rounded-full bg-[#bcff00] text-black flex items-center justify-center font-bold">
                            <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="font-bold text-white"><?php echo htmlspecialchars($msg['sender_name']); ?></p>
                            <p class="text-[10px] text-gray-500"><?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?></p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm line-clamp-2 mb-4 flex-1">
                        <?php echo $msg['message'] ? htmlspecialchars($msg['message']) : '📷 Image sent'; ?>
                    </p>
                    <a href="chat.php?user_id=<?php echo $msg['sender_id']; ?>" class="w-full bg-[#bcff00]/10 border border-[#bcff00]/20 text-[#bcff00] hover:bg-[#bcff00]/20 py-2 rounded-xl text-center text-xs font-bold transition">Reply</a>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-span-full py-12 text-center border border-dashed border-white/10 rounded-3xl">
                    <p class="text-gray-500 italic">No recent messages.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Listings -->
    <div data-aos="fade-up">
        <h2 class="text-3xl font-bold mb-8">My Listings</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            <?php
            $listings = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.seller_id = $user_id ORDER BY p.created_at DESC");
            if($listings->num_rows > 0):
                while($item = $listings->fetch_assoc()):
            ?>
                <div class="bg-white/5 border border-white/10 rounded-3xl overflow-hidden flex flex-col">
                    <div class="aspect-square relative bg-white/5">
                        <img src="assets/img/products/<?php echo $item['images']; ?>" 
                             onerror="this.style.display='none'"
                             class="w-full h-full object-cover">
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest <?php echo $item['status'] == 'available' ? 'bg-[#bcff00]/20 text-[#bcff00] border border-[#bcff00]/20' : 'bg-white/10 text-gray-400 border border-white/10'; ?>">
                                <?php echo $item['status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-xl truncate pr-4"><?php echo $item['title']; ?></h3>
                            <p class="font-bold text-xl">₹<?php echo number_format($item['price'], 0); ?></p>
                        </div>
                        <p class="text-gray-500 text-sm mb-6 line-clamp-1"><?php echo $item['description']; ?></p>
                        <div class="mt-auto flex space-x-3">
                            <?php if ($item['status'] === 'available' || $item['status'] === 'pending'): ?>
                            <button onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>', <?php echo $item['price']; ?>, '<?php echo addslashes($item['description']); ?>')"
                                class="flex-1 bg-white/5 hover:bg-[#bcff00]/10 hover:text-[#bcff00] border border-white/10 hover:border-[#bcff00]/30 py-3 rounded-xl text-center text-sm font-bold transition">Edit</button>
                            <?php else: ?>
                            <span class="flex-1 bg-white/[0.02] border border-white/5 py-3 rounded-xl text-center text-sm font-bold text-gray-600 cursor-not-allowed">Sold</span>
                            <?php endif; ?>
                            <form action="actions/seller_action.php" method="POST" class="flex-1" onsubmit="event.preventDefault(); showCustomConfirm('Remove Listing', 'Are you sure you want to remove this listing forever?', 'danger', () => this.submit());">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-500 py-3 rounded-xl text-center text-sm font-bold transition">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
                echo "<div class='col-span-full py-20 text-center text-gray-500 border border-dashed border-white/10 rounded-[2rem]'>
                        <p class='mb-4'>You haven't listed any items yet.</p>
                        <a href='add_product.php' class='text-[#bcff00] font-bold hover:underline'>Start Selling &rarr;</a>
                      </div>";
            endif; 
            ?>
        </div>
    </div>
    <!-- Return Requests -->
    <?php
    $return_requests = $conn->query("SELECT o.*, p.title, u.name as buyer_name, p.images FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.buyer_id = u.id WHERE p.seller_id = $user_id AND o.return_status = 'requested' ORDER BY o.created_at DESC");
    if($return_requests->num_rows > 0):
    ?>
    <div data-aos="fade-up" class="mb-20">
        <h2 class="text-3xl font-bold mb-8 flex items-center">
            Return Requests 
            <span class="ml-4 bg-orange-500/10 text-orange-500 text-xs px-3 py-1 rounded-full border border-orange-500/20">Action Required</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php while($return = $return_requests->fetch_assoc()): ?>
                <div class="bg-white/5 border border-orange-500/30 p-6 rounded-3xl flex flex-col md:flex-row gap-6 items-start">
                    <div class="w-24 h-24 rounded-2xl overflow-hidden bg-black flex-shrink-0 border border-white/10">
                        <img src="assets/img/products/<?php echo $return['images']; ?>" class="w-full h-full object-cover" onerror="this.style.display='none'">
                    </div>
                    <div class="flex-1 w-full">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-lg text-white"><?php echo htmlspecialchars($return['title']); ?></h3>
                            <span class="text-orange-500 font-bold">₹<?php echo number_format($return['amount'], 0); ?></span>
                        </div>
                        <p class="text-xs text-gray-400 mb-2">Buyer: <span class="text-white font-bold"><?php echo htmlspecialchars($return['buyer_name']); ?></span></p>
                        <div class="bg-black/50 p-4 rounded-xl border border-white/5 mb-4">
                            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold mb-1">Reason for return:</p>
                            <p class="text-sm text-gray-300 italic">"<?php echo htmlspecialchars($return['return_reason']); ?>"</p>
                        </div>
                        <div class="flex gap-3">
                            <form action="actions/seller_action.php" method="POST" class="flex-1" onsubmit="event.preventDefault(); showCustomConfirm('Approve Return', 'Approve this return request? The order will be cancelled and refund initiated.', 'success', () => this.submit());">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="approve_return">
                                <input type="hidden" name="order_id" value="<?php echo $return['id']; ?>">
                                <button type="submit" class="w-full bg-[#bcff00]/10 hover:bg-[#bcff00]/20 text-[#bcff00] font-bold py-2 rounded-xl transition text-xs uppercase tracking-widest border border-[#bcff00]/20">Approve</button>
                            </form>
                            <form action="actions/seller_action.php" method="POST" class="flex-1" onsubmit="event.preventDefault(); showCustomConfirm('Reject Return', 'Reject this return request? The order will remain completed.', 'danger', () => this.submit());">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="reject_return">
                                <input type="hidden" name="order_id" value="<?php echo $return['id']; ?>">
                                <button type="submit" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-500 font-bold py-2 rounded-xl transition text-xs uppercase tracking-widest border border-red-500/20">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Sales -->
    <div data-aos="fade-up" class="mt-20" id="sales-section">
        <h2 class="text-3xl font-bold mb-8 flex items-center">
            Recent Sales 
            <span class="ml-4 bg-[#bcff00]/10 text-[#bcff00] text-xs px-3 py-1 rounded-full border border-[#bcff00]/20">New Orders</span>
        </h2>
        <div class="glass-nav rounded-3xl border border-white/10 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500 font-bold">
                    <tr>
                        <th class="p-6">Product</th>
                        <th class="p-6">Buyer Details</th>
                        <th class="p-6 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php
                    $sales = $conn->query("SELECT o.*, p.title, u.name as buyer_name, o.phone, o.shipping_address 
                                         FROM orders o 
                                         JOIN products p ON o.product_id = p.id 
                                         JOIN users u ON o.buyer_id = u.id 
                                         WHERE p.seller_id = $user_id 
                                         ORDER BY o.created_at DESC");
                    if($sales->num_rows > 0):
                        while($sale = $sales->fetch_assoc()):
                    ?>
                        <tr class="hover:bg-white/[0.02] transition">
                            <td class="p-6">
                                <p class="font-bold text-white"><?php echo $sale['title']; ?></p>
                                <p class="text-xs text-gray-500">Order ID: #<?php echo $sale['id']; ?></p>
                            </td>
                            <td class="p-6">
                                <p class="font-bold text-white"><?php echo htmlspecialchars($sale['buyer_name']); ?></p>
                                <?php 
                                if ($sale['status'] === 'pending' || $sale['status'] === 'cancelled') {
                                    $phone = $sale['phone'];
                                    $masked_phone = strlen($phone) >= 10 ? substr($phone, 0, 3) . '****' . substr($phone, -3) : '**********';
                                    $address = $sale['shipping_address'];
                                    $parts = explode(',', $address);
                                    $city_state = array_slice($parts, -2); // Get last two parts (usually City, State)
                                    $masked_address = "Hidden Address, " . implode(',', $city_state) . " (Full address unlocked after shipping)";
                                    
                                    echo '<p class="text-xs text-gray-500">' . htmlspecialchars($masked_phone) . '</p>';
                                    echo '<p class="text-xs text-gray-400 mt-1 italic">' . htmlspecialchars($masked_address) . '</p>';
                                } else {
                                    echo '<p class="text-xs text-gray-500">' . htmlspecialchars($sale['phone']) . '</p>';
                                    echo '<p class="text-xs text-gray-400 mt-1 italic">' . htmlspecialchars($sale['shipping_address']) . '</p>';
                                }
                                ?>
                            </td>
                            <td class="p-6 text-right">
                                <p class="font-bold text-[#bcff00] mb-3">₹<?php echo number_format($sale['amount'] - $sale['discount_applied'], 0); ?></p>
                                <?php if ($sale['status'] === 'pending'): ?>
                                    <select onchange="handleSellerAction(this, <?php echo $sale['id']; ?>)" class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 w-full text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-[#bcff00] transition text-center cursor-pointer appearance-none">
                                        <option value="" disabled selected>Select Action ▼</option>
                                        <option value="shipped" class="bg-black text-[#bcff00]">Mark as Shipped</option>
                                        <option value="cancel" class="bg-black text-red-500">Cancel Order</option>
                                    </select>
                                <?php else: ?>
                                    <div class="bg-white/5 border border-white/10 rounded-lg px-3 py-1 text-[10px] font-bold uppercase tracking-widest inline-block mb-2 text-center w-full">
                                        <?php echo htmlspecialchars($sale['status']); ?>
                                    </div>
                                    <?php if ($sale['tracking_number']): ?>
                                        <div class="mt-2 text-[10px] text-gray-500 font-medium">
                                            <span class="text-white font-bold"><?php echo htmlspecialchars($sale['carrier']); ?>:</span>
                                            <?php echo htmlspecialchars($sale['tracking_number']); ?>
                                        </div>
                                        <button type="button" onclick="openShippingModal(<?php echo $sale['id']; ?>, '<?php echo htmlspecialchars($sale['carrier'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($sale['tracking_number'], ENT_QUOTES); ?>')" class="text-[9px] text-[#bcff00] uppercase font-bold tracking-widest hover:underline mt-1 inline-block">Edit Tracker</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                        echo "<tr><td colspan='3' class='p-12 text-center text-gray-500 italic'>No sales yet. Keep sharing your items!</td></tr>";
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Shipping Modal -->
<div id="shipping-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeShippingModal()"></div>
    <div class="bg-[#0a0a0a] border border-white/10 p-8 rounded-[2.5rem] w-full max-w-md relative z-10 shadow-2xl" data-aos="zoom-in">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold">Shipping <span class="neon-text">Tracker</span></h3>
            <button onclick="closeShippingModal()" class="text-gray-500 hover:text-white text-2xl">&times;</button>
        </div>
        <form action="actions/seller_action.php" method="POST" class="space-y-6">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_shipping">
            <input type="hidden" name="order_id" id="shipping-order-id" value="">
            
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Carrier</label>
                <select name="carrier" id="shipping-carrier" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white">
                    <option value="FedEx" class="bg-black text-white">FedEx</option>
                    <option value="Blue Dart" class="bg-black text-white">Blue Dart</option>
                    <option value="DHL" class="bg-black text-white">DHL</option>
                    <option value="Delhivery" class="bg-black text-white">Delhivery</option>
                    <option value="India Post" class="bg-black text-white">India Post</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Tracking Number</label>
                <input type="text" name="tracking_number" id="shipping-tracking-number" required placeholder="e.g. 1234567890" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white text-sm">
            </div>
            
            <button type="submit" class="neon-btn w-full font-bold py-4 rounded-xl mt-4">Save Shipping Details</button>
        </form>
    </div>
</div>

<script>
function openShippingModal(orderId, carrier, trackingNumber) {
    document.getElementById('shipping-order-id').value = orderId;
    if (carrier) {
        document.getElementById('shipping-carrier').value = carrier;
    }
    if (trackingNumber) {
        document.getElementById('shipping-tracking-number').value = trackingNumber;
    } else {
        document.getElementById('shipping-tracking-number').value = '';
    }
    document.getElementById('shipping-modal').classList.remove('hidden');
}

function closeShippingModal() {
    document.getElementById('shipping-modal').classList.add('hidden');
}

function handleStatusChange(select, orderId, currentStatus) {
    if (select.value === 'shipped') {
        select.value = currentStatus;
        openShippingModal(orderId, '', '');
    } else {
        select.form.submit();
    }
}

function sellerCancelOrder(orderId, btn) {
    showCustomConfirm('Cancel Order', 'Cancel this order? The product will be listed as available again.', 'danger', () => {
        const orig = btn.innerText;
        btn.innerText = 'Cancelling...';
        btn.disabled = true;
        const fd = new FormData();
        fd.append('order_id', orderId);
        fd.append('role', 'seller');
        fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        fetch('actions/cancel_order_action.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Order cancelled. Product is now available again.', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast('Error: ' + (data.error || 'Could not cancel.'), 'error');
                    btn.innerText = orig;
                    btn.disabled = false;
                }
            })
            .catch(() => {
                showToast('Unexpected error occurred.', 'error');
                btn.innerText = orig;
                btn.disabled = false;
            });
    });
}
</script>

<!-- Edit Product Modal -->
<div id="edit-product-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="bg-[#0a0a0a] border border-white/10 p-8 rounded-[2.5rem] w-full max-w-md relative z-10">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold">Edit <span class="neon-text">Listing</span></h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-white text-2xl">&times;</button>
        </div>
        <form id="edit-product-form" class="space-y-5">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit-product-id" value="">

            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Title</label>
                <input type="text" name="title" id="edit-title" required maxlength="255"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Price (₹)</label>
                <input type="number" name="price" id="edit-price" required min="1" max="100000"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Description</label>
                <textarea name="description" id="edit-description" rows="3" required
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white text-sm"></textarea>
            </div>
            <button type="submit" id="edit-submit-btn" class="neon-btn w-full font-bold py-4 rounded-xl mt-2">Save Changes</button>
        </form>
    </div>
</div>

<!-- Custom Confirm Modal -->
<div id="custom-confirm-modal" class="fixed inset-0 z-[200] hidden flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-md" onclick="closeCustomConfirm()"></div>
    <div class="bg-[#0a0a0a] border border-white/10 p-8 rounded-[2rem] w-full max-w-sm relative z-10 shadow-2xl scale-95 opacity-0 transition-all duration-300" id="custom-confirm-box">
        <div class="flex items-center space-x-4 mb-4">
            <div id="custom-confirm-icon" class="w-12 h-12 rounded-full flex items-center justify-center bg-red-500/10 text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 id="custom-confirm-title" class="text-xl font-bold">Are you sure?</h3>
        </div>
        <p id="custom-confirm-message" class="text-sm text-gray-400 mb-8 leading-relaxed">This action cannot be undone.</p>
        <div class="flex space-x-4">
            <button onclick="closeCustomConfirm()" class="flex-1 py-3 rounded-xl font-bold bg-white/5 hover:bg-white/10 text-white transition">Cancel</button>
            <button id="custom-confirm-btn" class="flex-1 py-3 rounded-xl font-bold transition">Confirm</button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed bottom-6 right-6 z-[300] flex flex-col gap-3 pointer-events-none"></div>

<script>
// --- Custom Modal Logic ---
let confirmActionCallback = null;

function showCustomConfirm(title, message, type, callback) {
    document.getElementById('custom-confirm-title').innerText = title;
    document.getElementById('custom-confirm-message').innerText = message;
    
    const iconDiv = document.getElementById('custom-confirm-icon');
    const actionBtn = document.getElementById('custom-confirm-btn');
    
    if (type === 'danger') {
        iconDiv.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-red-500/10 text-red-500';
        iconDiv.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>';
        actionBtn.className = 'flex-1 py-3 rounded-xl font-bold transition bg-red-500/20 text-red-500 hover:bg-red-500 hover:text-white border border-red-500/30';
        actionBtn.innerText = 'Yes, Proceed';
    } else {
        iconDiv.className = 'w-12 h-12 rounded-full flex items-center justify-center bg-[#bcff00]/10 text-[#bcff00]';
        iconDiv.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
        actionBtn.className = 'flex-1 py-3 rounded-xl font-bold transition bg-[#bcff00]/20 text-[#bcff00] hover:bg-[#bcff00] hover:text-black border border-[#bcff00]/30';
        actionBtn.innerText = 'Yes, Proceed';
    }

    confirmActionCallback = callback;
    
    const modal = document.getElementById('custom-confirm-modal');
    const box = document.getElementById('custom-confirm-box');
    modal.classList.remove('hidden');
    setTimeout(() => {
        box.classList.remove('scale-95', 'opacity-0');
        box.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeCustomConfirm() {
    const modal = document.getElementById('custom-confirm-modal');
    const box = document.getElementById('custom-confirm-box');
    box.classList.remove('scale-100', 'opacity-100');
    box.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

document.getElementById('custom-confirm-btn').addEventListener('click', () => {
    if (confirmActionCallback) {
        confirmActionCallback();
        closeCustomConfirm();
    }
});

// --- Toast Logic ---
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `px-6 py-4 rounded-xl flex items-center space-x-3 text-sm font-bold shadow-2xl transform transition-all duration-300 translate-y-10 opacity-0 pointer-events-auto border backdrop-blur-md`;
    
    if (type === 'success') {
        toast.classList.add('bg-[#bcff00]/10', 'text-[#bcff00]', 'border-[#bcff00]/30');
        toast.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>${message}</span>`;
    } else {
        toast.classList.add('bg-red-500/10', 'text-red-500', 'border-red-500/30');
        toast.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>${message}</span>`;
    }

    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-y-10', 'opacity-0');
    }, 10);

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-10');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// --- Rest of Seller Logic ---
function handleSellerAction(selectElement, orderId) {
    const action = selectElement.value;
    selectElement.value = ""; 
    
    if (action === 'shipped') {
        openShippingModal(orderId, '', '');
    } else if (action === 'cancel') {
        sellerCancelOrder(orderId, selectElement);
    }
}

function openEditModal(id, title, price, description) {
    document.getElementById('edit-product-id').value = id;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-price').value = price;
    document.getElementById('edit-description').value = description;
    document.getElementById('edit-product-modal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('edit-product-modal').classList.add('hidden');
}

document.getElementById('edit-product-form').onsubmit = function(e) {
    e.preventDefault();
    const btn = document.getElementById('edit-submit-btn');
    btn.disabled = true;
    btn.innerText = 'Saving...';
    const formData = new FormData(this);
    fetch('actions/seller_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Listing updated successfully!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Error: ' + (data.error || 'Update failed'), 'error');
                btn.disabled = false;
                btn.innerText = 'Save Changes';
            }
        })
        .catch(() => {
            showToast('An unexpected error occurred.', 'error');
            btn.disabled = false;
            btn.innerText = 'Save Changes';
        });
};
</script>

<?php include 'includes/footer.php'; ?>
