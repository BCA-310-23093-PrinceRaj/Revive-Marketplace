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

<section class="py-24 px-6 max-w-7xl mx-auto">
    <div class="mb-16" data-aos="fade-right">
        <h1 class="text-5xl font-bold tracking-tight mb-4">My <span class="neon-text italic">Wishlist</span></h1>
        <p class="text-gray-400 text-lg">Your curated selection of favorite pieces.</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <?php
        $wishlist = $conn->query("SELECT p.*, c.name as category_name 
                                FROM wishlist w 
                                JOIN products p ON w.product_id = p.id 
                                JOIN categories c ON p.category_id = c.id 
                                WHERE w.user_id = $user_id AND p.status = 'available'
                                ORDER BY w.created_at DESC");
        
        if($wishlist->num_rows > 0):
            while($product = $wishlist->fetch_assoc()):
        ?>
            <div class="group" data-aos="fade-up">
                <div class="relative aspect-square overflow-hidden rounded-2xl bg-white/5 border border-white/10 mb-4">
                    <img src="assets/img/products/<?php echo $product['images']; ?>" 
                         class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    
                    <button onclick="toggleWishlist(<?php echo $product['id']; ?>, this)" 
                            class="absolute top-4 right-4 z-20 bg-black/40 backdrop-blur-md p-2 rounded-full border border-white/10 hover:scale-110 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 fill-red-500 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>

                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="bg-white text-black font-bold px-6 py-2 rounded-full text-sm transform translate-y-4 group-hover:translate-y-0 transition duration-300">
                            View Product
                        </a>
                    </div>
                </div>
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-sm group-hover:text-[#bcff00] transition"><?php echo $product['title']; ?></h3>
                        <p class="text-gray-500 text-[10px]"><?php echo $product['brand']; ?> • <?php echo $product['size']; ?></p>
                    </div>
                    <p class="font-bold text-[#bcff00] text-sm">₹<?php echo number_format($product['price'], 0); ?></p>
                </div>
            </div>
        <?php 
            endwhile;
        else:
            echo "<div class='col-span-full py-20 text-center border border-dashed border-white/10 rounded-[2rem]'>
                    <p class='text-gray-500 italic mb-4'>Your wishlist is empty.</p>
                    <a href='shop.php' class='neon-btn px-8 py-3 rounded-xl font-bold'>Browse Shop</a>
                  </div>";
        endif;
        ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
