<?php 
require_once 'config/db.php';
include 'includes/header.php'; 

$seller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$seller = $conn->query("SELECT u.*, 
        (SELECT AVG(rating) FROM reviews r JOIN products p ON r.product_id = p.id WHERE p.seller_id = u.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews r JOIN products p ON r.product_id = p.id WHERE p.seller_id = u.id) as review_count,
        (SELECT COUNT(*) FROM products WHERE seller_id = u.id AND status = 'sold') as items_sold,
        (SELECT COUNT(*) FROM products WHERE seller_id = u.id AND status = 'available') as active_listings
        FROM users u WHERE u.id = $seller_id AND u.role IN ('seller', 'admin')")->fetch_assoc();

if(!$seller) {
    echo "<section class='py-40 text-center px-6'><h1 class='text-4xl font-bold mb-4'>Seller Not Found</h1><a href='index.php' class='neon-btn px-8 py-3 rounded-xl font-bold'>Back to Home</a></section>";
    include 'includes/footer.php';
    exit();
}
?>

<section class="py-24 px-6 max-w-7xl mx-auto">
    <!-- Seller Header -->
    <div class="bg-white/[0.02] border border-white/5 rounded-[3rem] p-12 mb-20 relative overflow-hidden" data-aos="fade-up">
        <div class="absolute top-0 right-0 w-96 h-96 bg-[#bcff00]/5 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/2"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start space-y-8 md:space-y-0 md:space-x-12">
            <div class="w-32 h-32 rounded-full bg-[#bcff00]/10 flex items-center justify-center text-[#bcff00] font-bold text-5xl border-2 border-[#bcff00]/20 shadow-2xl">
                <?php echo strtoupper(substr($seller['name'], 0, 1)); ?>
            </div>
            
            <div class="flex-1 text-center md:text-left">
                <div class="flex flex-col md:flex-row items-center md:items-end space-y-2 md:space-y-0 md:space-x-4 mb-4">
                    <h1 class="text-5xl font-bold tracking-tight"><?php echo $seller['name']; ?></h1>
                    <span class="bg-[#bcff00]/20 text-[#bcff00] text-[10px] font-bold uppercase tracking-widest px-4 py-1.5 rounded-full border border-[#bcff00]/20 mb-1">
                        Verified Seller
                    </span>
                </div>
                
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-6 text-gray-400 font-medium mb-8">
                    <div class="flex items-center">
                        <span class="text-[#bcff00] font-bold text-xl mr-2"><?php echo round($seller['avg_rating'] ?: 0, 1); ?> ★</span>
                        <span class="text-xs">(<?php echo $seller['review_count']; ?> Feedback)</span>
                    </div>
                    <div class="w-1 h-1 bg-white/20 rounded-full hidden md:block"></div>
                    <p class="text-sm">Member since <?php echo date('M Y', strtotime($seller['created_at'])); ?></p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-2xl">
                    <div class="bg-white/5 p-6 rounded-3xl border border-white/10">
                        <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mb-1">Items Sold</p>
                        <p class="text-2xl font-bold"><?php echo $seller['items_sold']; ?></p>
                    </div>
                    <div class="bg-white/5 p-6 rounded-3xl border border-white/10">
                        <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mb-1">Active Now</p>
                        <p class="text-2xl font-bold text-[#bcff00]"><?php echo $seller['active_listings']; ?></p>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-auto">
                <a href="chat.php?user_id=<?php echo $seller_id; ?>" class="neon-btn w-full block text-center font-bold px-10 py-5 rounded-2xl shadow-xl shadow-[#bcff00]/10">
                    Message Seller
                </a>
            </div>
        </div>
    </div>

    <!-- Listings & Reviews Tabs -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-16">
        <!-- Active Listings -->
        <div class="lg:col-span-2">
            <h2 class="text-3xl font-bold mb-10 flex items-center">
                Active <span class="neon-text italic ml-3">Listings</span>
            </h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                <?php
                $prods = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.seller_id = $seller_id AND p.status = 'available' ORDER BY p.created_at DESC");
                if($prods->num_rows > 0):
                    while($product = $prods->fetch_assoc()):
                ?>
                    <div class="group" data-aos="fade-up">
                        <div class="relative aspect-[3/4] overflow-hidden rounded-3xl bg-white/5 border border-white/10 mb-6">
                            <img src="assets/img/products/<?php echo $product['images']; ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition duration-700">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="bg-white text-black font-bold px-8 py-3 rounded-full text-sm">View Item</a>
                            </div>
                        </div>
                        <h3 class="font-bold text-xl mb-1"><?php echo $product['title']; ?></h3>
                        <p class="font-bold text-[#bcff00] text-lg">₹<?php echo number_format($product['price'], 0); ?></p>
                    </div>
                <?php 
                    endwhile;
                else:
                    echo "<p class='text-gray-500 italic'>No active listings right now.</p>";
                endif;
                ?>
            </div>
        </div>

        <!-- Recent Feedback -->
        <div class="lg:col-span-1">
            <h2 class="text-3xl font-bold mb-10">Recent <span class="neon-text italic">Feedback</span></h2>
            <div class="space-y-6">
                <?php
                $revs = $conn->query("SELECT r.*, u.name as user_name, p.title as product_title 
                                    FROM reviews r 
                                    JOIN users u ON r.user_id = u.id 
                                    JOIN products p ON r.product_id = p.id 
                                    WHERE p.seller_id = $seller_id 
                                    ORDER BY r.created_at DESC LIMIT 5");
                if($revs->num_rows > 0):
                    while($rev = $revs->fetch_assoc()):
                ?>
                    <div class="bg-white/5 border border-white/10 p-8 rounded-3xl">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center font-bold text-[10px]">
                                    <?php echo strtoupper(substr($rev['user_name'], 0, 1)); ?>
                                </div>
                                <span class="font-bold text-sm"><?php echo $rev['user_name']; ?></span>
                            </div>
                            <span class="text-[#bcff00] text-xs font-bold"><?php echo $rev['rating']; ?> ★</span>
                        </div>
                        <p class="text-gray-400 text-sm italic mb-4 leading-relaxed">"<?php echo $rev['comment']; ?>"</p>
                        <p class="text-[9px] uppercase tracking-widest text-gray-600 font-bold">On: <?php echo $rev['product_title']; ?></p>
                    </div>
                <?php 
                    endwhile;
                else:
                    echo "<p class='text-gray-500 italic'>No feedback yet.</p>";
                endif;
                ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
