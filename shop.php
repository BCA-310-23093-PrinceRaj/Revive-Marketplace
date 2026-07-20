<?php 
require_once 'config/db.php';

// Check if it is an AJAX filter request
if (isset($_GET['ajax'])) {
    $category_filters = isset($_GET['categories']) ? array_map('intval', $_GET['categories']) : [];
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $selected_sizes = isset($_GET['sizes']) ? $_GET['sizes'] : [];
    $selected_brands = isset($_GET['brands']) ? $_GET['brands'] : [];
    $selected_conditions = isset($_GET['conditions']) ? $_GET['conditions'] : [];
    $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;
    $sort = $_GET['sort'] ?? 'newest';
    $limit = 12;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

    $user_id = $_SESSION['user_id'] ?? 0;

    // Build WHERE conditions with a closure to allow exclusions for dynamic filter counting
    $buildWhere = function($exclude) use ($conn, $category_filters, $selected_sizes, $selected_brands, $selected_conditions, $min_price, $max_price, $search_query) {
        $w = "WHERE p.status = 'available'";
        if (!empty($category_filters)) $w .= " AND p.category_id IN (" . implode(',', $category_filters) . ")";
        if ($exclude !== 'sizes' && !empty($selected_sizes)) {
            $escaped = array_map(fn($s) => "'" . mysqli_real_escape_string($conn, $s) . "'", $selected_sizes);
            $w .= " AND p.size IN (" . implode(',', $escaped) . ")";
        }
        if ($exclude !== 'brands' && !empty($selected_brands)) {
            $escaped = array_map(fn($b) => "'" . mysqli_real_escape_string($conn, $b) . "'", $selected_brands);
            $w .= " AND p.brand IN (" . implode(',', $escaped) . ")";
        }
        if ($exclude !== 'conditions' && !empty($selected_conditions)) {
            $escaped = array_map(fn($c) => "'" . mysqli_real_escape_string($conn, $c) . "'", $selected_conditions);
            $w .= " AND p.product_condition IN (" . implode(',', $escaped) . ")";
        }
        if ($min_price >= 0) $w .= " AND p.price >= $min_price";
        if ($max_price > 0) $w .= " AND p.price <= $max_price";
        if ($search_query) {
            $keywords = explode(' ', $search_query);
            $search_conditions = [];
            foreach ($keywords as $word) {
                $word = mysqli_real_escape_string($conn, $word);
                $search_conditions[] = "(p.title LIKE '%$word%' OR p.brand LIKE '%$word%' OR p.description LIKE '%$word%')";
            }
            $w .= " AND (" . implode(' AND ', $search_conditions) . ")";
        }
        return $w;
    };

    $where = $buildWhere('none');

    // Dynamic Counts Calculations
    $size_counts = [];
    $cond_counts = [];
    $brand_counts = [];
    
    $sc_query = $conn->query("SELECT p.size, COUNT(p.id) as c FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id " . $buildWhere('sizes') . " GROUP BY p.size");
    if($sc_query) while($row = $sc_query->fetch_assoc()) $size_counts[$row['size']] = (int)$row['c'];
    
    $cc_query = $conn->query("SELECT p.product_condition, COUNT(p.id) as c FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id " . $buildWhere('conditions') . " GROUP BY p.product_condition");
    if($cc_query) while($row = $cc_query->fetch_assoc()) $cond_counts[$row['product_condition']] = (int)$row['c'];
    
    $bc_query = $conn->query("SELECT p.brand, COUNT(p.id) as c FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id " . $buildWhere('brands') . " GROUP BY p.brand");
    if($bc_query) while($row = $bc_query->fetch_assoc()) $brand_counts[$row['brand']] = (int)$row['c'];

    $sort_sql = match($sort) {
        'price_low' => "p.price ASC",
        'price_high' => "p.price DESC",
        default => "p.created_at DESC"
    };

    // Count query (no subquery conflict)
    $count_sql = "SELECT COUNT(*) as total 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  JOIN users u ON p.seller_id = u.id 
                  $where";
    $total_count = $conn->query($count_sql)->fetch_assoc()['total'] ?? 0;
    $has_more = ($offset + $limit) < $total_count;

    // Main products query
    $sql = "SELECT p.*, c.name as category_name, u.name as seller_name,
            (SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = p.id) as is_wishlisted
            FROM products p 
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.seller_id = u.id
            $where
            ORDER BY $sort_sql LIMIT $limit OFFSET $offset";

    $products = $conn->query($sql);

    // Render new-design cards
    if ($products->num_rows > 0) {
        while ($product = $products->fetch_assoc()) {
            $cond = $product['product_condition'];
            $badge = match($cond) {
                'New'        => ['label' => 'Like New',     'class' => 'bg-[#bcff00] text-black'],
                'Like New'   => ['label' => 'Like New',     'class' => 'bg-[#bcff00] text-black'],
                'Gently Used'=> ['label' => 'Gently Used',  'class' => 'bg-orange-400/90 text-black'],
                'Used'       => ['label' => 'Gently Used',  'class' => 'bg-orange-400/90 text-black'],
                'Vintage'    => ['label' => 'Vintage',      'class' => 'bg-purple-500/90 text-white'],
                default      => ['label' => htmlspecialchars($cond), 'class' => 'bg-white/20 text-white'],
            };
            ?>
            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="group block bg-[#111] border border-white/8 rounded-2xl overflow-hidden hover:border-white/20 hover:shadow-xl hover:shadow-black/40 transition-all duration-300">
                <!-- Product Image -->
                <div class="relative aspect-[3/4] overflow-hidden bg-[#1a1a1a]">
                    <img src="assets/img/products/<?php echo htmlspecialchars($product['images']); ?>"
                         onerror="this.style.display='none'"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    <!-- Condition Badge -->
                    <span class="absolute bottom-3 left-3 text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-lg <?php echo $badge['class']; ?>">
                        <?php echo $badge['label']; ?>
                    </span>
                    <!-- Wishlist Button -->
                    <button onclick="event.preventDefault(); toggleWishlist(<?php echo $product['id']; ?>, this)"
                            class="absolute top-3 right-3 z-20 bg-black/60 backdrop-blur-md p-2 rounded-full border border-white/10 hover:scale-110 hover:bg-black/80 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 <?php echo $product['is_wishlisted'] ? 'fill-red-500 text-red-500' : 'text-gray-300'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
                <!-- Card Info -->
                <div class="p-4">
                    <h3 class="font-bold text-white text-sm mb-1 truncate group-hover:text-[#bcff00] transition"><?php echo htmlspecialchars($product['title']); ?></h3>
                    <p class="text-gray-500 text-xs mb-1"><?php echo htmlspecialchars($product['brand']); ?></p>
                    <p class="text-gray-500 text-xs mb-3">Size <?php echo htmlspecialchars($product['size']); ?></p>
                    <div class="flex items-center justify-between">
                        <p class="text-[#bcff00] font-black text-lg">₹<?php echo number_format($product['price'], 0); ?></p>
                    </div>
                    <div class="flex items-center space-x-1 mt-2 pt-2 border-t border-white/5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-[#bcff00] flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-500 text-[11px]">Sold by: <span class="text-gray-300 font-semibold"><?php echo htmlspecialchars($product['seller_name']); ?></span></span>
                    </div>
                </div>
            </a>
            <?php
        }
    } else {
        if ($offset === 0) {
            echo "<div class='col-span-full py-24 px-6 text-center flex flex-col items-center justify-center border border-white/5 rounded-3xl bg-[#111]'>
                    <div class='bg-white/5 p-6 rounded-full mb-6 border border-white/10'>
                        <svg xmlns='http://www.w3.org/2000/svg' class='h-12 w-12 text-[#bcff00]/60' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/></svg>
                    </div>
                    <h2 class='text-3xl text-white font-black mb-3'>No products match your filters.</h2>
                    <p class='text-gray-500 text-base mb-8 max-w-md'>We couldn't find any items matching your current filter combination. Try adjusting them or clear all filters.</p>
                    <div class='flex flex-wrap gap-4 items-center justify-center'>
                        <button onclick='clearAllFilters()' class='px-8 py-3.5 bg-[#bcff00] text-black text-sm font-black uppercase tracking-widest rounded-xl hover:bg-[#d4ff4d] hover:scale-105 transition duration-300 shadow-[0_0_20px_rgba(188,255,0,0.3)]'>Clear All Filters</button>
                    </div>
                  </div>";
        }
    }

    if ($has_more) {
        $next_offset = $offset + $limit;
        echo "<div id='load-more-trigger' class='col-span-full' data-next-offset='$next_offset'></div>";
    } else {
        if ($total_count > 0) {
            echo "<div id='no-more-products' class='col-span-full text-center py-8 text-gray-700 text-xs font-bold uppercase tracking-widest'>— All products loaded —</div>";
        }
    }

    $filterData = json_encode([
        'total' => $total_count,
        'sizes' => $size_counts,
        'conditions' => $cond_counts,
        'brands' => $brand_counts
    ]);
    echo "<script type='application/json' id='filter-data'>{$filterData}</script>";

    exit();
}

include 'includes/header.php'; 

$view = $_GET['view'] ?? '';
$hide_filters = ($view === 'products');

// Fetch range metrics
$max_price_db = $conn->query("SELECT MAX(price) as max_p FROM products WHERE status = 'available'")->fetch_assoc()['max_p'] ?? 10000;
$max_price_limit = ceil($max_price_db);
if ($max_price_limit <= 0) $max_price_limit = 10000;

// Dynamic Brands list
$brands_list = [];
$brands_res = $conn->query("SELECT DISTINCT brand FROM products WHERE status = 'available' AND brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
while ($brand_row = $brands_res->fetch_assoc()) {
    $brands_list[] = $brand_row['brand'];
}

// Categories
$categories_list = [];
$cats_res = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
while ($cat_row = $cats_res->fetch_assoc()) {
    $categories_list[] = $cat_row;
}
?>

<style>
    .size-tag-checkbox:checked + div {
        background-color: #bcff00;
        color: #000;
        border-color: #bcff00;
        font-weight: bold;
    }
    .cat-tag-checkbox:checked + div {
        background-color: #bcff00;
        color: #000;
        border-color: #bcff00;
        font-weight: bold;
    }
    .custom-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
    .loading-grid { opacity: 0.3; pointer-events: none; transition: opacity 0.3s; }
    .range-slider { position: relative; height: 4px; background: rgba(255,255,255,0.1); border-radius: 4px; }
    .range-slider .progress { position: absolute; height: 100%; left: 0%; right: 0%; background: #bcff00; border-radius: 4px; }
    .range-input { position: relative; height: 20px; margin-top: 8px; }
    .range-input input { position: absolute; width: 100%; height: 4px; top: 0; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
    input[type="range"]::-webkit-slider-thumb { height: 16px; width: 16px; border-radius: 50%; background: #bcff00; pointer-events: auto; -webkit-appearance: none; cursor: pointer; }
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; }
    input[type="number"] { -moz-appearance: textfield; }
    .filter-section { border-bottom: 1px solid rgba(255,255,255,0.06); }
    .suggestion-dropdown { position: absolute; top: 105%; left: 0; width: 100%; background: #0f1115; border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); z-index: 100; overflow: hidden; }
    .suggestion-item { padding: 10px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.2s; font-size: 12px; }
    .suggestion-item:hover { background: rgba(255,255,255,0.05); }
    .list-view-card { display: flex; flex-direction: row; }
    .list-view-card .card-img { width: 100px; flex-shrink: 0; aspect-ratio: 1; }
    #products-catalog-grid.list-view { grid-template-columns: 1fr !important; }
</style>

<!-- Shop Page -->
<section class="pt-10 pb-24 px-4 md:px-6 max-w-7xl mx-auto">

    <!-- Page Title -->
    <div class="mb-8" data-aos="fade-up">
        <h1 class="text-4xl font-black tracking-tight">Shop</h1>
        <p class="text-gray-500 text-sm mt-1">Discover sustainable fashion from trusted sellers.</p>
    </div>

    <!-- Search Bar -->
    <?php if (!$hide_filters): ?>
    <div class="relative w-full mb-8" data-aos="fade-up">
        <div class="relative max-w-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="shop-search-input" placeholder="Search title, brand, category..."
                class="w-full bg-white/5 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm focus:outline-none focus:border-[#bcff00] transition text-white">
        </div>
        <div id="suggestions-box" class="suggestion-dropdown hidden" style="max-width:32rem;"></div>
    </div>
    <?php endif; ?>

    <!-- Main Grid -->
    <div class="flex gap-8 items-start">

        <?php if (!$hide_filters): ?>
        <!-- ═══ FILTER SIDEBAR ═══ -->
        <aside id="shop-filters-sidebar" class="hidden lg:flex flex-col w-64 flex-shrink-0 sticky top-24 bg-[#111] border border-white/8 rounded-2xl overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
                <span class="text-xs font-black uppercase tracking-widest text-white">Filters</span>
                <button onclick="clearAllFilters()" class="text-[#bcff00] text-xs font-bold hover:underline flex items-center space-x-1">
                    <span>Clear All</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="filters-form" class="flex flex-col flex-1 overflow-y-auto custom-scroll">

                <!-- GENDER (styled as pills) -->
                <div class="filter-section px-5 py-4">
                    <button type="button" onclick="toggleSection('section-category')" class="flex items-center justify-between w-full mb-3">
                        <span class="text-[11px] font-black uppercase tracking-widest text-gray-400">Gender</span>
                        <svg id="icon-category" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <div id="section-category" class="flex flex-wrap gap-2">
                        <?php foreach($categories_list as $cat): ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" class="cat-tag-checkbox sr-only" onchange="triggerFiltersReload()">
                            <div class="px-3 py-1.5 border border-white/10 rounded-lg text-xs font-bold text-gray-400 hover:border-white/30 hover:text-white transition select-none">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SIZE (styled as pills) -->
                <div class="filter-section px-5 py-4">
                    <button type="button" onclick="toggleSection('section-size')" class="flex items-center justify-between w-full mb-3">
                        <span class="text-[11px] font-black uppercase tracking-widest text-gray-400">Size</span>
                        <svg id="icon-size" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <div id="section-size" class="flex flex-wrap gap-2">
                        <?php foreach(['XS','S','M','L','XL','XXL','XXXL','Free Size','Custom'] as $sz): ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="sizes[]" value="<?php echo $sz; ?>" class="size-tag-checkbox sr-only" onchange="triggerFiltersReload()">
                            <div class="px-3 py-1.5 border border-white/10 rounded-lg text-xs font-bold text-gray-400 hover:border-white/30 hover:text-white transition select-none">
                                <?php echo $sz; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CONDITION (checkboxes) -->
                <div class="filter-section px-5 py-4">
                    <button type="button" onclick="toggleSection('section-condition')" class="flex items-center justify-between w-full mb-3">
                        <span class="text-[11px] font-black uppercase tracking-widest text-gray-400">Condition</span>
                        <svg id="icon-condition" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <div id="section-condition" class="grid grid-cols-2 gap-2">
                        <?php foreach(['New','Like New','Gently Used','Vintage','Used'] as $cond): ?>
                        <label class="flex items-center space-x-2 cursor-pointer group">
                            <input type="checkbox" name="conditions[]" value="<?php echo $cond; ?>" class="w-4 h-4 rounded border-white/20 accent-[#bcff00]" onchange="triggerFiltersReload()">
                            <span class="text-xs text-gray-400 group-hover:text-white transition"><?php echo $cond; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- BRAND (search + checkboxes) -->
                <?php if(!empty($brands_list)): ?>
                <div class="filter-section px-5 py-4">
                    <button type="button" onclick="toggleSection('section-brand')" class="flex items-center justify-between w-full mb-3">
                        <span class="text-[11px] font-black uppercase tracking-widest text-gray-400">Brand</span>
                        <svg id="icon-brand" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <div id="section-brand">
                        <input type="text" id="brand-search-input" placeholder="Search brands..." oninput="filterBrandList(this.value)"
                            class="w-full bg-white/5 border border-white/8 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-[#bcff00] transition mb-3">
                        <div id="brand-list" class="space-y-2 max-h-44 overflow-y-auto custom-scroll">
                            <?php foreach($brands_list as $brand): ?>
                            <label class="flex items-center space-x-2 cursor-pointer group brand-item">
                                <input type="checkbox" name="brands[]" value="<?php echo htmlspecialchars($brand); ?>" class="w-4 h-4 rounded border-white/20 accent-[#bcff00]" onchange="triggerFiltersReload()">
                                <span class="text-xs text-gray-400 group-hover:text-white transition"><?php echo htmlspecialchars($brand); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- PRICE RANGE -->
                <div class="px-5 py-4">
                    <button type="button" onclick="toggleSection('section-price')" class="flex items-center justify-between w-full mb-3">
                        <span class="text-[11px] font-black uppercase tracking-widest text-gray-400">Price Range</span>
                        <svg id="icon-price" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <div id="section-price">
                        <div class="flex justify-between text-xs text-gray-500 mb-2">
                            <span>₹<span id="price-min-display">0</span></span>
                            <span>₹<span id="price-max-display"><?php echo number_format($max_price_limit); ?></span>+</span>
                        </div>
                        <div class="range-slider mb-4">
                            <div class="progress" id="slider-progress-bar"></div>
                        </div>
                        <div class="range-input mb-4">
                            <input type="range" class="range-min" min="0" max="<?php echo $max_price_limit; ?>" value="0" step="50" oninput="updateRangeInputs(this, 'min')">
                            <input type="range" class="range-max" min="0" max="<?php echo $max_price_limit; ?>" value="<?php echo $max_price_limit; ?>" step="50" oninput="updateRangeInputs(this, 'max')">
                        </div>
                        <!-- Hidden inputs for form submission -->
                        <input type="hidden" id="min-price-number" name="min_price" value="0">
                        <input type="hidden" id="max-price-number" name="max_price" value="<?php echo $max_price_limit; ?>">
                    </div>
                </div>

                <!-- Apply Filters Button -->
                <div class="px-5 pb-5">
                    <button type="button" onclick="triggerFiltersReload()" id="apply-filters-btn"
                        class="w-full bg-[#bcff00] text-black font-black text-sm py-3 rounded-xl hover:bg-[#d4ff4d] transition flex items-center justify-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span>Apply Filters <span id="filter-count-badge" class="hidden bg-black/20 rounded-full px-1.5 ml-1"></span></span>
                    </button>
                </div>

            </form>
        </aside>
        <?php endif; ?>

        <!-- ═══ RIGHT SIDE: Products ═══ -->
        <div class="flex-1 min-w-0">

            <!-- Grid Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
                <div class="text-sm text-gray-500">
                    Showing <span class="text-white font-bold" id="total-results-count">--</span> results
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">Sort by:</span>
                        <select id="shop-sorting-select" onchange="triggerFiltersReload()"
                            class="bg-[#111] border border-white/10 rounded-xl px-4 py-2 text-xs font-bold focus:outline-none focus:border-[#bcff00] transition text-white">
                            <option value="newest" class="bg-[#111]">Newest First</option>
                            <option value="price_low" class="bg-[#111]">Price: Low to High</option>
                            <option value="price_high" class="bg-[#111]">Price: High to Low</option>
                        </select>
                    </div>
                    <!-- Grid/List Toggle -->
                    <div class="flex bg-[#111] border border-white/10 rounded-xl p-1">
                        <button id="grid-view-btn" onclick="setViewMode('grid')" class="p-2 rounded-lg bg-[#bcff00] transition" title="Grid View">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </button>
                        <button id="list-view-btn" onclick="setViewMode('list')" class="p-2 rounded-lg transition text-gray-400 hover:text-white" title="List View">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active Filter Chips -->
            <div id="active-filter-chips" class="flex flex-wrap gap-2 mb-4 hidden"></div>

            <!-- Products Grid -->
            <div id="products-catalog-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 transition duration-300">
                <div class="col-span-full py-32 text-center text-gray-500">Loading products...</div>
            </div>
        </div>
    </div>
</section>

<!-- Mobile Filter Trigger -->
<?php if (!$hide_filters): ?>
<div class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50">
    <button type="button" onclick="toggleMobileFilters(true)" class="bg-[#bcff00] text-black font-black px-8 py-3.5 rounded-full text-xs shadow-2xl flex items-center space-x-2 tracking-wider uppercase">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        <span>Filters</span>
    </button>
</div>

<!-- Mobile Drawer -->
<div id="mobile-filters-drawer" class="fixed inset-0 z-[100] hidden flex flex-col justify-end">
    <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" onclick="toggleMobileFilters(false)"></div>
    <div class="relative bg-[#111] border-t border-white/10 rounded-t-[2rem] p-6 max-h-[85vh] overflow-y-auto z-10">
        <div class="flex justify-between items-center pb-4 border-b border-white/8 mb-4">
            <h3 class="font-black text-sm uppercase tracking-wider">Filters</h3>
            <button onclick="toggleMobileFilters(false)" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
        </div>
        <div id="mobile-form-placeholder" class="pb-20"></div>
        <div class="fixed bottom-0 left-0 right-0 bg-[#111] border-t border-white/8 p-4 flex gap-3 z-20">
            <button type="button" onclick="clearAllFilters(); toggleMobileFilters(false);" class="flex-1 py-3 text-xs font-bold uppercase border border-white/10 rounded-xl text-gray-400">Clear</button>
            <button type="button" onclick="triggerFiltersReload(); toggleMobileFilters(false);" class="flex-1 py-3 text-xs font-black uppercase bg-[#bcff00] rounded-xl text-black">Apply</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Trust Strip -->
<section class="pb-16 px-4 md:px-6 max-w-7xl mx-auto">
    <div class="bg-[#111] border border-white/8 rounded-2xl px-6 py-5 grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php
        $trust = [
            ['♻️', 'Sustainable Fashion', 'Eco-friendly choices'],
            ['⭐', 'Trusted Sellers', 'Verified & reliable'],
            ['🔒', 'Secure Payments', '100% protected'],
            ['↩️', 'Easy Returns', 'Hassle-free returns'],
        ];
        foreach($trust as $t): ?>
        <div class="flex items-center space-x-3">
            <span class="text-2xl"><?php echo $t[0]; ?></span>
            <div>
                <p class="text-xs font-bold text-white"><?php echo $t[1]; ?></p>
                <p class="text-[11px] text-gray-500"><?php echo $t[2]; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
    let filterTimeout = null;
    let currentViewMode = 'grid';

    document.addEventListener("DOMContentLoaded", function() {
        syncSliderInputs();
        syncFiltersFromUrl();

        // Mobile filter clone
        const formObj = document.getElementById('filters-form');
        const mPlace = document.getElementById('mobile-form-placeholder');
        if (formObj && mPlace) {
            mPlace.appendChild(formObj.cloneNode(true));
            mPlace.addEventListener('change', function(e) {
                const originalInput = formObj.querySelector(`[name="${e.target.name}"][value="${e.target.value}"]`);
                if (originalInput) originalInput.checked = e.target.checked;
            });
        }

        // Search input listener
        const searchInput = document.getElementById('shop-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(triggerFiltersReload, 500);
                handleSuggestionsSearch(this.value.trim());
            });
        }
    });

    // ── View Mode Toggle ──
    function setViewMode(mode) {
        currentViewMode = mode;
        const grid = document.getElementById('products-catalog-grid');
        const gridBtn = document.getElementById('grid-view-btn');
        const listBtn = document.getElementById('list-view-btn');
        if (mode === 'list') {
            grid.classList.add('list-view');
            grid.style.gridTemplateColumns = '1fr';
            gridBtn.classList.remove('bg-[#bcff00]'); gridBtn.classList.add('text-gray-400');
            listBtn.classList.add('bg-[#bcff00]'); listBtn.classList.remove('text-gray-400');
            listBtn.querySelector('svg').classList.add('text-black');
        } else {
            grid.classList.remove('list-view');
            grid.style.gridTemplateColumns = '';
            listBtn.classList.remove('bg-[#bcff00]'); listBtn.classList.add('text-gray-400');
            listBtn.querySelector('svg').classList.remove('text-black');
            gridBtn.classList.add('bg-[#bcff00]'); gridBtn.classList.remove('text-gray-400');
        }
    }

    // ── Filter Section Toggle ──
    function toggleSection(id) {
        const el = document.getElementById(id);
        const iconId = 'icon-' + id.replace('section-', '');
        const icon = document.getElementById(iconId);
        if (el) {
            el.classList.toggle('hidden');
            if (icon) icon.style.transform = el.classList.contains('hidden') ? 'rotate(180deg)' : '';
        }
    }

    // ── Brand Search Filter ──
    function filterBrandList(query) {
        document.querySelectorAll('.brand-item').forEach(item => {
            const name = item.querySelector('span').textContent.toLowerCase();
            item.style.display = name.includes(query.toLowerCase()) ? '' : 'none';
        });
    }

    // ── Range Slider ──
    function updateRangeInputs(el, type) {
        const rangeMin = document.querySelector('.range-min');
        const rangeMax = document.querySelector('.range-max');
        let minVal = parseInt(rangeMin.value);
        let maxVal = parseInt(rangeMax.value);
        if (type === 'min' && minVal >= maxVal) { rangeMin.value = maxVal - 50; minVal = maxVal - 50; }
        if (type === 'max' && maxVal <= minVal) { rangeMax.value = minVal + 50; maxVal = minVal + 50; }
        document.getElementById('min-price-number').value = minVal;
        document.getElementById('max-price-number').value = maxVal;
        document.getElementById('price-min-display').textContent = minVal.toLocaleString();
        document.getElementById('price-max-display').textContent = maxVal.toLocaleString();
        syncSliderProgress();
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(triggerFiltersReload, 600);
    }

    function syncSliderInputs() {
        const rangeMin = document.querySelector('.range-min');
        const rangeMax = document.querySelector('.range-max');
        const minNum = document.getElementById('min-price-number');
        const maxNum = document.getElementById('max-price-number');
        if (!rangeMin || !rangeMax) return;
        if (minNum) rangeMin.value = minNum.value;
        if (maxNum) rangeMax.value = maxNum.value;
        if (document.getElementById('price-min-display')) document.getElementById('price-min-display').textContent = parseInt(rangeMin.value).toLocaleString();
        if (document.getElementById('price-max-display')) document.getElementById('price-max-display').textContent = parseInt(rangeMax.value).toLocaleString();
        syncSliderProgress();
    }

    function syncSliderProgress() {
        const rangeMin = document.querySelector('.range-min');
        const rangeMax = document.querySelector('.range-max');
        const progress = document.getElementById('slider-progress-bar');
        if (!rangeMin || !rangeMax || !progress) return;
        const max = parseInt(rangeMin.max);
        const left = (parseInt(rangeMin.value) / max) * 100;
        const right = 100 - (parseInt(rangeMax.value) / max) * 100;
        progress.style.left = left + '%';
        progress.style.right = right + '%';
    }

    // ── Clear All Filters ──
    function clearAllFilters() {
        const form = document.getElementById('filters-form');
        if (!form) return;
        form.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        const rangeMin = document.querySelector('.range-min');
        const rangeMax = document.querySelector('.range-max');
        const maxVal = rangeMax ? rangeMax.max : <?php echo $max_price_limit; ?>;
        if (rangeMin) rangeMin.value = 0;
        if (rangeMax) rangeMax.value = maxVal;
        document.getElementById('min-price-number').value = 0;
        document.getElementById('max-price-number').value = maxVal;
        const searchInput = document.getElementById('shop-search-input');
        if (searchInput) searchInput.value = '';
        syncSliderInputs();
        triggerFiltersReload();
    }

    // ── Update Filter Count Badge ──
    function updateFilterCountBadge() {
        const form = document.getElementById('filters-form');
        if (!form) return;
        const count = form.querySelectorAll('input[type="checkbox"]:checked').length;
        const badge = document.getElementById('filter-count-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('hidden', count === 0);
        }
    }

    // ── Active Filter Chips ──
    function updateActiveFilterChips() {
        const chips = document.getElementById('active-filter-chips');
        if (!chips) return;
        chips.innerHTML = '';
        const form = document.getElementById('filters-form');
        const search = document.getElementById('shop-search-input')?.value.trim();
        let hasAny = false;
        if (search) {
            hasAny = true;
            chips.appendChild(makeChip(`🔍 "${search}"`, () => { document.getElementById('shop-search-input').value = ''; triggerFiltersReload(); }));
        }
        form.querySelectorAll('input[type="checkbox"]:checked').forEach(input => {
            hasAny = true;
            chips.appendChild(makeChip(input.value, () => { input.checked = false; triggerFiltersReload(); }));
        });
        const minP = parseInt(document.getElementById('min-price-number').value) || 0;
        const maxP = parseInt(document.getElementById('max-price-number').value) || 0;
        const maxLimit = <?php echo $max_price_limit; ?>;
        if (minP > 0 || maxP < maxLimit) {
            hasAny = true;
            chips.appendChild(makeChip(`₹${minP.toLocaleString()} – ₹${maxP.toLocaleString()}`, () => {
                document.getElementById('min-price-number').value = 0;
                document.getElementById('max-price-number').value = maxLimit;
                syncSliderInputs(); triggerFiltersReload();
            }));
        }
        chips.classList.toggle('hidden', !hasAny);
    }

    function makeChip(label, onRemove) {
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center space-x-1.5 bg-[#bcff00]/10 border border-[#bcff00]/30 text-[#bcff00] text-xs font-bold px-3 py-1.5 rounded-full';
        chip.innerHTML = `<span>${label}</span><button class="hover:text-white transition font-black text-sm leading-none">✕</button>`;
        chip.querySelector('button').onclick = (e) => { e.stopPropagation(); onRemove(); };
        return chip;
    }

    // ── Mobile Filters Toggle ──
    function toggleMobileFilters(show) {
        const drawer = document.getElementById('mobile-filters-drawer');
        if (drawer) drawer.classList.toggle('hidden', !show);
    }

    // ── Main Fetch Function ──
    function triggerFiltersReload() {
        const grid = document.getElementById('products-catalog-grid');
        grid.classList.add('loading-grid');
        const sortSelect = document.getElementById('shop-sorting-select').value;
        const searchInput = document.getElementById('shop-search-input')?.value.trim() || '';
        const form = document.getElementById('filters-form');
        const params = new URLSearchParams();
        params.append('ajax', '1');
        params.append('sort', sortSelect);
        params.append('offset', '0');
        if (searchInput) params.append('search', searchInput);
        form.querySelectorAll('input[type="checkbox"]:checked').forEach(input => params.append(input.name, input.value));
        params.append('min_price', document.getElementById('min-price-number').value);
        params.append('max_price', document.getElementById('max-price-number').value);
        window._shopParams = params.toString();

        fetch('shop.php?' + params.toString())
            .then(res => res.text())
            .then(html => {
                grid.classList.remove('loading-grid');
                grid.innerHTML = html;
                const cardsCount = grid.querySelectorAll('a.group').length;
                document.getElementById('total-results-count').innerText = cardsCount;

                // Process Dynamic Filter Data
                let needsReload = false;
                const filterDataEl = document.getElementById('filter-data');
                if (filterDataEl) {
                    try {
                        const data = JSON.parse(filterDataEl.textContent);
                        
                        const processSection = (name, counts) => {
                            const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
                            checkboxes.forEach(chk => {
                                const val = chk.value;
                                const count = counts[val] || 0;
                                const labelDiv = chk.nextElementSibling;
                                
                                // Update label text with count
                                let baseText = labelDiv.innerText.replace(/\s\(\d+\)$/, '').trim();
                                labelDiv.innerText = `${baseText} (${count})`;
                                
                                // Handle Empty State
                                if (count === 0) {
                                    chk.disabled = true;
                                    labelDiv.classList.add('opacity-30', 'cursor-not-allowed');
                                    // Auto-deselect if it was selected but became 0 due to other filters
                                    if (chk.checked) {
                                        chk.checked = false;
                                        needsReload = true;
                                    }
                                } else {
                                    chk.disabled = false;
                                    labelDiv.classList.remove('opacity-30', 'cursor-not-allowed');
                                }
                            });
                        };

                        processSection('sizes[]', data.sizes);
                        processSection('conditions[]', data.conditions);
                        processSection('brands[]', data.brands);

                    } catch (e) { console.error("Error parsing filter data", e); }
                    filterDataEl.remove(); // Clean up DOM
                }

                params.delete('ajax'); params.delete('offset');
                window.history.replaceState({}, '', window.location.pathname + '?' + params.toString());
                updateActiveFilterChips();
                updateFilterCountBadge();
                if (currentViewMode === 'list') setViewMode('list');

                // If any filter was auto-deselected, we must fetch again to show correct results
                if (needsReload) {
                    triggerFiltersReload();
                }
            })
            .catch(() => {
                grid.classList.remove('loading-grid');
                grid.innerHTML = "<div class='col-span-full py-20 text-center text-red-500 font-bold'>Error loading products.</div>";
            });
    }

    // ── Load More (Infinite Scroll) ──
    function loadMoreProducts(nextOffset) {
        const grid = document.getElementById('products-catalog-grid');
        const trigger = document.getElementById('load-more-trigger');
        if (trigger) trigger.remove();
        const spinner = document.createElement('div');
        spinner.id = 'load-more-spinner';
        spinner.className = 'col-span-full flex justify-center py-10';
        spinner.innerHTML = `<div class="w-7 h-7 border-2 border-[#bcff00] border-t-transparent rounded-full animate-spin"></div>`;
        grid.appendChild(spinner);
        const params = new URLSearchParams(window._shopParams || '');
        params.set('ajax', '1'); params.set('offset', nextOffset);
        fetch('shop.php?' + params.toString())
            .then(res => res.text())
            .then(html => {
                const sp = document.getElementById('load-more-spinner');
                if (sp) sp.remove();
                const temp = document.createElement('div');
                temp.innerHTML = html;
                // Remove JSON script from pagination response
                const fd = temp.querySelector('#filter-data');
                if(fd) fd.remove();
                Array.from(temp.children).forEach(child => grid.appendChild(child));
                const cardsCount = grid.querySelectorAll('a.group').length;
                document.getElementById('total-results-count').innerText = cardsCount;
            })
            .catch(() => { const sp = document.getElementById('load-more-spinner'); if (sp) sp.remove(); });
    }

    // Intersection Observer for infinite scroll
    const gridObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target.id === 'load-more-trigger') {
                loadMoreProducts(parseInt(entry.target.dataset.nextOffset));
            }
        });
    }, { rootMargin: '200px' });

    const catalogGrid = document.getElementById('products-catalog-grid');
    if (catalogGrid) {
        new MutationObserver(() => {
            const trigger = document.getElementById('load-more-trigger');
            if (trigger) gridObserver.observe(trigger);
        }).observe(catalogGrid, { childList: true });
    }

    // ── Sync Filters from URL ──
    function syncFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const form = document.getElementById('filters-form');
        if (!form) { triggerFiltersReload(); return; }
        const sort = params.get('sort');
        if (sort) document.getElementById('shop-sorting-select').value = sort;
        const search = params.get('search');
        if (search && document.getElementById('shop-search-input')) document.getElementById('shop-search-input').value = search;
        params.getAll('categories[]').forEach(id => {
            const chk = form.querySelector(`input[name="categories[]"][value="${id}"]`);
            if (chk) chk.checked = true;
        });
        params.getAll('sizes[]').forEach(sz => {
            const chk = form.querySelector(`input[name="sizes[]"][value="${sz}"]`);
            if (chk) chk.checked = true;
        });
        params.getAll('brands[]').forEach(br => {
            const chk = form.querySelector(`input[name="brands[]"][value="${br}"]`);
            if (chk) chk.checked = true;
        });
        params.getAll('conditions[]').forEach(cond => {
            const chk = form.querySelector(`input[name="conditions[]"][value="${cond}"]`);
            if (chk) chk.checked = true;
        });
        if (params.has('min_price')) document.getElementById('min-price-number').value = params.get('min_price');
        if (params.has('max_price')) document.getElementById('max-price-number').value = params.get('max_price');
        syncSliderInputs();
        triggerFiltersReload();
    }

    // ── Autocomplete Search ──
    function handleSuggestionsSearch(keyword) {
        const dropdown = document.getElementById('suggestions-box');
        if (!dropdown) return;
        if (keyword.length < 2) { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; return; }
        fetch(`actions/search_suggestions.php?q=${encodeURIComponent(keyword)}`)
            .then(res => res.json())
            .then(suggestions => {
                if (!suggestions.length) { dropdown.classList.add('hidden'); return; }
                dropdown.innerHTML = '';
                suggestions.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = `<img src="assets/img/products/${item.images}" class="w-8 h-8 rounded-lg object-cover border border-white/10 flex-shrink-0" onerror="this.style.display='none'">
                        <div><p class="font-bold text-xs text-white truncate">${item.title}</p>
                        <p class="text-[10px] text-gray-500">${item.brand} • ₹${Number(item.price).toLocaleString()}</p></div>`;
                    div.onclick = () => {
                        document.getElementById('shop-search-input').value = item.title;
                        dropdown.classList.add('hidden');
                        triggerFiltersReload();
                    };
                    dropdown.appendChild(div);
                });
                dropdown.classList.remove('hidden');
            })
            .catch(() => dropdown.classList.add('hidden'));
    }
</script>

<?php include 'includes/footer.php'; ?>
