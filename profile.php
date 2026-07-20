<?php 
require_once 'config/db.php';

// Auth check BEFORE any HTML output
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Calculate Stats
$orders_res = $conn->query("SELECT COUNT(*) as count, SUM(amount) as total_spent FROM orders WHERE buyer_id = $user_id AND status IN ('completed', 'delivered')");
$order_stats = $orders_res->fetch_assoc();
$items_bought = $order_stats['count'] ?? 0;
$total_spent = $order_stats['total_spent'] ?? 0;

$waste_saved = $items_bought * 1.5; // Average 1.5kg per garment
$water_saved = $items_bought * 2700; // Average 2700L per cotton garment

// Badge Logic
$badge = "Eco Explorer";
if($items_bought >= 5) $badge = "Sustainable Stylist";
if($items_bought >= 15) $badge = "Circular Fashion Icon";
if($items_bought >= 30) $badge = "Planet Guardian";
?>

<section class="py-24 px-6 max-w-6xl mx-auto min-h-screen">
    <?php if(isset($_GET['success'])): ?>
    <div class="mb-8 bg-[#bcff00]/10 border border-[#bcff00]/20 text-[#bcff00] p-6 rounded-2xl font-bold">
        Profile updated successfully!
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
    <div class="mb-8 bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-2xl font-bold">
        Error: <?php echo htmlspecialchars($_GET['error']); ?>.
    </div>
    <?php endif; ?>

    <!-- Profile Header Card -->
    <div class="bg-white/5 border border-white/10 rounded-[3rem] p-12 mb-12 relative overflow-hidden" data-aos="fade-up">
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-[#bcff00]/5 rounded-full blur-[100px]"></div>
        
        <div class="flex flex-col md:flex-row items-center md:items-start space-y-8 md:space-y-0 md:space-x-12 relative z-10">
            <!-- Avatar -->
            <div class="relative group">
                <div class="w-40 h-40 rounded-[2.5rem] overflow-hidden border-4 border-[#bcff00]/20 p-2 bg-black/40">
                    <?php if(!empty($user['profile_image']) && file_exists('assets/img/users/'.$user['profile_image'])): ?>
                        <img src="assets/img/users/<?php echo $user['profile_image']; ?>" class="w-full h-full object-cover rounded-[2rem]">
                    <?php else: ?>
                        <div class="w-full h-full rounded-[2rem] bg-[#bcff00] flex items-center justify-center text-black text-5xl font-black italic">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="absolute -bottom-3 -right-3 w-12 h-12 bg-[#bcff00] rounded-2xl flex items-center justify-center text-black shadow-xl shadow-[#bcff00]/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Info -->
            <div class="flex-1 text-center md:text-left">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-2">
                    <h1 class="text-5xl font-bold tracking-tighter"><?php echo $user['name']; ?></h1>
                    <button onclick="document.getElementById('edit-profile-modal').classList.remove('hidden')" class="mt-4 md:mt-0 px-6 py-2 border border-white/20 rounded-full text-sm font-bold hover:bg-white/10 transition">
                        Edit Profile
                    </button>
                </div>
                <p class="text-gray-500 text-lg mb-6"><?php echo $user['email']; ?></p>
                
                <div class="flex flex-wrap justify-center md:justify-start gap-4">
                    <span class="px-6 py-2 bg-[#bcff00]/10 border border-[#bcff00]/30 rounded-full text-[#bcff00] text-xs font-bold uppercase tracking-widest italic">
                        <?php echo $badge; ?>
                    </span>
                    <span class="px-6 py-2 bg-white/5 border border-white/10 rounded-full text-gray-400 text-xs font-bold uppercase tracking-widest">
                        Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Impact Section (The ReWear Inspired Part) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        <!-- Main Impact Card -->
        <div class="lg:col-span-2 bg-[#bcff00] text-black rounded-[3rem] p-12 relative overflow-hidden group" data-aos="fade-right">
            <div class="absolute right-0 top-0 opacity-10 group-hover:scale-110 transition duration-1000">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-96 w-96 -mr-20 -mt-20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-9l6 4.5-6 4.5z"/>
                </svg>
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-16 h-16 rounded-2xl bg-black/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9-9c1.657 0 3 4.03 3 9s-1.343 9-3 9m0-18c-1.657 0-3 4.03-3 9s1.343 9 3 9" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black uppercase tracking-tighter italic">Your Circular Impact</h3>
                </div>

                <h2 class="text-6xl font-black mb-4 tracking-tighter italic">You saved <span class="underline decoration-black/20"><?php echo number_format($waste_saved, 1); ?>kg</span> of textile waste!</h2>
                <p class="text-black/60 text-xl font-medium max-w-lg leading-relaxed">By choosing pre-loved fashion, you've prevented garments from ending up in landfills. Keep up the great work!</p>
                
                <div class="mt-12 flex items-center space-x-12">
                    <div>
                        <p class="text-[10px] uppercase font-bold tracking-widest opacity-60 mb-1">Water Conserved</p>
                        <p class="text-4xl font-black italic"><?php echo number_format($water_saved); ?> L</p>
                    </div>
                    <div class="w-px h-12 bg-black/10"></div>
                    <div>
                        <p class="text-[10px] uppercase font-bold tracking-widest opacity-60 mb-1">Items Rescued</p>
                        <p class="text-4xl font-black italic"><?php echo $items_bought; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Level Card -->
        <div class="bg-white/5 border border-white/10 rounded-[3rem] p-12 flex flex-col justify-between" data-aos="fade-left">
            <div>
                <h3 class="font-bold uppercase tracking-widest text-xs text-gray-500 mb-8">Current Level</h3>
                <div class="relative w-40 h-40 mx-auto mb-8">
                    <!-- Simple Circular Progress -->
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="80" cy="80" r="70" stroke="currentColor" stroke-width="8" fill="transparent" class="text-white/5" />
                        <?php 
                            $progress = ($items_bought % 10) * 10; // Level up every 10 items
                            $circumference = 2 * pi() * 70;
                            $offset = $circumference - ($progress / 100) * $circumference;
                        ?>
                        <circle cx="80" cy="80" r="70" stroke="#bcff00" stroke-width="8" stroke-dasharray="<?php echo $circumference; ?>" stroke-dashoffset="<?php echo $offset; ?>" stroke-linecap="round" fill="transparent" class="transition-all duration-1000" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black italic"><?php echo floor($items_bought / 10) + 1; ?></span>
                        <span class="text-[9px] uppercase font-bold text-gray-500">Tier</span>
                    </div>
                </div>
                <p class="text-center text-sm font-bold text-gray-400"><?php echo (10 - ($items_bought % 10)); ?> items until next level</p>
            </div>
            
            <a href="shop.php" class="neon-btn w-full text-center py-4 rounded-2xl font-bold shadow-lg shadow-[#bcff00]/10 mt-8">
                Boost Your Score
            </a>
        </div>
    </div>

    <!-- Activity Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white/5 border border-white/10 p-8 rounded-3xl group hover:border-[#bcff00]/30 transition">
            <p class="text-[10px] uppercase font-bold tracking-widest text-gray-500 mb-4">Total Invested</p>
            <p class="text-3xl font-black italic group-hover:text-[#bcff00] transition">₹<?php echo number_format($total_spent); ?></p>
        </div>
        <div class="bg-white/5 border border-white/10 p-8 rounded-3xl group hover:border-[#bcff00]/30 transition">
            <p class="text-[10px] uppercase font-bold tracking-widest text-gray-500 mb-4">Carbon Offset</p>
            <p class="text-3xl font-black italic group-hover:text-[#bcff00] transition"><?php echo number_format($items_bought * 4.2, 1); ?> kg</p>
        </div>
        <div class="bg-white/5 border border-white/10 p-8 rounded-3xl group hover:border-[#bcff00]/30 transition">
            <p class="text-[10px] uppercase font-bold tracking-widest text-gray-500 mb-4">Active Wishlist</p>
            <p class="text-3xl font-black italic group-hover:text-[#bcff00] transition">
                <?php echo $conn->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = $user_id")->fetch_assoc()['count']; ?>
            </p>
        </div>
        <div class="bg-white/5 border border-white/10 p-8 rounded-3xl group hover:border-[#bcff00]/30 transition">
            <p class="text-[10px] uppercase font-bold tracking-widest text-gray-500 mb-4">Unread Alerts</p>
            <p class="text-3xl font-black italic group-hover:text-[#bcff00] transition">
                <?php echo $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0")->fetch_assoc()['count']; ?>
            </p>
        </div>
    </div>
</section>

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="document.getElementById('edit-profile-modal').classList.add('hidden')"></div>
    <div class="bg-[#0a0a0a] border border-white/10 p-8 rounded-[2.5rem] w-full max-w-md relative z-10" data-aos="zoom-in">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold">Edit <span class="neon-text">Profile</span></h3>
            <button onclick="document.getElementById('edit-profile-modal').classList.add('hidden')" class="text-gray-500 hover:text-white">&times;</button>
        </div>
        <form action="actions/edit_profile_action.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required maxlength="100" pattern="[A-Za-z .'-]+" title="Name can only contain letters, spaces, apostrophes, periods, and hyphens" oninput="this.value=this.value.replace(/[0-9]/g,'')" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">New Password (Optional)</label>
                <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus:outline-none focus:border-[#bcff00] text-white">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Profile Image (Optional)</label>
                <input type="file" name="profile_image" accept="image/*" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-[#bcff00]/10 file:text-[#bcff00] hover:file:bg-[#bcff00]/20">
            </div>
        <button type="submit" class="neon-btn w-full font-bold py-4 rounded-xl mt-4">Save Changes</button>
        </form>
    </div>
</div>

<!-- Danger Zone: Delete Account (OUTSIDE the modal, on the page) -->
<section class="pb-16 px-6 max-w-6xl mx-auto">
    <div class="mt-4 bg-red-500/5 border border-red-500/20 rounded-[2rem] p-8" data-aos="fade-up">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-red-500 mb-1">Delete Account</h3>
                <p class="text-gray-500 text-sm">Permanently delete your account and all associated data. This action is <strong class="text-red-500">irreversible</strong>.</p>
            </div>
            <button onclick="document.getElementById('delete-account-modal').classList.remove('hidden')"
                class="bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/20 font-bold px-6 py-3 rounded-xl text-sm transition flex-shrink-0 ml-6">
                Delete Account
            </button>
        </div>
    </div>
</section>

<!-- Delete Account Modal -->

<div id="delete-account-modal" class="fixed inset-0 z-[200] hidden flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-black/90 backdrop-blur-sm" onclick="document.getElementById('delete-account-modal').classList.add('hidden')"></div>
    <div class="bg-[#0a0a0a] border border-red-500/30 p-8 rounded-[2.5rem] w-full max-w-md relative z-10 shadow-2xl">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-red-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-red-500 mb-2">Delete Account?</h3>
            <p class="text-gray-400 text-sm">This will permanently delete your account, all your listings, orders history, chats, and wishlist. This cannot be undone.</p>
        </div>
        <form id="delete-account-form" class="space-y-4">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Confirm your Password</label>
                <input type="password" id="delete-confirm-password" name="password" required placeholder="Enter your current password"
                    class="w-full bg-white/5 border border-red-500/20 rounded-xl px-4 py-3 focus:outline-none focus:border-red-500 text-white">
            </div>
            <div class="flex space-x-3 pt-2">
                <button type="button" onclick="document.getElementById('delete-account-modal').classList.add('hidden')"
                    class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 font-bold py-4 rounded-xl text-sm transition">
                    Cancel
                </button>
                <button type="submit" id="delete-confirm-btn"
                    class="flex-1 bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/20 font-bold py-4 rounded-xl text-sm transition">
                    Yes, Delete Forever
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('delete-account-form').onsubmit = function(e) {
    e.preventDefault();
    const btn = document.getElementById('delete-confirm-btn');
    const password = document.getElementById('delete-confirm-password').value;
    if (!password) return;
    btn.disabled = true;
    btn.innerText = 'Deleting...';
    const fd = new FormData();
    fd.append('password', password);
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
    fetch('actions/delete_account_action.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Your account has been permanently deleted.');
                window.location.href = 'index.php';
            } else {
                alert('Error: ' + (data.error || 'Could not delete account.'));
                btn.disabled = false;
                btn.innerText = 'Yes, Delete Forever';
            }
        })
        .catch(() => {
            alert('An unexpected error occurred.');
            btn.disabled = false;
            btn.innerText = 'Yes, Delete Forever';
        });
};
</script>

<?php include 'includes/footer.php'; ?>
