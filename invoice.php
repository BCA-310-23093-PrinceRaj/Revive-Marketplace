<?php 
require_once 'config/db.php';
session_start();

if(!isset($_SESSION['user_id'])) exit('Unauthorized');

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Fetch order details
$sql = "SELECT o.*, p.title, p.brand, u.name as buyer_name, u.email as buyer_email, s.name as seller_name 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u ON o.buyer_id = u.id 
        JOIN users s ON p.seller_id = s.id
        WHERE o.id = $order_id AND (o.buyer_id = $user_id OR p.seller_id = $user_id)";

$result = $conn->query($sql);
if($result->num_rows === 0) exit('Invoice not found');

$order = $result->fetch_assoc();
$is_raw = isset($_GET['raw']) && $_GET['raw'] === 'true';
?>

<?php if (!$is_raw): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $order['id']; ?> - REVIVE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include html2pdf library for 1-click downloads -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; color: black; }
        }
    </style>
</head>
<body class="bg-gray-100 p-10">
<?php endif; ?>

    <div id="invoice-box" class="max-w-3xl mx-auto bg-white p-12 shadow-xl rounded-xl border border-gray-200">
        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-gray-100 pb-8 mb-8">
            <div>
                <h1 class="text-4xl font-black tracking-tighter text-black mb-1">REVIVE.</h1>
                <p class="text-gray-500 text-sm italic">Premium Second-Hand Fashion</p>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold text-gray-800">INVOICE</h2>
                <p class="text-gray-500">#INV-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 gap-12 mb-12">
            <div>
                <h3 class="text-xs uppercase tracking-widest text-gray-400 font-bold mb-3">Billed To:</h3>
                <p class="font-bold text-lg"><?php echo $order['buyer_name']; ?></p>
                <p class="text-gray-600"><?php echo $order['buyer_email']; ?></p>
                <p class="text-gray-600 mt-2 text-sm"><?php echo $order['shipping_address']; ?></p>
                <p class="text-gray-600 text-sm">Phone: <?php echo $order['phone']; ?></p>
            </div>
            <div class="text-right">
                <h3 class="text-xs uppercase tracking-widest text-gray-400 font-bold mb-3">Order Details:</h3>
                <p class="text-gray-600"><span class="font-bold">Date:</span> <?php echo date('d M, Y', strtotime($order['created_at'])); ?></p>
                <p class="text-gray-600"><span class="font-bold">Status:</span> <?php echo strtoupper($order['status']); ?></p>
                <p class="text-gray-600"><span class="font-bold">Seller:</span> <?php echo $order['seller_name']; ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full mb-12">
            <thead>
                <tr class="border-b-2 border-gray-100 text-left">
                    <th class="py-4 font-bold text-gray-800">Description</th>
                    <th class="py-4 text-right font-bold text-gray-800">Price</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-50">
                    <td class="py-6">
                        <p class="font-bold text-lg text-gray-900"><?php echo $order['title']; ?></p>
                        <p class="text-gray-500 text-sm"><?php echo $order['brand']; ?></p>
                    </td>
                    <td class="py-6 text-right font-bold text-lg">₹<?php echo number_format($order['amount'], 0); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="flex justify-end border-t-2 border-gray-100 pt-8">
            <div class="w-64">
                <div class="flex justify-between mb-4">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-bold">₹<?php echo number_format($order['amount'], 0); ?></span>
                </div>
                <div class="flex justify-between mb-4">
                    <span class="text-gray-500">Shipping</span>
                    <span class="text-green-600 font-bold">FREE</span>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-4">
                    <span class="text-xl font-bold">Total</span>
                    <span class="text-2xl font-black text-black">₹<?php echo number_format($order['amount'], 0); ?></span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-20 text-center border-t border-gray-100 pt-8">
            <p class="text-gray-400 text-sm mb-1">Thank you for shopping sustainably with Revive.</p>
            <p class="text-gray-300 text-[10px] uppercase tracking-widest">This is a computer-generated invoice.</p>
        </div>
    </div>

<?php if (!$is_raw): ?>
    <!-- Actions -->
    <div class="max-w-3xl mx-auto mt-8 flex justify-between items-center no-print">
        <a href="my_orders.php" class="text-gray-500 hover:text-black font-bold">&larr; Back to Orders</a>
        <button id="download-btn" onclick="downloadInvoice()" class="bg-black text-white px-10 py-4 rounded-full font-bold shadow-lg hover:scale-105 transition">
            Download PDF
        </button>
    </div>

    <script>
    function downloadInvoice() {
        const btn = document.getElementById('download-btn');
        const oldText = btn.innerText;
        btn.innerText = 'Generating PDF...';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        
        const element = document.getElementById('invoice-box');
        const opt = {
            margin:       [10, 10, 10, 10], // top, left, bottom, right
            filename:     'Invoice_REV-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        // Delay to allow UI to update the button text
        setTimeout(() => {
            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerText = oldText;
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }, 100);
    }
    </script>

</body>
</html>
<?php endif; ?>
