<?php 
require_once 'config/db.php';
include 'includes/header.php'; 

// Stats for banner
$total_products = $conn->query("SELECT COUNT(*) as c FROM products WHERE status = 'available'")->fetch_assoc()['c'] ?? 0;
$total_sellers  = $conn->query("SELECT COUNT(DISTINCT seller_id) as c FROM products")->fetch_assoc()['c'] ?? 0;
$total_sold     = $conn->query("SELECT COUNT(*) as c FROM products WHERE status = 'sold'")->fetch_assoc()['c'] ?? 0;
$waste_saved    = round($total_sold * 1.5, 1);
?>

<?php if(isset($_GET['success']) && $_GET['success'] === 'logged_in'): ?>
<div id="login-toast" class="fixed top-20 right-6 z-[999] bg-[#bcff00] text-black px-6 py-4 rounded-2xl font-bold shadow-2xl shadow-[#bcff00]/20 flex items-center space-x-3 transition-all duration-500">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Welcome back to Revive! 👋</span>
</div>
<script>
    setTimeout(() => {
        const toast = document.getElementById('login-toast');
        if (toast) { toast.style.opacity = '0'; toast.style.transform = 'translateY(-20px)'; setTimeout(() => toast.remove(), 500); }
    }, 3000);
</script>
<?php endif; ?>

<!-- Hero Section -->
<section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden px-6">
    <!-- Abstract Background Elements -->
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-[#bcff00]/10 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-purple-600/10 rounded-full blur-[120px]"></div>

    <div class="max-w-5xl mx-auto text-center relative z-10" data-aos="fade-up">
        <div class="inline-flex items-center space-x-2 bg-[#bcff00]/10 border border-[#bcff00]/20 text-[#bcff00] text-xs font-bold uppercase tracking-widest px-5 py-2 rounded-full mb-8">
            <span class="w-2 h-2 bg-[#bcff00] rounded-full animate-pulse"></span>
            <span>India's Sustainable Fashion Platform</span>
        </div>
        <h1 class="text-6xl md:text-8xl font-bold tracking-tighter leading-none mb-8">
            RETHINK<br>
            YOUR <span class="neon-text italic">STYLE.</span>
        </h1>
        <p class="text-xl text-gray-400 max-w-2xl mx-auto mb-12">
            The premium destination to buy and sell pre-loved luxury and streetwear. 
            Sustainable, fast, and secure.
        </p>
        <div class="flex flex-col md:flex-row items-center justify-center gap-6">
            <a href="shop.php?view=products" class="neon-btn text-lg font-bold px-10 py-4 rounded-full w-full md:w-auto text-center">
                Start Shopping
            </a>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'add_product.php' : 'login.php'; ?>" class="text-lg font-bold border border-white/20 px-10 py-4 rounded-full hover:bg-white/5 transition w-full md:w-auto text-center">
                List an Item
            </a>
        </div>
    </div>
</section>

<!-- Stats Banner -->
<section class="py-6 px-6 max-w-7xl mx-auto" data-aos="fade-up">
    <div class="bg-white/5 border border-white/10 rounded-[2rem] px-8 py-6 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        <div>
            <p class="text-3xl md:text-4xl font-black text-[#bcff00] italic"><?php echo number_format($total_products); ?>+</p>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">Live Listings</p>
        </div>
        <div>
            <p class="text-3xl md:text-4xl font-black text-white italic"><?php echo number_format($total_sellers); ?>+</p>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">Active Sellers</p>
        </div>
        <div>
            <p class="text-3xl md:text-4xl font-black text-purple-400 italic"><?php echo number_format($total_sold); ?>+</p>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">Items Sold</p>
        </div>
        <div>
            <p class="text-3xl md:text-4xl font-black text-emerald-400 italic"><?php echo $waste_saved; ?>kg</p>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">Textile Waste Saved</p>
        </div>
    </div>
</section>

<!-- Latest Drops Section -->
<section class="py-24 px-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-end mb-12">
        <div data-aos="fade-right">
            <h2 class="text-4xl font-bold tracking-tight mb-2">Latest Drops</h2>
            <p class="text-gray-400">Recently added pieces from our community.</p>
        </div>
        <a href="shop.php?view=products" class="text-[#bcff00] font-semibold hover:underline flex items-center space-x-1 group">
            <span>View all</span>
            <span class="group-hover:translate-x-1 transition">&rarr;</span>
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php
        $user_id = $_SESSION['user_id'] ?? 0;
        $products = $conn->query("SELECT p.*, c.name as category_name, 
                                 (SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = p.id) as is_wishlisted
                                 FROM products p JOIN categories c ON p.category_id = c.id 
                                 WHERE p.status = 'available' ORDER BY p.created_at DESC LIMIT 8");
        if($products->num_rows > 0):
            while($product = $products->fetch_assoc()):
        ?>
            <div class="group" data-aos="fade-up">
                <div class="relative aspect-square overflow-hidden rounded-2xl bg-white/5 border border-white/10 mb-4">
                    <img src="assets/img/products/<?php echo htmlspecialchars($product['images']); ?>" 
                         onerror="this.style.display='none'"
                         class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    
                    <div class="absolute top-4 left-4">
                        <span class="bg-black/60 backdrop-blur-md text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full border border-white/10">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                    </div>

                    <!-- Wishlist Button -->
                    <button onclick="toggleWishlist(<?php echo $product['id']; ?>, this)" 
                            class="absolute top-4 right-4 z-20 bg-black/40 backdrop-blur-md p-2 rounded-full border border-white/10 hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 <?php echo $product['is_wishlisted'] ? 'fill-red-500 text-red-500' : 'text-gray-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>

                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="bg-white text-black font-bold px-6 py-2 rounded-full text-sm transform translate-y-4 group-hover:translate-y-0 transition duration-300">
                            Quick View
                        </a>
                    </div>
                </div>
                <div class="px-1">
                    <h3 class="font-bold text-base group-hover:text-[#bcff00] transition truncate mb-1"><?php echo htmlspecialchars($product['title']); ?></h3>
                    <p class="text-gray-500 text-xs mb-2"><?php echo htmlspecialchars($product['brand']); ?> • Size <?php echo htmlspecialchars($product['size']); ?></p>
                    <p class="font-bold text-lg text-[#bcff00]">₹<?php echo number_format($product['price'], 0); ?></p>
                </div>
            </div>
        <?php 
            endwhile;
        else:
            echo "<div class='col-span-full py-16 text-center border border-dashed border-white/10 rounded-3xl'>
                    <p class='text-gray-500 mb-4'>No products listed yet.</p>
                    <a href='add_product.php' class='text-[#bcff00] font-bold hover:underline'>Be the first to list! &rarr;</a>
                  </div>";
        endif; 
        ?>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-24 px-6 max-w-7xl mx-auto" data-aos="fade-up">
    <div class="text-center mb-16">
        <h2 class="text-4xl font-bold tracking-tight mb-3">How <span class="neon-text italic">Revive</span> Works</h2>
        <p class="text-gray-400 max-w-xl mx-auto">Three simple steps to give pre-loved fashion a second life.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
        <!-- Connector line (desktop only) -->
        <div class="hidden md:block absolute top-16 left-1/6 right-1/6 h-px bg-gradient-to-r from-transparent via-[#bcff00]/30 to-transparent"></div>

        <!-- Step 1 -->
        <div class="bg-white/5 border border-white/10 rounded-[2rem] p-10 text-center group hover:border-[#bcff00]/40 hover:bg-white/[0.07] transition duration-500" data-aos="fade-up" data-aos-delay="0">
            <div class="w-16 h-16 bg-[#bcff00]/10 border border-[#bcff00]/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 group-hover:bg-[#bcff00]/20 transition duration-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div class="text-[#bcff00] text-xs font-bold uppercase tracking-widest mb-3">Step 01</div>
            <h3 class="text-2xl font-bold mb-3">List Your Item</h3>
            <p class="text-gray-400 leading-relaxed">Click photos, set your price, and list in under 2 minutes. Your item goes live after admin approval.</p>
        </div>

        <!-- Step 2 -->
        <div class="bg-white/5 border border-white/10 rounded-[2rem] p-10 text-center group hover:border-[#bcff00]/40 hover:bg-white/[0.07] transition duration-500" data-aos="fade-up" data-aos-delay="100">
            <div class="w-16 h-16 bg-purple-500/10 border border-purple-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 group-hover:bg-purple-500/20 transition duration-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
            <div class="text-purple-400 text-xs font-bold uppercase tracking-widest mb-3">Step 02</div>
            <h3 class="text-2xl font-bold mb-3">Connect & Buy</h3>
            <p class="text-gray-400 leading-relaxed">Buyers browse, chat with sellers, and purchase securely. Questions? Our built-in chat handles everything.</p>
        </div>

        <!-- Step 3 -->
        <div class="bg-white/5 border border-white/10 rounded-[2rem] p-10 text-center group hover:border-[#bcff00]/40 hover:bg-white/[0.07] transition duration-500" data-aos="fade-up" data-aos-delay="200">
            <div class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 group-hover:bg-emerald-500/20 transition duration-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
            </div>
            <div class="text-emerald-400 text-xs font-bold uppercase tracking-widest mb-3">Step 03</div>
            <h3 class="text-2xl font-bold mb-3">Revive & Earn</h3>
            <p class="text-gray-400 leading-relaxed">Ship the item, get paid, and track everything on your dashboard. Fashion gets a second life, you earn cash.</p>
        </div>
    </div>

    <div class="text-center mt-12">
        <a href="<?php echo isset($_SESSION['user_id']) ? 'add_product.php' : 'register.php'; ?>" class="neon-btn font-bold px-10 py-4 rounded-full text-lg inline-block">
            Start Selling Today &rarr;
        </a>
    </div>
</section>

<!-- Shop by Category -->
<section class="py-24 px-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-end mb-12" data-aos="fade-down">
        <div>
            <h2 class="text-4xl font-bold tracking-tight mb-2">Shop by Category</h2>
            <p class="text-gray-400 text-sm">Explore pieces curated by category and style.</p>
        </div>
        <a href="shop.php" class="text-[#bcff00] font-semibold hover:underline flex items-center space-x-1 group">
            <span>View all</span>
            <span class="group-hover:translate-x-1 transition">&rarr;</span>
        </a>
    </div>

    <!-- Scrolling/Wrapping Category Badges Grid -->
    <div class="flex flex-nowrap md:flex-wrap items-center justify-start md:justify-center gap-8 overflow-x-auto md:overflow-x-visible pb-6 md:pb-0 px-4 custom-scroll" data-aos="fade-up">
        <?php
        $cats = $conn->query("SELECT * FROM categories");
        if ($cats->num_rows > 0):
            while ($cat = $cats->fetch_assoc()):
                $img_path = "assets/img/cat_" . $cat['slug'] . "_cutout.png";
                if (!file_exists($img_path)) {
                    $img_path = "assets/img/cat_" . $cat['slug'] . ".png";
                }
        ?>
            <a href="shop.php?categories[]=<?php echo $cat['id']; ?>" class="flex flex-col items-center group cursor-pointer w-24 sm:w-28 flex-shrink-0">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center p-3 relative transition-all duration-500 group-hover:border-[#bcff00] group-hover:bg-white/10 group-hover:shadow-[0_0_20px_rgba(188,255,0,0.15)] group-hover:-translate-y-2">
                    <img src="<?php echo $img_path; ?>" 
                         alt="<?php echo htmlspecialchars($cat['name']); ?>" 
                         onerror="this.style.display='none'"
                         class="w-16 h-16 sm:w-20 sm:h-20 object-contain group-hover:scale-110 transition duration-500">
                </div>
                <span class="mt-4 text-xs sm:text-sm font-bold text-gray-400 group-hover:text-white transition duration-300">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </span>
            </a>
        <?php 
            endwhile;
        endif; 
        ?>
    </div>
</section>

<!-- Bottom CTA Banner -->
<section class="py-16 px-6 max-w-7xl mx-auto" data-aos="fade-up">
    <div class="bg-[#bcff00] text-black rounded-[3rem] p-12 md:p-16 text-center relative overflow-hidden">
        <div class="absolute inset-0 opacity-5">
            <div class="absolute top-0 left-0 w-64 h-64 bg-black rounded-full -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-black rounded-full translate-x-1/3 translate-y-1/3"></div>
        </div>
        <div class="relative z-10">
            <h2 class="text-4xl md:text-5xl font-black tracking-tighter mb-4 italic">Your Wardrobe.<br>Someone's Treasure.</h2>
            <p class="text-black/60 text-lg mb-8 max-w-xl mx-auto">Join thousands of fashion lovers giving pre-loved pieces a second life.</p>
            <div class="flex flex-col md:flex-row gap-4 justify-center">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'add_product.php' : 'register.php'; ?>" class="bg-black text-white font-bold px-10 py-4 rounded-full text-lg hover:scale-105 transition">
                    Start Selling
                </a>
                <a href="shop.php" class="bg-black/10 text-black font-bold px-10 py-4 rounded-full text-lg hover:bg-black/20 transition border border-black/10">
                    Browse Shop
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
