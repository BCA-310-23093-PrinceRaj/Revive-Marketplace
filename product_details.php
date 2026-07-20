<?php 
require_once 'config/db.php';
include 'includes/header.php'; 

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT p.*, c.name as category_name, u.name as seller_name FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id WHERE p.id = $id");

if($result->num_rows === 0) {
    echo "<section class='py-40 text-center'><h1 class='text-4xl font-bold'>Product not found.</h1><a href='shop.php' class='text-[#bcff00] mt-4 block'>Back to Shop</a></section>";
    include 'includes/footer.php';
    exit();
}

$product = $result->fetch_assoc();
?>

<section class="py-24 px-6 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
        <!-- Product Gallery -->
        <div data-aos="fade-right">
            <?php
            $images_res = $conn->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY is_primary DESC");
            $images = [];
            while($img = $images_res->fetch_assoc()) $images[] = $img['image_path'];
            
            $primary_image = !empty($images) ? $images[0] : $product['images'];
            if(empty($images)) $images[] = $product['images'];
            ?>
            <div class="relative aspect-square rounded-[3rem] overflow-hidden bg-white/5 border border-white/10 mb-6 group">
                <img id="main-product-image" src="assets/img/products/<?php echo $primary_image; ?>" 
                     class="absolute inset-0 w-full h-full object-cover transition duration-700">
                
                <!-- Zoom Overlay Icon -->
                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition flex items-center justify-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <?php if(count($images) > 1): ?>
            <div class="flex space-x-4 overflow-x-auto pb-4 scrollbar-hide">
                <?php foreach($images as $img_path): ?>
                    <button onclick="document.getElementById('main-product-image').src='assets/img/products/<?php echo $img_path; ?>'" 
                            class="w-24 h-24 rounded-2xl overflow-hidden flex-shrink-0 border-2 border-transparent hover:border-[#bcff00] transition active:scale-95">
                        <img src="assets/img/products/<?php echo $img_path; ?>" class="w-full h-full object-cover">
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div data-aos="fade-left">
            <div class="mb-8">
                <div class="flex items-center space-x-4 mb-6">
                    <span class="bg-[#bcff00]/10 text-[#bcff00] text-xs font-bold uppercase tracking-[0.2em] px-4 py-2 rounded-full border border-[#bcff00]/20">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </span>
                    <span class="text-gray-500 text-sm italic"><?php echo htmlspecialchars($product['product_condition']); ?> Condition</span>
                </div>
                
                <h1 class="text-5xl md:text-6xl font-bold tracking-tighter mb-4"><?php echo htmlspecialchars($product['title']); ?></h1>
                <p class="text-3xl font-bold text-[#bcff00] italic mb-8">₹<?php echo number_format($product['price'], 0); ?></p>
                
                <p class="text-gray-400 leading-relaxed mb-10">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </p>

                <!-- Seller Info Card -->
                <?php
                $seller_id = $product['seller_id'];
                $seller_stats = $conn->query("SELECT 
                    u.name, u.created_at,
                    (SELECT AVG(rating) FROM reviews r JOIN products p2 ON r.product_id = p2.id WHERE p2.seller_id = $seller_id) as avg_rating,
                    (SELECT COUNT(*) FROM reviews r JOIN products p2 ON r.product_id = p2.id WHERE p2.seller_id = $seller_id) as total_reviews
                    FROM users u WHERE u.id = $seller_id")->fetch_assoc();
                ?>
                <div class="bg-white/5 border border-white/10 rounded-3xl p-8 mb-12 group hover:border-[#bcff00]/50 transition duration-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-14 h-14 rounded-full bg-[#bcff00]/10 flex items-center justify-center text-[#bcff00] font-bold text-xl border border-[#bcff00]/20">
                                <?php echo strtoupper(substr($seller_stats['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-widest font-bold mb-1">Sold By</p>
                                <h4 class="text-xl font-bold"><?php echo $seller_stats['name']; ?></h4>
                                <div class="flex items-center mt-1 text-[#bcff00]">
                                    <?php 
                                    $rating = round($seller_stats['avg_rating'] ?: 0, 1);
                                    echo "<span class='text-sm font-bold mr-2'>$rating ★</span>";
                                    echo "<span class='text-gray-500 text-xs font-normal'>(" . $seller_stats['total_reviews'] . " reviews)</span>";
                                    ?>
                                </div>
                            </div>
                        </div>
                        <a href="seller_profile.php?id=<?php echo $seller_id; ?>" class="px-6 py-3 rounded-xl border border-white/10 text-sm font-bold hover:bg-white/5 transition">
                            View Profile
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-12">
                    <div class="bg-white/5 border border-white/10 p-6 rounded-2xl">
                        <p class="text-gray-500 uppercase tracking-widest text-[10px] font-bold mb-1">Brand</p>
                        <p class="font-bold text-white text-xl"><?php echo htmlspecialchars($product['brand']); ?></p>
                    </div>
                    <div class="bg-white/5 p-6 rounded-2xl border border-white/10">
                        <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">Size</p>
                        <p class="text-white font-bold text-xl"><?php echo htmlspecialchars($product['size']); ?></p>
                    </div>
                </div>

                <?php if(!empty($product['usage_info'])): ?>
                <div class="mb-12 bg-[#bcff00]/5 border border-[#bcff00]/20 p-6 rounded-2xl" data-aos="fade-up">
                    <p class="text-[#bcff00] uppercase tracking-widest text-[10px] font-bold mb-2">Usage History / Wear Detail</p>
                    <p class="text-white italic">"<?php echo htmlspecialchars($product['usage_info']); ?>"</p>
                </div>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row gap-4 mb-12">
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $product['seller_id']): ?>
                        <div class="w-full bg-white/5 border border-white/10 font-bold py-5 rounded-2xl text-center text-gray-500 italic">
                            This is your own product. You cannot buy or chat with yourself.
                        </div>
                    <?php else: ?>
                        <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                class="neon-btn flex-1 font-bold py-5 rounded-2xl text-lg flex items-center justify-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>Add to Bag</span>
                        </button>
                        <a href="chat.php?user_id=<?php echo $product['seller_id']; ?>&product_id=<?php echo $product['id']; ?>" 
                           class="flex-1 bg-white/5 border border-white/10 font-bold py-5 rounded-2xl text-lg flex items-center justify-center space-x-2 hover:bg-white/10 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                            <span>Chat</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Seller Info -->
                <div class="pt-8 border-t border-white/10 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-[#bcff00]/10 flex items-center justify-center text-[#bcff00] font-bold">
                            <?php echo strtoupper(substr($product['seller_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-gray-500 text-[10px] uppercase tracking-widest">Seller</p>
                            <p class="font-bold text-white"><?php echo htmlspecialchars($product['seller_name']); ?></p>
                        </div>
                    </div>
            </div>
        </div>
    </div> <!-- Closes fade-left column -->
</div> <!-- Closes two-column product grid -->

    <!-- Customer Reviews Section -->
    <div class="mt-32 pt-20 border-t border-white/5" data-aos="fade-up">
        <div class="flex flex-col md:flex-row justify-between items-start mb-16">
            <div>
                <h2 class="text-4xl font-bold mb-4">Customer <span class="neon-text">Reviews</span></h2>
                <p class="text-gray-400">What others think about this piece.</p>
            </div>
            
            <?php 
            $can_review = false;
            if (isset($_SESSION['user_id'])) {
                $uid = (int)$_SESSION['user_id'];
                $pid = (int)$product['id'];
                // User must have bought it & order completed
                $check_order = $conn->query("SELECT id FROM orders WHERE buyer_id = $uid AND product_id = $pid AND status = 'completed'");
                // User must not have reviewed it already
                $check_review = $conn->query("SELECT id FROM reviews WHERE user_id = $uid AND product_id = $pid");
                
                if ($check_order->num_rows > 0 && $check_review->num_rows == 0) {
                    $can_review = true;
                }
            }
            
            if($can_review): 
            ?>
            <button onclick="document.getElementById('review-form-box').classList.toggle('hidden')" 
                    class="mt-6 md:mt-0 px-8 py-3 rounded-full border border-[#bcff00] text-[#bcff00] font-bold hover:bg-[#bcff00]/10 transition">
                Write a Review
            </button>
            <?php endif; ?>
        </div>

        <?php if($can_review): ?>
        <!-- Review Submission Form (Hidden by default) -->
        <div id="review-form-box" class="hidden mb-20 bg-white/5 p-10 rounded-[2.5rem] border border-white/10 max-w-2xl">
            <form action="actions/review_action.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <div class="mb-8">
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-4">Your Rating</label>
                    <div class="flex space-x-4">
                        <?php for($i=1; $i<=5; $i++): ?>
                        <label class="cursor-pointer group">
                            <input type="radio" name="rating" value="<?php echo $i; ?>" class="hidden peer" <?php echo $i===5 ? 'checked' : ''; ?>>
                            <div class="w-12 h-12 rounded-xl border border-white/10 flex items-center justify-center peer-checked:bg-[#bcff00] peer-checked:text-black hover:border-[#bcff00] transition font-bold"><?php echo $i; ?></div>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-3">Your Thoughts</label>
                    <textarea name="comment" required rows="4" placeholder="Tell us about the quality, fit, and overall experience..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white"></textarea>
                </div>

                <button type="submit" class="neon-btn font-bold px-10 py-4 rounded-2xl shadow-lg">Post Review</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php
            $reviews = $conn->query("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = $id ORDER BY r.created_at DESC");
            if($reviews->num_rows > 0):
                while($rev = $reviews->fetch_assoc()):
            ?>
                <div class="bg-white/5 p-8 rounded-[2rem] border border-white/10">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center font-bold text-xs">
                                <?php echo strtoupper(substr($rev['user_name'], 0, 1)); ?>
                            </div>
                            <span class="font-bold"><?php echo $rev['user_name']; ?></span>
                        </div>
                        <div class="flex text-[#bcff00]">
                            <?php for($k=0; $k<$rev['rating']; $k++) echo '★'; ?>
                        </div>
                    </div>
                    <p class="text-gray-400 italic">"<?php echo $rev['comment']; ?>"</p>
                    <p class="text-[10px] text-gray-600 mt-4 uppercase tracking-widest"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></p>
                </div>
            <?php 
                endwhile;
            else:
                echo "<p class='text-gray-500 col-span-full italic'>Be the first to review this piece!</p>";
            endif;
            ?>
        </div>
    </div>
        <h2 class="text-3xl font-bold mb-12">You might also <span class="neon-text italic">like</span></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php
            $cat_id = $product['category_id'];
            $current_id = $product['id'];
            $related = $conn->query("SELECT * FROM products WHERE category_id = $cat_id AND id != $current_id AND status = 'available' LIMIT 4");
            
            if($related->num_rows > 0):
                while($rp = $related->fetch_assoc()):
            ?>
                <a href="product_details.php?id=<?php echo $rp['id']; ?>" class="group">
                    <div class="aspect-[3/4] rounded-2xl overflow-hidden bg-white/5 border border-white/10 mb-4">
                        <img src="assets/img/products/<?php echo $rp['images']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <h4 class="font-bold group-hover:text-[#bcff00] transition"><?php echo $rp['title']; ?></h4>
                    <p class="text-[#bcff00] font-bold">₹<?php echo number_format($rp['price'], 0); ?></p>
                </a>
            <?php 
                endwhile;
            else:
                echo "<p class='text-gray-500 italic'>No related products found yet.</p>";
            endif;
            ?>
        </div>
</section>

<?php include 'includes/footer.php'; ?>
