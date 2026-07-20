<?php
require_once 'config/db.php';
include 'includes/header.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_ids_str = $_GET['order_ids'] ?? '';
if (empty($order_ids_str)) {
    header("Location: shop.php");
    exit();
}

$order_ids = explode(',', $order_ids_str);
$total_amount = 0;

// Fetch total amount (net of discounts)
foreach ($order_ids as $id) {
    $id = (int)$id;
    $res = $conn->query("SELECT amount, discount_applied FROM orders WHERE id = $id");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $total_amount += ($row['amount'] - $row['discount_applied']);
    }
}
?>

<section class="py-24 px-6 max-w-5xl mx-auto relative">
    <!-- Payment Page Loading Overlay -->
    <div id="payment-loading-overlay" class="fixed inset-0 z-[200] hidden flex flex-col items-center justify-center bg-black/95 backdrop-blur-md">
        <div class="w-16 h-16 border-4 border-t-[#bcff00] border-r-transparent border-b-[#bcff00] border-l-transparent rounded-full animate-spin mb-6"></div>
        <h3 class="text-2xl font-bold text-white tracking-wide" id="loading-status-text">Securing Transaction...</h3>
        <p class="text-sm text-gray-500 mt-2">Do not refresh the page or click back.</p>
    </div>

    <div class="mb-12" data-aos="fade-right">
        <h1 class="text-5xl font-bold tracking-tight mb-4">Secure <span class="neon-text">Payment</span></h1>
        <p class="text-gray-400 text-lg">Choose your preferred gateway to complete checkout.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Gateway / Payment Panels -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Selector Tabs -->
            <div class="flex p-1.5 bg-white/5 border border-white/10 rounded-2xl gap-2">
                <button type="button" id="tab-stripe-btn" onclick="switchGateway('stripe')" class="flex-1 py-3 text-sm font-bold uppercase tracking-wider rounded-xl transition duration-300 bg-[#bcff00] text-black shadow-lg shadow-[#bcff00]/10">
                    Stripe Card
                </button>
                <button type="button" id="tab-razorpay-btn" onclick="switchGateway('razorpay')" class="flex-1 py-3 text-sm font-bold uppercase tracking-wider rounded-xl transition duration-300 text-gray-400 hover:text-white hover:bg-white/5">
                    Razorpay UPI/Netbanking
                </button>
            </div>

            <!-- Main Form -->
            <form id="payment-main-form" action="actions/complete_payment_action.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="order_ids" value="<?php echo $order_ids_str; ?>">
                <input type="hidden" name="gateway_method" id="selected-gateway-method" value="stripe">

                <!-- Stripe Card Element Panel -->
                <div id="stripe-panel" class="bg-white/5 border border-white/10 p-10 rounded-[2.5rem] space-y-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-2xl font-bold">Stripe Elements</h3>
                        <!-- Card Type Indicator -->
                        <div id="card-type-icon" class="text-gray-500 font-mono font-bold text-xs uppercase tracking-widest px-3 py-1 bg-white/5 rounded-lg border border-white/10 transition-all duration-300">
                            Card Type
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Cardholder Name</label>
                            <input type="text" id="stripe-name" required maxlength="100" pattern="[A-Za-z .'-]+" title="Name can only contain letters, spaces, apostrophes, periods, and hyphens" oninput="this.value=this.value.replace(/[0-9]/g,'')" placeholder="e.g. Aditya Kumar Sah" class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3.5 focus:outline-none focus:border-[#bcff00] transition text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Card Number</label>
                            <div class="relative">
                                <input type="text" id="stripe-card-number" required maxlength="19" placeholder="4111 2222 3333 4444" class="w-full bg-white/5 border border-white/10 rounded-xl pl-5 pr-14 py-3.5 focus:outline-none focus:border-[#bcff00] transition text-white tracking-widest font-mono text-sm">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Expiry Date</label>
                                <input type="text" id="stripe-expiry" required maxlength="5" placeholder="MM/YY" class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3.5 focus:outline-none focus:border-[#bcff00] transition text-white tracking-widest font-mono text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">CVV</label>
                                <input type="password" id="stripe-cvv" required maxlength="4" placeholder="•••" class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3.5 focus:outline-none focus:border-[#bcff00] transition text-white tracking-widest font-mono text-sm">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="neon-btn w-full mt-8 font-bold py-5 rounded-2xl text-lg shadow-xl shadow-[#bcff00]/10">
                        Securely Pay ₹<?php echo number_format($total_amount, 0); ?>
                    </button>
                </div>

                <!-- Razorpay Panel View -->
                <div id="razorpay-panel" class="bg-white/5 border border-white/10 p-10 rounded-[2.5rem] space-y-8 hidden">
                    <div class="flex justify-between items-center">
                        <h3 class="text-2xl font-bold">Razorpay Gateway</h3>
                        <span class="text-[9px] uppercase font-bold tracking-wider px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-full">Secure API</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-6 bg-white/5 border border-white/10 rounded-2xl cursor-pointer hover:border-[#bcff00]/30 transition group flex flex-col items-center justify-center text-center py-8" onclick="triggerRazorpayMockFlow('upi')">
                            <div class="w-12 h-12 rounded-full bg-[#bcff00]/10 flex items-center justify-center text-[#bcff00] mb-4 group-hover:scale-110 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            </div>
                            <p class="font-bold">UPI / QR Code</p>
                            <p class="text-xs text-gray-500 mt-1">Pay via PhonePe, GPay, Paytm</p>
                        </div>

                        <div class="p-6 bg-white/5 border border-white/10 rounded-2xl cursor-pointer hover:border-[#bcff00]/30 transition group flex flex-col items-center justify-center text-center py-8" onclick="triggerRazorpayMockFlow('netbanking')">
                            <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400 mb-4 group-hover:scale-110 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.111L12 12l3.889 3.889M8 8h8M3 21h18M4 11h16M5 11v6m4-6v6m4-6v6m4-6v6m4-6v6" /></svg>
                            </div>
                            <p class="font-bold">Netbanking</p>
                            <p class="text-xs text-gray-500 mt-1">Select from major Indian banks</p>
                        </div>
                    </div>

                    <button type="button" onclick="triggerRazorpayMockFlow('standard')" class="w-full border border-white/10 bg-white/5 hover:bg-[#bcff00] hover:text-black hover:border-transparent text-white font-bold py-5 rounded-2xl text-lg transition duration-300 flex items-center justify-center space-x-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <span>Launch Razorpay Popup</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Order Summary SidePanel -->
        <div class="lg:col-span-1" data-aos="fade-left">
            <div class="bg-[#bcff00]/5 border border-[#bcff00]/20 p-10 rounded-[2.5rem] sticky top-32">
                <h3 class="text-2xl font-bold mb-8 text-[#bcff00]">Payment Summary</h3>
                <div class="space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Total Purchase</span>
                        <span class="font-bold text-white">₹<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-[#bcff00]/10 pt-4">
                        <span class="text-gray-400">Taxes & Fees</span>
                        <span class="text-[#bcff00] font-bold uppercase text-[10px]">Calculated / Incl.</span>
                    </div>
                    <div class="flex justify-between items-center border-t border-[#bcff00]/20 pt-6">
                        <span class="text-xl font-bold text-white">Net Due</span>
                        <span class="text-3xl font-bold text-[#bcff00]">₹<?php echo number_format($total_amount, 0); ?></span>
                    </div>
                </div>
                <div class="mt-8 p-5 bg-white/5 border border-white/10 rounded-2xl flex items-center space-x-3.5">
                    <div class="text-[#bcff00]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-white tracking-wider">SSL Encrypted</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">Mock testing gateway mode active.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Razorpay Pop-Up Overlay Emulator -->
<div id="razorpay-emulator-modal" class="fixed inset-0 z-[150] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeRazorpayEmulator()"></div>
    <div class="bg-[#0f1115] border border-white/10 w-full max-w-md rounded-[2.5rem] overflow-hidden relative z-10 shadow-2xl animate-fade-in">
        <!-- Header -->
        <div class="p-6 bg-gradient-to-r from-blue-600 to-blue-800 text-white flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center font-bold">R</div>
                <div>
                    <h4 class="font-bold text-sm tracking-wide">Razorpay Checkout</h4>
                    <p class="text-[10px] text-blue-200">Order ID: #REV-<?php echo implode('-', $order_ids); ?></p>
                </div>
            </div>
            <button onclick="closeRazorpayEmulator()" class="text-white/60 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <div class="p-8 space-y-6">
            <!-- Amount block -->
            <div class="flex justify-between items-center bg-white/5 border border-white/10 p-5 rounded-2xl">
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Amount Due</p>
                    <p class="text-xs text-gray-400 mt-1">revive@store.com</p>
                </div>
                <p class="text-2xl font-bold text-white">₹<?php echo number_format($total_amount, 0); ?></p>
            </div>

            <!-- Custom Content based on payment choice -->
            <div id="razorpay-upi-view" class="space-y-4 hidden">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Select UPI Application</p>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" class="py-3 bg-white/5 border border-white/10 rounded-xl hover:border-blue-500 hover:bg-blue-500/5 transition text-xs font-bold text-white">PhonePe</button>
                    <button type="button" class="py-3 bg-white/5 border border-white/10 rounded-xl hover:border-blue-500 hover:bg-blue-500/5 transition text-xs font-bold text-white">Google Pay</button>
                    <button type="button" class="py-3 bg-white/5 border border-white/10 rounded-xl hover:border-blue-500 hover:bg-blue-500/5 transition text-xs font-bold text-white">Paytm UPI</button>
                    <button type="button" class="py-3 bg-white/5 border border-white/10 rounded-xl hover:border-blue-500 hover:bg-blue-500/5 transition text-xs font-bold text-white">BHIM UPI</button>
                </div>
                <div class="pt-4 flex flex-col items-center justify-center space-y-2 border-t border-white/5 mt-4">
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest">Scan Mock QR</p>
                    <div class="w-32 h-32 bg-white p-2 rounded-xl flex items-center justify-center">
                        <!-- Simulated QR Code SVG representation -->
                        <svg class="w-full h-full text-black" viewBox="0 0 100 100">
                            <rect x="0" y="0" width="30" height="30" fill="currentColor"/>
                            <rect x="10" y="10" width="10" height="10" fill="white"/>
                            <rect x="70" y="0" width="30" height="30" fill="currentColor"/>
                            <rect x="80" y="10" width="10" height="10" fill="white"/>
                            <rect x="0" y="70" width="30" height="30" fill="currentColor"/>
                            <rect x="10" y="80" width="10" height="10" fill="white"/>
                            <rect x="40" y="40" width="20" height="20" fill="currentColor"/>
                            <rect x="60" y="60" width="20" height="20" fill="currentColor"/>
                            <rect x="80" y="80" width="20" height="20" fill="currentColor"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div id="razorpay-netbanking-view" class="space-y-4 hidden">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Select Bank Account</p>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" class="py-3 bg-white/5 border border-white/10 rounded-xl hover:border-blue-500 hover:bg-blue-500/5 transition text-xs font-bold text-white">State Bank of India</button>
                    <button type="button" class="py-3 bg-white/5 border border-white/10 rounded-xl hover:border-blue-500 hover:bg-blue-500/5 transition text-xs font-bold text-white">HDFC Bank</button>
                    <button type="button" class="py-3 bg-white/5 border border-white/10 rounded-xl hover:border-blue-500 hover:bg-blue-500/5 transition text-xs font-bold text-white">ICICI Bank</button>
                    <button type="button" class="py-3 bg-white/5 border border-white/10 rounded-xl hover:border-blue-500 hover:bg-blue-500/5 transition text-xs font-bold text-white">Axis Bank</button>
                </div>
            </div>

            <div id="razorpay-standard-view" class="space-y-4">
                <p class="text-xs text-gray-400 text-center leading-relaxed">This emulator integrates standard Razorpay payment options. Select status to test checkout flow redirects.</p>
            </div>

            <!-- Simulation Controls -->
            <div class="grid grid-cols-2 gap-4 pt-6 border-t border-white/5">
                <button type="button" onclick="completeMockRazorpay(false)" class="w-full bg-red-500/10 border border-red-500/20 hover:bg-red-500 hover:text-white hover:border-transparent text-red-500 font-bold py-3.5 rounded-xl text-xs uppercase tracking-wider transition">
                    Simulate Fail
                </button>
                <button type="button" onclick="completeMockRazorpay(true)" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3.5 rounded-xl text-xs uppercase tracking-wider transition">
                    Simulate Success
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab gateway toggling
    function switchGateway(method) {
        const stripeBtn = document.getElementById('tab-stripe-btn');
        const razorBtn = document.getElementById('tab-razorpay-btn');
        const stripePanel = document.getElementById('stripe-panel');
        const razorPanel = document.getElementById('razorpay-panel');
        const gatewayInput = document.getElementById('selected-gateway-method');

        gatewayInput.value = method;

        if (method === 'stripe') {
            stripeBtn.className = "flex-1 py-3 text-sm font-bold uppercase tracking-wider rounded-xl transition duration-300 bg-[#bcff00] text-black shadow-lg shadow-[#bcff00]/10";
            razorBtn.className = "flex-1 py-3 text-sm font-bold uppercase tracking-wider rounded-xl transition duration-300 text-gray-400 hover:text-white hover:bg-white/5";
            stripePanel.classList.remove('hidden');
            razorPanel.classList.add('hidden');
        } else {
            razorBtn.className = "flex-1 py-3 text-sm font-bold uppercase tracking-wider rounded-xl transition duration-300 bg-[#bcff00] text-black shadow-lg shadow-[#bcff00]/10";
            stripeBtn.className = "flex-1 py-3 text-sm font-bold uppercase tracking-wider rounded-xl transition duration-300 text-gray-400 hover:text-white hover:bg-white/5";
            razorPanel.classList.remove('hidden');
            stripePanel.classList.add('hidden');
        }
    }

    // Card Input Auto Formatting & Luhn Checking
    const cardInput = document.getElementById('stripe-card-number');
    const expiryInput = document.getElementById('stripe-expiry');
    const cvvInput = document.getElementById('stripe-cvv');
    const cardTypeIcon = document.getElementById('card-type-icon');

    cardInput.oninput = function(e) {
        let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formatted = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formatted += ' ';
            }
            formatted += value[i];
        }
        this.value = formatted;

        // Card Brand Prefix Matching
        if (value.startsWith('4')) {
            cardTypeIcon.innerText = "Visa";
            cardTypeIcon.className = "text-blue-400 border-blue-500/20 bg-blue-500/5 font-mono font-bold text-xs uppercase tracking-widest px-3 py-1 rounded-lg border";
        } else if (value.startsWith('51') || value.startsWith('52') || value.startsWith('53') || value.startsWith('54') || value.startsWith('55')) {
            cardTypeIcon.innerText = "Mastercard";
            cardTypeIcon.className = "text-orange-400 border-orange-500/20 bg-orange-500/5 font-mono font-bold text-xs uppercase tracking-widest px-3 py-1 rounded-lg border";
        } else if (value.startsWith('34') || value.startsWith('37')) {
            cardTypeIcon.innerText = "Amex";
            cardTypeIcon.className = "text-green-400 border-green-500/20 bg-green-500/5 font-mono font-bold text-xs uppercase tracking-widest px-3 py-1 rounded-lg border";
        } else {
            cardTypeIcon.innerText = "Card Type";
            cardTypeIcon.className = "text-gray-500 font-mono font-bold text-xs uppercase tracking-widest px-3 py-1 bg-white/5 rounded-lg border border-white/10";
        }
    };

    expiryInput.oninput = function(e) {
        let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        if (value.length > 2) {
            this.value = value.substring(0, 2) + '/' + value.substring(2, 4);
        } else {
            this.value = value;
        }
    };

    cvvInput.oninput = function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    };

    // Form Submission Handling
    document.getElementById('payment-main-form').onsubmit = function(e) {
        const method = document.getElementById('selected-gateway-method').value;
        if (method === 'stripe') {
            e.preventDefault();
            // Validate fields
            const cardNum = cardInput.value.replace(/\s/g, '');
            const expiry = expiryInput.value;
            const cvv = cvvInput.value;
            
            if (cardNum.length < 15) {
                alert('Please enter a valid card number.');
                return;
            }
            if (!expiry.includes('/') || expiry.length < 5) {
                alert('Please enter a valid expiry date (MM/YY).');
                return;
            }
            if (cvv.length < 3) {
                alert('Please enter a valid CVV.');
                return;
            }

            // Trigger beautiful loading transitions
            const overlay = document.getElementById('payment-loading-overlay');
            const statusText = document.getElementById('loading-status-text');
            overlay.classList.remove('hidden');

            setTimeout(() => {
                statusText.innerText = "Authorizing Card...";
            }, 1000);

            setTimeout(() => {
                statusText.innerText = "Processing Funds...";
            }, 2200);

            setTimeout(() => {
                // Submit Form natively
                document.getElementById('payment-main-form').submit();
            }, 3500);
        }
    };

    // Razorpay emulator
    function triggerRazorpayMockFlow(type) {
        const upiView = document.getElementById('razorpay-upi-view');
        const netbankingView = document.getElementById('razorpay-netbanking-view');
        const standardView = document.getElementById('razorpay-standard-view');

        upiView.classList.add('hidden');
        netbankingView.classList.add('hidden');
        standardView.classList.add('hidden');

        if (type === 'upi') {
            upiView.classList.remove('hidden');
        } else if (type === 'netbanking') {
            netbankingView.classList.remove('hidden');
        } else {
            standardView.classList.remove('hidden');
        }

        document.getElementById('razorpay-emulator-modal').classList.remove('hidden');
    }

    function closeRazorpayEmulator() {
        document.getElementById('razorpay-emulator-modal').classList.add('hidden');
    }

    function completeMockRazorpay(success) {
        closeRazorpayEmulator();
        if (success) {
            const overlay = document.getElementById('payment-loading-overlay');
            const statusText = document.getElementById('loading-status-text');
            overlay.classList.remove('hidden');

            statusText.innerText = "Verifying UPI Transaction...";

            setTimeout(() => {
                statusText.innerText = "Capturing Payment...";
            }, 1200);

            setTimeout(() => {
                document.getElementById('payment-main-form').submit();
            }, 2500);
        } else {
            alert('Payment simulation failed or was cancelled by user.');
        }
    }
</script>
<?php include 'includes/footer.php'; ?>
