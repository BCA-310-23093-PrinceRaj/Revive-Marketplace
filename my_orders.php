<?php 
require_once 'config/db.php';

// Auth check BEFORE any HTML output
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

$user_id = $_SESSION['user_id'];
?>

<section class="py-24 px-6 max-w-5xl mx-auto">
    <div class="mb-16" data-aos="fade-right">
        <h1 class="text-5xl font-bold tracking-tight mb-4">My <span class="neon-text">Purchases</span></h1>
        <p class="text-gray-400 text-lg">Track your orders and find your unique pieces.</p>
    </div>

    <div class="space-y-6">
        <?php
        $orders = $conn->query("SELECT o.*, p.title, p.images, u.name as seller_name, 
                                      d.status as dispute_status, d.id as dispute_id
                              FROM orders o 
                              JOIN products p ON o.product_id = p.id 
                              JOIN users u ON p.seller_id = u.id 
                              LEFT JOIN disputes d ON o.id = d.order_id
                              WHERE o.buyer_id = $user_id 
                              ORDER BY o.created_at DESC");
        
        if($orders->num_rows > 0):
            while($order = $orders->fetch_assoc()):
        ?>
            <div class="bg-white/5 border border-white/10 p-6 md:p-10 rounded-[2rem] md:rounded-[3rem] flex flex-col group hover:border-[#bcff00]/30 transition-all duration-500 shadow-2xl" data-aos="fade-up">
                <!-- Order Header -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-8 pb-8 border-b border-white/5 gap-6">
                    <div class="flex items-center gap-5 flex-1 min-w-0 w-full">
                        <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl overflow-hidden bg-[#0a0a0a] border border-white/10 flex-shrink-0 relative group-hover:shadow-[0_0_20px_rgba(188,255,0,0.15)] transition-all duration-500">
                            <img src="assets/img/products/<?php echo $order['images']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <span class="px-2 py-0.5 bg-white/10 rounded-md text-[9px] uppercase tracking-widest text-gray-300 font-bold">Order #REV-<?php echo $order['id']; ?></span>
                                <span class="text-[9px] text-gray-500 uppercase tracking-widest"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <h3 class="text-xl md:text-3xl font-bold mb-1 tracking-tighter truncate w-full text-white" title="<?php echo htmlspecialchars($order['title']); ?>"><?php echo $order['title']; ?></h3>
                            <p class="text-gray-400 text-xs md:text-sm italic truncate">Purchased from <a href="seller_profile.php?id=<?php $sid_q = $conn->query("SELECT seller_id FROM products WHERE id = " . $order['product_id']); echo $sid_q ? $sid_q->fetch_assoc()['seller_id'] : ''; ?>" class="text-white font-semibold hover:text-[#bcff00] underline decoration-white/20 hover:decoration-[#bcff00] transition"><?php echo $order['seller_name']; ?></a></p>
                        </div>
                    </div>
                    
                    <div class="flex flex-row lg:flex-col items-center lg:items-end justify-between w-full lg:w-auto flex-shrink-0 bg-black/20 lg:bg-transparent p-4 lg:p-0 rounded-2xl lg:rounded-none border border-white/5 lg:border-none">
                        <div class="flex flex-col items-start lg:items-end">
                            <p class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-1 lg:hidden">Total Amount</p>
                            <p class="text-2xl md:text-4xl font-bold tracking-tighter italic text-[#bcff00]">₹<?php echo number_format($order['amount'], 0); ?></p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <button onclick="downloadInvoiceDirect(<?php echo $order['id']; ?>, this)" class="text-[10px] uppercase font-bold text-gray-400 hover:text-white tracking-widest transition flex items-center gap-1 bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg lg:bg-transparent lg:hover:bg-transparent lg:px-0 lg:py-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Invoice
                            </button>
                            
                            <?php if ($order['dispute_status']): ?>
                                <span class="text-[9px] uppercase font-bold px-2 py-1 bg-red-500/10 border border-red-500/20 text-red-500 rounded-md tracking-wider">
                                    Dispute: <?php echo str_replace('_', ' ', $order['dispute_status']); ?>
                                </span>
                            <?php elseif ($order['status'] === 'completed' && strtotime($order['created_at']) >= strtotime('-48 hours')): ?>
                                <button onclick="openDisputeModal(<?php echo $order['id']; ?>)" class="text-[10px] uppercase font-bold text-red-500/70 hover:text-red-500 tracking-widest transition hover:underline">
                                    Dispute Order
                                </button>
                            <?php endif; ?>

                            <?php if ($order['status'] === 'pending'): ?>
                                <button onclick="openCancelModal(<?php echo $order['id']; ?>, '<?php echo addslashes(htmlspecialchars($order['title'])); ?>', this)" class="text-[10px] uppercase font-bold text-orange-500/70 hover:text-orange-500 tracking-widest transition hover:underline">
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar or Cancelled State -->
                <?php 
                $status = strtolower($order['status']); 
                if ($status === 'cancelled'):
                ?>
                    <div class="relative px-2 sm:px-4 mb-4">
                        <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-5 flex items-center gap-4">
                            <div class="w-12 h-12 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-red-500 uppercase tracking-widest mb-1">Order Cancelled</h4>
                                <p class="text-gray-400 text-xs md:text-sm">This order has been cancelled and will not be fulfilled.</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php
                    $steps = ['pending', 'shipped', 'delivered', 'completed'];
                    $current_step = array_search($status, $steps);
                    if($current_step === false) $current_step = 0;
                    ?>
                    <div class="relative px-2 sm:px-4 mb-4">
                        <!-- Progress Line (Background) -->
                        <div class="absolute top-5 left-[12.5%] right-[12.5%] h-1 bg-white/10 rounded-full -z-0"></div>
                        <!-- Progress Line (Active) -->
                        <div class="absolute top-5 left-[12.5%] h-1 bg-gradient-to-r from-[#bcff00]/50 to-[#bcff00] rounded-full transition-all duration-1000 -z-0 shadow-[0_0_10px_rgba(188,255,0,0.5)]" 
                             style="width: <?php echo ($current_step / 3) * 75; ?>%;"></div>
     
                        <div class="relative z-10 grid grid-cols-4">
                            <div class="flex flex-col items-center group/step">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-500 <?php echo $current_step >= 0 ? 'bg-[#bcff00] text-black shadow-[0_0_15px_rgba(188,255,0,0.4)] scale-110' : 'bg-[#111] text-gray-600 border-2 border-white/10'; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                </div>
                                <span class="text-[9px] md:text-[10px] uppercase tracking-widest font-bold mt-3 text-center <?php echo $current_step >= 0 ? 'text-white' : 'text-gray-600'; ?>">Ordered</span>
                            </div>
                            <div class="flex flex-col items-center group/step">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-500 <?php echo $current_step >= 1 ? 'bg-[#bcff00] text-black shadow-[0_0_15px_rgba(188,255,0,0.4)] scale-110' : 'bg-[#111] text-gray-600 border-2 border-white/10'; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                </div>
                                <span class="text-[9px] md:text-[10px] uppercase tracking-widest font-bold mt-3 text-center <?php echo $current_step >= 1 ? 'text-white' : 'text-gray-600'; ?>">Shipped</span>
                            </div>
                            <div class="flex flex-col items-center group/step">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-500 <?php echo $current_step >= 2 ? 'bg-[#bcff00] text-black shadow-[0_0_15px_rgba(188,255,0,0.4)] scale-110' : 'bg-[#111] text-gray-600 border-2 border-white/10'; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <span class="text-[9px] md:text-[10px] uppercase tracking-widest font-bold mt-3 text-center <?php echo $current_step >= 2 ? 'text-white' : 'text-gray-600'; ?>">Delivered</span>
                            </div>
                            <div class="flex flex-col items-center group/step">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-500 <?php echo $current_step >= 3 ? 'bg-[#bcff00] text-black shadow-[0_0_15px_rgba(188,255,0,0.4)] scale-110' : 'bg-[#111] text-gray-600 border-2 border-white/10'; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <span class="text-[9px] md:text-[10px] uppercase tracking-widest font-bold mt-3 text-center <?php echo $current_step >= 3 ? 'text-white' : 'text-gray-600'; ?>">Completed</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Footer: Shipping Info & Actions -->
                <?php if ($order['tracking_number'] && $status !== 'cancelled'): ?>
                    <div class="mt-8 p-5 bg-[#0a0a0a] border border-white/5 rounded-3xl flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                        <!-- Tracking Info -->
                        <div class="flex items-center space-x-4 w-full lg:w-auto">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-white flex-shrink-0 border border-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 16h6m-6 0H6m13 0a2 2 0 002-2v-4a2 2 0 00-.59-1.41L17 7h-4m0 0v5h5M13 16h6" /></svg>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-widest text-gray-500 font-bold mb-0.5">Shipped via <?php echo htmlspecialchars($order['carrier']); ?></p>
                                <p class="font-mono text-white text-sm md:text-base tracking-wider"><?php echo htmlspecialchars($order['tracking_number']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row w-full lg:w-auto gap-3">
                            <?php if ($order['status'] === 'shipped' || $order['status'] === 'delivered'): ?>
                                <form action="actions/buyer_action.php" method="POST" class="flex-1 sm:flex-none" id="mark-received-form-<?php echo $order['id']; ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="mark_completed">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="button" onclick="openMarkReceivedModal(<?php echo $order['id']; ?>)" class="w-full bg-[#bcff00] hover:bg-[#d4ff4d] text-black font-bold px-6 py-3.5 rounded-xl text-center text-[11px] uppercase tracking-widest transition shadow-[0_0_20px_rgba(188,255,0,0.2)] hover:shadow-[0_0_25px_rgba(188,255,0,0.4)] flex items-center justify-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        Mark as Received
                                    </button>
                                </form>
                            <?php elseif ($order['status'] === 'completed'): ?>
                                <?php if ($order['return_status'] === 'none'): ?>
                                    <button type="button" onclick="openReturnModal(<?php echo $order['id']; ?>)" class="flex-1 sm:flex-none bg-transparent hover:bg-red-500/10 border border-red-500/20 text-red-500 font-bold px-6 py-3.5 rounded-xl text-center text-[11px] uppercase tracking-widest transition">
                                        Request Return
                                    </button>
                                <?php else: ?>
                                    <span class="flex-1 sm:flex-none flex items-center justify-center px-6 py-3.5 rounded-xl text-[11px] uppercase tracking-widest font-bold <?php 
                                        if($order['return_status'] === 'requested') echo 'bg-orange-500/10 text-orange-500 border border-orange-500/20';
                                        elseif($order['return_status'] === 'approved') echo 'bg-[#bcff00]/10 text-[#bcff00] border border-[#bcff00]/20';
                                        elseif($order['return_status'] === 'rejected') echo 'bg-red-500/10 text-red-500 border border-red-500/20';
                                    ?>">
                                        Return <?php echo ucfirst($order['return_status']); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php 
            endwhile;
        else:
            echo "<div class='py-20 text-center border border-dashed border-white/10 rounded-[2rem]'>
                    <p class='text-gray-500 italic mb-4'>You haven't bought anything yet.</p>
                    <a href='shop.php' class='neon-btn px-8 py-3 rounded-xl font-bold'>Browse Shop</a>
                  </div>";
        endif;
        ?>
    </div>
</section>

<!-- Dispute Modal -->
<div id="dispute-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeDisputeModal()"></div>
    <div class="bg-[#0a0a0a] border border-white/10 p-8 rounded-[2.5rem] w-full max-w-md relative z-10" data-aos="zoom-in">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold">Dispute <span class="neon-text">Order</span></h3>
            <button onclick="closeDisputeModal()" class="text-gray-500 hover:text-white text-2xl">&times;</button>
        </div>
        <form id="dispute-form" class="space-y-6">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create_dispute">
            <input type="hidden" name="order_id" id="dispute-order-id" value="">
            
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Reason</label>
                <select name="reason" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white">
                    <option value="Damaged item" class="bg-black text-white">Damaged item</option>
                    <option value="Wrong size" class="bg-black text-white">Wrong size / Incorrect fit</option>
                    <option value="Item not as described" class="bg-black text-white">Item not as described</option>
                    <option value="Other" class="bg-black text-white">Other Reason</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Explanation / Details</label>
                <textarea name="details" required rows="4" placeholder="Please describe the issue in detail..." class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white text-sm"></textarea>
            </div>
            
            <button type="submit" id="dispute-submit-btn" class="neon-btn w-full font-bold py-4 rounded-xl mt-4">Submit Dispute</button>
        </form>
    </div>
</div>

<!-- Include html2pdf in my_orders -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadInvoiceDirect(orderId, btnElement) {
    const originalText = btnElement.innerText;
    btnElement.innerText = "DOWNLOADING...";
    btnElement.style.pointerEvents = "none";
    btnElement.classList.add('text-[#bcff00]');
    
    fetch('invoice.php?id=' + orderId + '&raw=true')
        .then(response => response.text())
        .then(html => {
            const container = document.createElement('div');
            container.style.position = 'absolute';
            container.style.opacity = '0';
            container.style.pointerEvents = 'none';
            container.style.zIndex = '-9999';
            container.style.width = '800px'; 
            container.innerHTML = html;
            document.body.appendChild(container);
            
            const element = container.querySelector('#invoice-box');
            
            const opt = {
                margin:       [10, 10, 10, 10],
                filename:     'Invoice_REV-' + String(orderId).padStart(5, '0') + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            setTimeout(() => {
                html2pdf().set(opt).from(element).save().then(() => {
                    document.body.removeChild(container);
                    btnElement.innerText = originalText;
                    btnElement.style.pointerEvents = "auto";
                    btnElement.classList.remove('text-[#bcff00]');
                });
            }, 300);
        });
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-6 right-6 z-[9999] flex items-center space-x-3 px-6 py-4 rounded-2xl shadow-2xl font-bold text-sm transition-all duration-500 translate-x-full`;
    if (type === 'success') {
        toast.classList.add('bg-[#bcff00]', 'text-black');
        toast.innerHTML = `<svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg><span>${message}</span>`;
    } else {
        toast.classList.add('bg-red-500', 'text-white');
        toast.innerHTML = `<svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><span>${message}</span>`;
    }
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.remove('translate-x-full'), 50);
    setTimeout(() => { toast.classList.add('translate-x-full', 'opacity-0'); setTimeout(() => toast.remove(), 500); }, 3500);
}

// Cancel Order Modal
let _cancelOrderId = null;
let _cancelBtnEl = null;

function openCancelModal(orderId, orderTitle, btnElement) {
    _cancelOrderId = orderId;
    _cancelBtnEl = btnElement;
    document.getElementById('cancel-order-title').innerText = orderTitle;
    document.getElementById('cancel-modal').classList.remove('hidden');
    document.getElementById('cancel-modal').classList.add('flex');
}

function closeCancelModal() {
    document.getElementById('cancel-modal').classList.add('hidden');
    document.getElementById('cancel-modal').classList.remove('flex');
    _cancelOrderId = null;
    _cancelBtnEl = null;
}

function confirmCancelOrder() {
    if (!_cancelOrderId || !_cancelBtnEl) return;
    closeCancelModal();

    const originalText = _cancelBtnEl.innerText;
    _cancelBtnEl.innerText = 'Cancelling...';
    _cancelBtnEl.disabled = true;

    const formData = new FormData();
    formData.append('order_id', _cancelOrderId);
    formData.append('role', 'buyer');
    formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

    fetch('actions/cancel_order_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('✅ Order cancelled successfully.');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showToast('❌ ' + (data.error || 'Could not cancel order.'), 'error');
                _cancelBtnEl.innerText = originalText;
                _cancelBtnEl.disabled = false;
            }
        })
        .catch(() => {
            showToast('❌ Unexpected error. Please try again.', 'error');
            _cancelBtnEl.innerText = originalText;
            _cancelBtnEl.disabled = false;
        });
}

function openReturnModal(orderId) {
    document.getElementById('return-order-id').value = orderId;
    document.getElementById('return-modal').classList.remove('hidden');
}

function closeReturnModal() {
    document.getElementById('return-modal').classList.add('hidden');
}

function openDisputeModal(orderId) {
    document.getElementById('dispute-order-id').value = orderId;
    document.getElementById('dispute-modal').classList.remove('hidden');
}

function closeDisputeModal() {
    document.getElementById('dispute-modal').classList.add('hidden');
}

document.getElementById('dispute-form').onsubmit = function(e) {
    e.preventDefault();
    const btn = document.getElementById('dispute-submit-btn');
    btn.disabled = true;
    btn.innerText = "Submitting...";

    const formData = new FormData(this);
    fetch('actions/dispute_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('✅ Dispute submitted successfully.');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showToast('❌ ' + (data.error || 'Submission failed'), 'error');
                btn.disabled = false;
                btn.innerText = "Submit Dispute";
            }
        })
        .catch(() => {
            showToast('❌ Unexpected error. Please try again.', 'error');
            btn.disabled = false;
            btn.innerText = "Submit Dispute";
        });
}
</script>

<!-- ===== CANCEL ORDER MODAL ===== -->
<div id="cancel-modal" class="hidden fixed inset-0 z-[200] bg-black/80 backdrop-blur-sm items-center justify-center px-4">
    <div class="bg-[#0f0f0f] border border-white/10 rounded-3xl p-8 w-full max-w-md shadow-2xl" data-aos="zoom-in">
        <!-- Icon -->
        <div class="w-16 h-16 bg-orange-500/10 border border-orange-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <!-- Title -->
        <h3 class="text-2xl font-bold text-center mb-2">Cancel Order?</h3>
        <p class="text-gray-500 text-center text-sm mb-1">You are about to cancel:</p>
        <p id="cancel-order-title" class="text-white font-bold text-center mb-6 truncate px-4"></p>

        <!-- Warning -->
        <div class="bg-orange-500/5 border border-orange-500/20 rounded-2xl p-4 mb-8 text-center">
            <p class="text-orange-400 text-xs font-semibold">⚠️ This action cannot be undone. The order will be cancelled and the product will become available again.</p>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4">
            <button onclick="closeCancelModal()" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold py-4 rounded-2xl transition">
                Keep Order
            </button>
            <button onclick="confirmCancelOrder()" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-orange-500/20">
                Yes, Cancel
            </button>
        </div>
    </div>
</div>

<!-- ✅ Mark as Received — Custom Confirmation Modal -->
<div id="mark-received-modal" class="fixed inset-0 z-[100] hidden items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeMarkReceivedModal()"></div>
    <div class="bg-[#0a0a0a] border border-white/10 p-10 rounded-[2.5rem] w-full max-w-md relative z-10 shadow-2xl text-center" data-aos="zoom-in">
        <!-- Icon -->
        <div class="w-20 h-20 rounded-full bg-[#bcff00]/10 border border-[#bcff00]/20 flex items-center justify-center mx-auto mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <!-- Text -->
        <h3 class="text-2xl font-bold mb-3">Order Received?</h3>
        <p class="text-gray-400 text-sm leading-relaxed mb-8">
            Confirm that you have received this order in good condition.<br>
            <span class="text-[#bcff00] font-bold">This will mark the transaction as completed and final.</span>
        </p>
        <!-- Buttons -->
        <div class="flex gap-4">
            <button onclick="closeMarkReceivedModal()" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold py-4 rounded-2xl transition">
                Not Yet
            </button>
            <button onclick="confirmMarkReceived()" class="flex-1 bg-[#bcff00] hover:bg-[#d4ff4d] text-black font-bold py-4 rounded-2xl transition shadow-lg shadow-[#bcff00]/20">
                ✓ Yes, Received!
            </button>
        </div>
    </div>
</div>

<!-- Return Order Modal -->
<div id="return-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeReturnModal()"></div>
    <div class="bg-[#0a0a0a] border border-white/10 p-8 rounded-[2.5rem] w-full max-w-md relative z-10 shadow-2xl">
        <h3 class="text-2xl font-bold mb-6">Request <span class="text-red-500">Return</span></h3>
        <p class="text-gray-400 text-sm mb-6">Please provide a reason for returning this item. The seller will review your request.</p>
        <form action="actions/buyer_action.php" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="request_return">
            <input type="hidden" name="order_id" id="return-order-id" value="">
            <textarea name="return_reason" required rows="4" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-red-500 text-white text-sm" placeholder="Why are you returning this item?"></textarea>
            <div class="flex gap-4">
                <button type="button" onclick="closeReturnModal()" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-3 rounded-xl transition text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-red-500/20 text-sm">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReturnModal(orderId) {
    document.getElementById('return-order-id').value = orderId;
    document.getElementById('return-modal').classList.remove('hidden');
}

function closeReturnModal() {
    document.getElementById('return-modal').classList.add('hidden');
}

// Mark as Received — Custom Modal
let _markReceivedOrderId = null;
function openMarkReceivedModal(orderId) {
    _markReceivedOrderId = orderId;
    document.getElementById('mark-received-modal').classList.remove('hidden');
    document.getElementById('mark-received-modal').classList.add('flex');
}
function closeMarkReceivedModal() {
    document.getElementById('mark-received-modal').classList.add('hidden');
    document.getElementById('mark-received-modal').classList.remove('flex');
    _markReceivedOrderId = null;
}
function confirmMarkReceived() {
    if (_markReceivedOrderId) {
        document.getElementById('mark-received-form-' + _markReceivedOrderId).submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
