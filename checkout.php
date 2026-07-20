<?php 
require_once 'config/db.php';

// Auth check BEFORE any HTML output
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$cart_items = $conn->query("SELECT c.quantity, p.title, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");

$total = 0;
$items = [];
while($row = $cart_items->fetch_assoc()) {
    $items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

if(count($items) === 0) {
    echo "<section class='py-40 text-center'><h1 class='text-4xl font-bold'>Your bag is empty.</h1><a href='shop.php' class='text-[#bcff00] mt-4 block'>Back to Shop</a></section>";
    include 'includes/footer.php';
    exit();
}
?>

<section class="py-24 px-6 max-w-5xl mx-auto">
    <div class="mb-16" data-aos="fade-right">
        <h1 class="text-5xl font-bold tracking-tight mb-4">Final <span class="neon-text">Step</span></h1>
        <p class="text-gray-400 text-lg">Complete your order and get your pieces.</p>
    </div>

    <form id="checkout-form">
    <?php echo csrf_field(); ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-16">
        <!-- Shipping Form -->
        <div class="lg:col-span-2 space-y-12" data-aos="fade-up">
            <div class="bg-white/5 border border-white/10 p-10 rounded-[2.5rem]">
                <h3 class="text-2xl font-bold mb-8">Shipping Address</h3>
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" required readonly class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3 focus:outline-none focus:border-[#bcff00] transition text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Phone Number</label>
                            <input type="tel" name="phone" required inputmode="numeric" maxlength="10" pattern="[0-9]{10}" title="Please enter a 10-digit mobile number" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" placeholder="10-digit mobile number" class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3 focus:outline-none focus:border-[#bcff00] transition text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Street Address</label>
                        <textarea name="address" rows="3" required maxlength="500" placeholder="Apartment, house number, area, city, pincode..." class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3 focus:outline-none focus:border-[#bcff00] transition text-white"></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white/5 border border-white/10 p-10 rounded-[2.5rem]">
                <h3 class="text-2xl font-bold mb-8">Payment Method</h3>
                <div class="space-y-4">
                    <label class="flex items-center p-6 bg-white/5 border border-[#bcff00]/50 rounded-2xl cursor-pointer">
                        <input type="radio" name="payment_method" value="cod" checked class="w-5 h-5 accent-[#bcff00]">
                        <div class="ml-4">
                            <p class="font-bold">Cash on Delivery (COD)</p>
                            <p class="text-xs text-gray-500">Pay when you receive your package.</p>
                        </div>
                    </label>
                    <label class="flex items-center p-6 bg-white/5 border border-white/10 rounded-2xl cursor-pointer hover:border-[#bcff00]/50 transition">
                        <input type="radio" name="payment_method" value="online" class="w-5 h-5 accent-[#bcff00]">
                        <div class="ml-4">
                            <p class="font-bold">Online Payment</p>
                            <p class="text-xs text-gray-500">Pay securely with Credit Card / UPI.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1" data-aos="fade-left">
            <div class="bg-[#bcff00]/5 border border-[#bcff00]/20 p-10 rounded-[2.5rem] sticky top-32">
                <h3 class="text-2xl font-bold mb-8 text-[#bcff00]">Order Summary</h3>
                <div class="space-y-4 mb-8">
                    <?php foreach($items as $item): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400"><?php echo $item['title']; ?> <span class="text-xs">x<?php echo $item['quantity']; ?></span></span>
                        <span class="font-bold text-white">₹<?php echo number_format($item['price'] * $item['quantity'], 0); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="border-t border-[#bcff00]/20 pt-6 space-y-4">
                    <div class="flex justify-between text-gray-400 text-sm">
                        <span>Subtotal</span>
                        <span class="font-bold text-white" id="checkout-subtotal">₹<?php echo number_format($total, 0); ?></span>
                    </div>
                    <div class="flex justify-between text-gray-400 text-sm">
                        <span>Shipping</span>
                        <span class="text-[#bcff00] font-bold uppercase text-[10px]">Free</span>
                    </div>
                    <div class="flex justify-between items-center pt-2">
                        <span class="text-xl font-bold text-white">Total</span>
                        <span class="text-3xl font-bold text-[#bcff00]" id="checkout-total">₹<?php echo number_format($total, 0); ?></span>
                    </div>
                </div>
                <button type="submit" id="place-order-btn" class="neon-btn w-full mt-10 font-bold py-5 rounded-2xl text-lg shadow-xl shadow-[#bcff00]/10">
                    Place Order
                </button>
            </div>
        </div>
    </div>
    </form>

    <script>
    // Toast Notification Function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-6 right-6 z-[9999] flex items-center space-x-3 px-6 py-4 rounded-2xl shadow-2xl font-bold text-sm transition-all duration-500 translate-x-full`;
        
        if (type === 'success') {
            toast.classList.add('bg-[#bcff00]', 'text-black', 'shadow-[#bcff00]/30');
            toast.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg><span>${message}</span>`;
        } else {
            toast.classList.add('bg-red-500', 'text-white', 'shadow-red-500/30');
            toast.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><span>${message}</span>`;
        }
        
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.remove('translate-x-full'), 50);
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }

    document.getElementById('checkout-form').onsubmit = function(e) {
        e.preventDefault();
        const btn = document.getElementById('place-order-btn');
        btn.disabled = true;
        btn.innerText = "Processing...";

        const formData = new FormData(this);
        fetch('actions/order_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('✅ Order placed! Redirecting...', 'success');
                    setTimeout(() => window.location.href = 'index.php?success=order_placed', 2000);
                } else if (data.redirect) {
                    showToast('Redirecting to payment...', 'success');
                    setTimeout(() => window.location.href = data.redirect, 1500);
                } else {
                    showToast('❌ ' + (data.error || 'Order failed. Please try again.'), 'error');
                    btn.disabled = false;
                    btn.innerText = "Place Order";
                }
            })
            .catch(() => {
                showToast('❌ Unexpected error. Please try again.', 'error');
                btn.disabled = false;
                btn.innerText = "Place Order";
            });
    }
    </script>
</section>

<?php include 'includes/footer.php'; ?>
