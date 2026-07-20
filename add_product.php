<?php 
session_start();
include 'includes/header.php'; 

// Strict Check if user is logged in
if(!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

// Preserve old data on validation failure
$old = $_SESSION['old_product_data'] ?? [];
unset($_SESSION['old_product_data']);
?>

<section class="py-24 px-6 max-w-4xl mx-auto">
    <div class="mb-12" data-aos="fade-right">
        <h1 class="text-5xl font-bold tracking-tight mb-4">List an <span class="neon-text">Item</span></h1>
        <p class="text-gray-400 text-lg">Turn your wardrobe into cash. Fast and easy.</p>
    </div>

    <?php if(isset($_GET['error'])): ?>
    <div class="mb-8 bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-2xl font-bold">
        Error: <?php echo htmlspecialchars($_GET['error']); ?>. Please fix the highlighted fields and try again.
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>
    <div class="mb-8 bg-[#bcff00]/10 border border-[#bcff00]/20 text-[#bcff00] p-6 rounded-2xl font-bold">
        ✅ Item listed successfully and sent for Admin Approval!
    </div>
    <?php endif; ?>

    <form id="add-product-form" action="actions/product_action.php" method="POST" enctype="multipart/form-data" class="space-y-12" novalidate data-aos="fade-up">
        <?php echo csrf_field(); ?>

        <!-- Image Upload Section -->
        <div>
            <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Product Photos</label>
            <div class="bg-white/5 border-2 border-dashed border-white/10 rounded-[2rem] p-12 text-center hover:border-[#bcff00] transition group cursor-pointer relative" id="upload-zone">
                <input type="file" name="product_images[]" id="product_images" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" multiple accept=".jpg,.jpeg,.png,.webp" onchange="previewImages(this)">
                <div id="upload-placeholder">
                    <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-[#bcff00]/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 group-hover:text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <p class="text-xl font-bold mb-2">Upload Photos</p>
                    <p class="text-gray-500 text-sm">Select 1 to 5 photos (Max 5MB per photo. JPG, PNG, WebP only)</p>
                </div>
                <div id="image-preview-container" class="grid grid-cols-2 md:grid-cols-4 gap-4 hidden"></div>
            </div>
            <p id="images-error" class="text-red-400 text-sm mt-2 hidden">⚠️ At least one photo is required (Max 5).</p>
        </div>

        <!-- Product Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <div class="col-span-full">
                <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Product Title</label>
                <input type="text" name="title" id="field-title" maxlength="100" placeholder="e.g. Vintage Nike Windbreaker" value="<?php echo htmlspecialchars($old['title'] ?? ''); ?>"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-xl font-semibold">
                <p id="title-error" class="text-red-400 text-sm mt-2 hidden">⚠️ Title must be 5-100 characters and contain only letters, numbers, and spaces.</p>
            </div>

            <div class="col-span-full">
                <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Description</label>
                <textarea name="description" id="field-description" rows="4" placeholder="Tell us about the fit, condition, and details..."
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-gray-300"><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
                <p id="description-error" class="text-red-400 text-sm mt-2 hidden">⚠️ Description must be 20-500 characters and cannot be empty spaces.</p>
            </div>

            <div>
                <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Price (₹)</label>
                <div class="relative">
                    <span class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-500 font-bold">₹</span>
                    <input type="number" name="price" id="field-price" min="1" max="100000" step="1" placeholder="Enter price" value="<?php echo htmlspecialchars($old['price'] ?? ''); ?>"
                        class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-xl font-bold text-[#bcff00]">
                </div>
                <p id="price-error" class="text-red-400 text-sm mt-2 hidden">⚠️ Price must be between ₹1 and ₹100,000.</p>
            </div>

            <div>
                <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Category (Gender)</label>
                <select name="category_id" id="field-category"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white">
                    <option value="" disabled <?php echo empty($old['category_id']) ? 'selected' : ''; ?> class="bg-[#0a0a0a] text-gray-500">— Select Category —</option>
                    <?php
                    $cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                    while($cat = $cats->fetch_assoc()) {
                        $selected = (isset($old['category_id']) && $old['category_id'] == $cat['id']) ? 'selected' : '';
                        echo "<option value='{$cat['id']}' $selected class='bg-[#0a0a0a] text-white'>{$cat['name']}</option>";
                    }
                    ?>
                </select>
                <p id="category-error" class="text-red-400 text-sm mt-2 hidden">⚠️ Please select a valid category.</p>
            </div>

            <div>
                <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Condition</label>
                <select name="condition" id="field-condition"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white">
                    <option value="" disabled <?php echo empty($old['condition']) ? 'selected' : ''; ?> class="bg-[#0a0a0a] text-gray-500">— Select Condition —</option>
                    <?php
                    $conditions = ['New', 'Like New', 'Excellent', 'Good', 'Fair'];
                    foreach($conditions as $cond) {
                        $selected = (isset($old['condition']) && $old['condition'] == $cond) ? 'selected' : '';
                        echo "<option value='$cond' $selected class='bg-[#0a0a0a] text-white'>$cond</option>";
                    }
                    ?>
                </select>
                <p id="condition-error" class="text-red-400 text-sm mt-2 hidden">⚠️ Please select a valid condition.</p>
            </div>

            <div>
                <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Size</label>
                <select name="size" id="field-size"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white">
                    <option value="" disabled <?php echo empty($old['size']) ? 'selected' : ''; ?> class="bg-[#0a0a0a] text-gray-500">— Select Size —</option>
                    <?php
                    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                    foreach($sizes as $sz) {
                        $selected = (isset($old['size']) && $old['size'] == $sz) ? 'selected' : '';
                        echo "<option value='$sz' $selected class='bg-[#0a0a0a] text-white'>$sz</option>";
                    }
                    ?>
                </select>
                <p id="size-error" class="text-red-400 text-sm mt-2 hidden">⚠️ Please select a valid size.</p>
            </div>

            <div class="col-span-full">
                <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Brand</label>
                <input type="text" name="brand" id="field-brand" maxlength="50" placeholder="e.g. Nike, Adidas, Zara" value="<?php echo htmlspecialchars($old['brand'] ?? ''); ?>"
                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition">
                <p id="brand-error" class="text-red-400 text-sm mt-2 hidden">⚠️ Brand name can only contain letters and spaces (Max 50).</p>
            </div>

            <div class="col-span-full">
                <label class="block text-sm font-bold uppercase tracking-widest text-gray-500 mb-3 text-[#bcff00]">Usage History / Wear Detail</label>
                <input type="text" name="usage_info" id="field-usage" maxlength="300" placeholder="e.g. Worn only once, 3 months old, Never used..." value="<?php echo htmlspecialchars($old['usage_info'] ?? ''); ?>"
                    class="w-full bg-white/10 border border-[#bcff00]/20 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition text-white font-medium">
                <p id="usage-error" class="text-red-400 text-sm mt-2 hidden">⚠️ Usage details must be 10-300 characters.</p>
            </div>
        </div>

        <button type="submit" id="submit-btn" class="neon-btn w-full font-bold py-6 rounded-2xl text-xl shadow-lg transform hover:scale-[1.01] active:scale-[0.99] transition">
            List My Item Now
        </button>
    </form>
</section>

<script>
// Image Preview Logic
let fileDataTransfer = new DataTransfer();

function previewImages(input) {
    const files = Array.from(input.files);
    let errorMsg = '';
    const validTypes = ['image/jpeg', 'image/png', 'image/webp'];

    files.forEach(file => {
        if (!validTypes.includes(file.type)) {
            errorMsg = 'Invalid file type. Only JPG, PNG, and WebP are allowed.';
        } else if (file.size > 5242880) { // 5MB limit
            errorMsg = 'Image ' + file.name + ' exceeds 5MB limit.';
        } else if (fileDataTransfer.files.length >= 5) {
            errorMsg = 'Maximum 5 images allowed.';
        } else {
            fileDataTransfer.items.add(file);
        }
    });

    if (errorMsg) {
        alert(errorMsg);
    }

    input.files = fileDataTransfer.files;
    renderPreviews();
}

function removeImage(index) {
    const newDT = new DataTransfer();
    Array.from(fileDataTransfer.files).forEach((file, i) => {
        if (i !== index) newDT.items.add(file);
    });
    fileDataTransfer = newDT;
    document.getElementById('product_images').files = fileDataTransfer.files;
    renderPreviews();
}

function renderPreviews() {
    const container = document.getElementById('image-preview-container');
    const placeholder = document.getElementById('upload-placeholder');
    container.innerHTML = '';

    if (fileDataTransfer.files.length > 0) {
        container.classList.remove('hidden');
        placeholder.classList.add('hidden');
        Array.from(fileDataTransfer.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative aspect-square rounded-xl overflow-hidden bg-white/5 border border-white/10 group/img';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <button type="button" onclick="removeImage(${index})"
                        class="absolute top-2 right-2 w-7 h-7 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center text-white text-xs font-black shadow-lg opacity-0 group-hover/img:opacity-100 transition-all duration-200 z-10">
                        ✕
                    </button>
                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] font-bold px-2 py-1 truncate opacity-0 group-hover/img:opacity-100 transition-all duration-200">
                        ${file.name}
                    </div>
                `;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    } else {
        container.classList.add('hidden');
        placeholder.classList.remove('hidden');
    }
}

// Client-Side Validation Logic
document.getElementById('add-product-form').addEventListener('submit', function(e) {
    e.preventDefault();
    let valid = true;

    function setError(fieldId, errorId, hasError, customMessage = null) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(errorId);
        if (hasError) {
            field?.classList.add('border-red-500');
            field?.classList.remove('border-white/10', 'border-[#bcff00]/20');
            if (customMessage) error.innerText = '⚠️ ' + customMessage;
            error?.classList.remove('hidden');
            valid = false;
        } else {
            field?.classList.remove('border-red-500');
            field?.classList.add('border-white/10');
            error?.classList.add('hidden');
        }
    }

    // 1. Images
    const imgCount = fileDataTransfer.files.length;
    setError(null, 'images-error', imgCount < 1 || imgCount > 5, 'At least 1 and maximum 5 photos are required.');

    // 2. Title
    const title = document.getElementById('field-title').value.trim();
    const titleRegex = /^[a-zA-Z0-9\s]+$/;
    setError('field-title', 'title-error', title.length < 5 || title.length > 100 || !titleRegex.test(title));

    // 3. Description
    const desc = document.getElementById('field-description').value.trim();
    setError('field-description', 'description-error', desc.length < 20 || desc.length > 500);

    // 4. Price
    const price = parseFloat(document.getElementById('field-price').value);
    setError('field-price', 'price-error', isNaN(price) || price < 1 || price > 100000);

    // 5. Category
    setError('field-category', 'category-error', !document.getElementById('field-category').value);

    // 6. Condition
    setError('field-condition', 'condition-error', !document.getElementById('field-condition').value);

    // 7. Size
    setError('field-size', 'size-error', !document.getElementById('field-size').value);

    // 8. Brand
    const brand = document.getElementById('field-brand').value.trim();
    const brandRegex = /^[a-zA-Z\s]+$/;
    setError('field-brand', 'brand-error', brand.length > 0 && (brand.length > 50 || !brandRegex.test(brand)));

    // 9. Usage Info
    const usage = document.getElementById('field-usage').value.trim();
    setError('field-usage', 'usage-error', usage.length > 0 && (usage.length < 10 || usage.length > 300));

    // Submit if valid
    if (valid) {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = 'Uploading... Please wait <span class="animate-pulse">⏳</span>';
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        this.submit();
    } else {
        // Scroll to top to see errors
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});

// Clear red border on input
['field-title', 'field-description', 'field-price', 'field-category', 'field-condition', 'field-size', 'field-brand', 'field-usage'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
        el.classList.remove('border-red-500');
        el.classList.add('border-white/10');
        const errId = id.replace('field-', '') + '-error';
        document.getElementById(errId)?.classList.add('hidden');
    });
    el.addEventListener('change', () => el.dispatchEvent(new Event('input')));
});
</script>

<?php include 'includes/footer.php'; ?>
