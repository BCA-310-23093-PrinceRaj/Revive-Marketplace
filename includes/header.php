<?php 
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'config/db.php';

// Enforce real-time ban status check
if (isset($_SESSION['user_id'])) {
    $session_uid = (int)$_SESSION['user_id'];
    $ban_check = $conn->query("SELECT status FROM users WHERE id = $session_uid")->fetch_assoc();
    if ($ban_check && $ban_check['status'] === 'banned') {
        session_unset();
        session_destroy();
        header("Location: login.php?error=banned");
        exit();
    }
}

// CSRF Security Token Initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Revive - The premium destination for pre-loved luxury and streetwear. Buy and sell sustainable fashion securely.">
    <meta name="keywords" content="fashion, streetwear, luxury, second-hand, sustainable, marketplace, revive">
    <title>Revive | Modern Second-Hand Fashion</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- AOS Library for Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #050505;
            color: #ffffff;
        }
        .glass-nav {
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .neon-text {
            color: #bcff00; /* Electric Lime */
            text-shadow: 0 0 10px rgba(188, 255, 0, 0.5);
        }
        .neon-btn {
            background-color: #bcff00;
            color: #000;
            transition: all 0.3s ease;
        }
        .neon-btn:hover {
            box-shadow: 0 0 20px rgba(188, 255, 0, 0.8);
            transform: translateY(-2px);
        }
        /* Toast Notifications */
        .toast {
            position: fixed;
            top: 90px;        /* navbar ke neeche enough gap */
            right: 24px;
            max-width: calc(100vw - 48px);
            transform: translateY(-20px);
            opacity: 0;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 14px 28px;
            border-radius: 20px;
            z-index: 60;      /* navbar z-50 se upar, dropdowns se neeche */
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            pointer-events: none;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .toast.success { border-color: #bcff00; color: #bcff00; }
        .toast.error { border-color: #ff4d4d; color: #ff4d4d; }

        /* Autofill Dark Mode Fix */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #111111 inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="glass-nav sticky top-0 z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <!-- Mobile Menu Toggle -->
            <button onclick="toggleMobileMenu()" class="md:hidden p-2 text-gray-400 hover:text-[#bcff00] transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
            </button>

            <a href="index.php" class="text-3xl font-bold tracking-tighter">
                REVIVE<span class="neon-text">.</span>
            </a>
            
            <div class="hidden md:flex space-x-8 items-center font-medium">
                <?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                    <a href="index.php" class="hover:text-white transition">Home</a>
                    <a href="shop.php" class="hover:text-white transition">Shop</a>
                    
                    <!-- Search Bar -->
                    <?php if (basename($_SERVER['PHP_SELF']) !== 'shop.php'): ?>
                    <div class="relative group ml-4">
                        <form action="shop.php" method="GET">
                            <input type="text" id="search-input" name="search" autocomplete="off" placeholder="Search brands, styles..." 
                                class="bg-white/5 border border-white/10 rounded-full px-5 py-2 pl-10 text-sm focus:outline-none focus:border-[#bcff00] w-48 transition-all focus:w-80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 group-focus-within:text-[#bcff00] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </form>
                        
                        <!-- Suggestions Dropdown -->
                        <div id="search-suggestions" class="absolute left-0 right-0 mt-4 bg-[#0a0a0a] border border-white/10 rounded-[2rem] shadow-2xl z-[110] opacity-0 pointer-events-none transition-all duration-300 translate-y-4 overflow-hidden">
                            <!-- Dynamic Results -->
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="flex items-center space-x-2 text-[10px] font-bold uppercase tracking-[0.2em] text-[#bcff00]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#bcff00] animate-pulse"></span>
                        <span>Secure Admin Environment</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex items-center space-x-4 md:space-x-6">
                <?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                    <!-- Wishlist Icon -->
                    <a href="wishlist.php" class="relative group p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400 group-hover:text-red-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span id="wishlist-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center hidden">0</span>
                    </a>

                    <!-- Cart Icon -->
                    <button onclick="toggleCart()" class="relative group p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400 group-hover:text-[#bcff00] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span id="cart-count" class="absolute -top-1 -right-1 bg-[#bcff00] text-black text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center hidden">0</span>
                    </button>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button onclick="toggleNotifications()" class="relative group p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400 group-hover:text-[#bcff00] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span id="notif-badge" class="absolute top-1 right-1 w-2 h-2 bg-[#bcff00] rounded-full border-2 border-black hidden"></span>
                        </button>
                        
                        <div id="notif-dropdown" class="absolute right-0 mt-4 w-80 bg-[#0a0a0a] border border-white/10 rounded-[2rem] shadow-2xl z-[100] opacity-0 pointer-events-none transition-all duration-300 scale-95 origin-top-right overflow-hidden">
                            <div class="p-6 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                                <h4 class="font-bold text-sm uppercase tracking-widest">Alerts</h4>
                                <button onclick="markAllRead()" class="text-[10px] text-red-500 font-bold uppercase tracking-widest hover:underline">Delete All</button>
                            </div>
                            <div id="notif-items" class="max-h-[400px] overflow-y-auto">
                                <!-- Notifications loaded here -->
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- Account Dropdown -->
                    <div class="relative">
                        <button onclick="toggleAccount()" class="flex items-center space-x-3 group p-1.5 rounded-full bg-white/5 border border-white/10 hover:border-[#bcff00]/50 transition">
                            <?php 
                            $u_id = $_SESSION['user_id'];
                            $u_data = $conn->query("SELECT profile_image FROM users WHERE id = $u_id")->fetch_assoc();
                            if(!empty($u_data['profile_image']) && file_exists('assets/img/users/'.$u_data['profile_image'])): 
                            ?>
                                <img src="assets/img/users/<?php echo $u_data['profile_image']; ?>" class="w-8 h-8 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-[#bcff00] flex items-center justify-center font-bold text-black text-xs">
                                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="account-dropdown" class="absolute right-0 mt-4 w-64 bg-[#0a0a0a] border border-white/10 rounded-[2rem] shadow-2xl z-[100] opacity-0 pointer-events-none transition-all duration-300 scale-95 origin-top-right overflow-hidden">
                            <div class="p-6 border-b border-white/5 bg-white/[0.02]">
                                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold mb-1">Signed in as</p>
                                <p class="font-bold truncate"><?php echo $_SESSION['user_name']; ?></p>
                            </div>
                            <div class="p-4 space-y-1">
                                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <!-- Admin-only dropdown links -->
                                    <a href="admin_dashboard.php" class="flex items-center space-x-3 p-4 rounded-2xl hover:bg-white/5 transition group">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 group-hover:text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                                        <span class="text-sm font-medium">Admin Dashboard</span>
                                    </a>
                                <?php else: ?>
                                    <!-- Buyer/Seller dropdown links -->
                                    <a href="profile.php" class="flex items-center space-x-3 p-4 rounded-2xl hover:bg-white/5 transition group">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 group-hover:text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        <span class="text-sm font-medium">Profile & Impact</span>
                                    </a>
                                    <a href="my_orders.php" class="flex items-center space-x-3 p-4 rounded-2xl hover:bg-white/5 transition group">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 group-hover:text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                        <span class="text-sm font-medium">My Purchases</span>
                                    </a>
                                    <a href="chat.php" class="flex items-center space-x-3 p-4 rounded-2xl hover:bg-white/5 transition group">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 group-hover:text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                                        <span class="text-sm font-medium">Messages</span>
                                    </a>
                                    <div class="h-px bg-white/5 my-2 mx-4"></div>
                                    <a href="seller_dashboard.php" class="flex items-center space-x-3 p-4 rounded-2xl hover:bg-white/5 transition group">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 group-hover:text-[#bcff00]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                        <span class="text-sm font-medium">Selling Mode</span>
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php" class="flex items-center space-x-3 p-4 rounded-2xl hover:bg-red-500/5 transition group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500/50 group-hover:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    <span class="text-sm font-medium text-red-500/80 group-hover:text-red-500">Sign Out</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="text-gray-400 hover:text-white text-sm font-medium transition hidden md:block">Login</a>
                    <a href="register.php" class="neon-btn font-bold px-6 py-2 rounded-full text-sm hidden sm:block">
                        Join
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <div id="mobile-menu-overlay" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[110] opacity-0 pointer-events-none transition-opacity duration-300 md:hidden" onclick="toggleMobileMenu()"></div>
    <div id="mobile-menu" class="fixed left-0 top-0 h-full w-full max-w-[300px] bg-[#050505] border-r border-white/10 z-[111] -translate-x-full transition-transform duration-500 md:hidden flex flex-col p-8">
        <div class="flex justify-between items-center mb-12">
            <span class="text-2xl font-bold tracking-tighter">REVIVE<span class="neon-text">.</span></span>
            <button onclick="toggleMobileMenu()" class="text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex flex-col space-y-6 text-xl font-bold">
            <?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                <a href="index.php" class="hover:text-[#bcff00] transition">Home</a>
                <a href="shop.php" class="hover:text-[#bcff00] transition">Shop</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="my_orders.php" class="hover:text-[#bcff00] transition">My Orders</a>
                    <a href="wishlist.php" class="hover:text-[#bcff00] transition">Wishlist</a>
                    <a href="chat.php" class="hover:text-[#bcff00] transition">Messages</a>
                    <a href="seller_dashboard.php" class="hover:text-[#bcff00] transition">Seller Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="admin_dashboard.php" class="hover:text-[#bcff00] transition">Admin Dashboard</a>
                <a href="admin_dashboard.php#approvals" class="hover:text-[#bcff00] transition">Approvals</a>
            <?php endif; ?>

            <?php if(isset($_SESSION['user_id'])): ?>
                <hr class="border-white/10 my-4">
                <a href="logout.php" class="text-red-500 text-sm uppercase tracking-widest">Logout</a>
            <?php else: ?>
                <a href="login.php" class="hover:text-[#bcff00] transition">Login</a>
                <a href="register.php" class="text-[#bcff00] transition">Join Community</a>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </nav>

    <?php if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
    <!-- Cart Drawer Overlay -->
    <div id="cart-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] opacity-0 pointer-events-none transition-opacity duration-300" onclick="toggleCart()"></div>
    
    <!-- Cart Drawer -->
    <div id="cart-drawer" class="fixed right-0 top-0 h-full w-full max-w-md bg-[#0a0a0a] border-l border-white/10 z-[101] translate-x-full transition-transform duration-500 flex flex-col">
        <div class="p-8 border-b border-white/10 flex justify-between items-center">
            <h2 class="text-2xl font-bold">Your <span class="neon-text">Bag</span></h2>
            <button onclick="toggleCart()" class="text-gray-500 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="cart-items" class="flex-1 overflow-y-auto p-8 space-y-6">
            <!-- Items loaded here -->
        </div>

        <div class="p-8 border-t border-white/10 bg-white/[0.02]">
            <div class="flex justify-between items-center mb-6">
                <span class="text-gray-400 font-medium">Subtotal</span>
                <span id="cart-total" class="text-2xl font-bold text-[#bcff00]">₹0</span>
            </div>
            <a href="checkout.php" class="neon-btn w-full block text-center font-bold py-4 rounded-2xl shadow-lg">
                Checkout Now
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div id="toast-container" class="toast"></div>

    <script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast-container');
        toast.innerText = message;
        toast.className = `toast show ${type}`;
        
        setTimeout(() => {
            toast.className = 'toast';
        }, 4000);
    }

    // Check URL for messages (skip 'logged_in' — index.php handles that separately)
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            const val = urlParams.get('success');
            if (val !== 'logged_in') { // index.php has its own welcome toast
                const msg = val.replace(/_/g, ' ');
                showToast(msg.charAt(0).toUpperCase() + msg.slice(1), 'success');
            }
        }
        if (urlParams.has('error')) {
            const msg = urlParams.get('error').replace('_', ' ');
            showToast(msg.charAt(0).toUpperCase() + msg.slice(1), 'error');
        }

        // AOS Initialization
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                once: true,
                easing: 'ease-out-quad'
            });
        }
    });

    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        const overlay = document.getElementById('mobile-menu-overlay');
        const isClosed = menu.classList.contains('-translate-x-full');
        
        if(isClosed) {
            menu.classList.remove('-translate-x-full');
            overlay.classList.remove('opacity-0', 'pointer-events-none');
        } else {
            menu.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0', 'pointer-events-none');
        }
    }

    function toggleCart() {
        const drawer = document.getElementById('cart-drawer');
        const overlay = document.getElementById('cart-overlay');
        const isClosed = drawer.classList.contains('translate-x-full');
        
        if(isClosed) {
            drawer.classList.remove('translate-x-full');
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            loadCart();
        } else {
            drawer.classList.add('translate-x-full');
            overlay.classList.add('opacity-0', 'pointer-events-none');
        }
    }

    function loadCart() {
        fetch('actions/cart_action.php?action=get')
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('cart-items');
                const countBadge = document.getElementById('cart-count');
                
                if(data.count > 0) {
                    countBadge.innerText = data.count;
                    countBadge.classList.remove('hidden');
                    container.innerHTML = data.items.map(item => `
                        <div class="flex items-center space-x-4 group">
                            <div class="w-20 h-24 rounded-xl bg-white/5 overflow-hidden border border-white/10 flex-shrink-0">
                                <img src="assets/img/products/${item.images}" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-sm mb-1">${item.title}</h4>
                                <p class="text-[#bcff00] font-bold">₹${parseInt(item.price).toLocaleString()}</p>
                                <div class="flex items-center mt-2 space-x-4">
                                    <span class="text-xs text-gray-500 italic">Qty: ${item.quantity}</span>
                                    <button onclick="removeFromCart(${item.id})" class="text-red-500 text-[10px] uppercase font-bold tracking-widest hover:underline">Remove</button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    countBadge.classList.add('hidden');
                    container.innerHTML = '<div class="text-center py-20 text-gray-600"><p class="mb-4">Your bag is empty.</p><a href="shop.php?view=products" class="text-[#bcff00] font-bold">Explore Shop</a></div>';
                }
                document.getElementById('cart-total').innerText = '₹' + parseInt(data.total).toLocaleString();
            });
    }

    function addToCart(productId) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        
        fetch('actions/cart_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    toggleCart();
                } else if(data.error === 'Login required') {
                    window.location.href = 'login.php';
                } else if(data.error) {
                    showToast(data.error, 'error');
                }
            });
    }

    function removeFromCart(cartId) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('cart_id', cartId);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        
        fetch('actions/cart_action.php', { method: 'POST', body: formData })
            .then(() => loadCart());
    }

    function toggleWishlist(productId, btnElement) {
        const formData = new FormData();
        formData.append('action', 'toggle');
        formData.append('product_id', productId);
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        
        fetch('actions/wishlist_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const icon = btnElement.querySelector('svg');
                    if(data.status === 'added') {
                        icon.classList.add('fill-red-500', 'text-red-500');
                        icon.classList.remove('text-gray-400');
                        showToast('Added to Wishlist');
                    } else {
                        icon.classList.remove('fill-red-500', 'text-red-500');
                        icon.classList.add('text-gray-400');
                        showToast('Removed from Wishlist');
                    }
                    updateWishlistCount();
                } else if(data.error === 'Login required') {
                    window.location.href = 'login.php';
                }
            });
    }

    function updateWishlistCount() {
        fetch('actions/wishlist_action.php?action=get_count')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('wishlist-count');
                if(data.count > 0) {
                    badge.innerText = data.count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
    }

    function toggleNotifications() {
        const dropdown = document.getElementById('notif-dropdown');
        const badge = document.getElementById('notif-badge');
        const isClosed = dropdown.classList.contains('opacity-0');
        
        if(isClosed) {
            dropdown.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
            loadNotifications();
            // Automatically mark as read when opened so the green dot vanishes instantly
            if (!badge.classList.contains('hidden')) {
                fetch('actions/notification_action.php?action=mark_read').then(() => {
                    badge.classList.add('hidden');
                });
            }
        } else {
            dropdown.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
        }
    }

    function loadNotifications() {
        fetch('actions/notification_action.php?action=get')
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('notif-items');
                const badge = document.getElementById('notif-badge');
                
                if(data.unread > 0) {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                if(data.notifications.length > 0) {
                    container.innerHTML = data.notifications.map(n => `
                        <div class="p-6 border-b border-white/5 hover:bg-white/[0.02] transition cursor-default">
                            <p class="text-sm text-gray-300 leading-relaxed mb-2">${n.message}</p>
                            <span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest">${n.time_ago}</span>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="p-12 text-center text-gray-600 text-xs italic">No new notifications.</div>';
                }
            });
    }

    function markAllRead() {
        fetch('actions/notification_action.php?action=clear_all')
            .then(() => {
                loadNotifications();
                showToast('All notifications deleted', 'success');
            });
    }

    function toggleAccount() {
        const dropdown = document.getElementById('account-dropdown');
        const isClosed = dropdown.classList.contains('opacity-0');
        
        if(isClosed) {
            dropdown.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
        } else {
            dropdown.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
        }
    }

    // Close dropdowns on outside click
    window.addEventListener('click', (e) => {
        if (!e.target.closest('button')) {
            document.getElementById('notif-dropdown')?.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
            document.getElementById('account-dropdown')?.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
        }
    });

    // Smart Search Logic
    const searchInput = document.getElementById('search-input');
    const suggestionsBox = document.getElementById('search-suggestions');
    let searchTimeout;

    if(searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value;

            if(query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    fetch(`actions/search_suggestions.php?q=${query}`)
                        .then(res => res.json())
                        .then(data => {
                            if(data.length > 0) {
                                suggestionsBox.innerHTML = data.map(item => `
                                    <a href="product_details.php?id=${item.id}" class="flex items-center space-x-4 p-4 hover:bg-white/[0.03] transition group border-b border-white/5 last:border-0">
                                        <div class="w-12 h-12 rounded-xl bg-white/5 overflow-hidden flex-shrink-0">
                                            <img src="assets/img/products/${item.images}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-white mb-0.5 line-clamp-1">${item.title}</p>
                                            <p class="text-[10px] uppercase font-bold text-[#bcff00]">₹${parseInt(item.price).toLocaleString()} • ${item.brand}</p>
                                        </div>
                                    </a>
                                `).join('') + `
                                    <button onclick="document.forms[0].submit()" class="w-full p-4 text-center text-[10px] uppercase font-black tracking-widest text-gray-500 hover:text-white transition bg-white/[0.02]">
                                        View All Results
                                    </button>
                                `;
                                suggestionsBox.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
                            } else {
                                suggestionsBox.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
                            }
                        });
                }, 300);
            } else {
                suggestionsBox.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
            }
        });

        // Close search on escape
        document.addEventListener('keydown', (e) => {
            if(e.key === 'Escape') suggestionsBox.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
        });
    }

    // Initial count load
    document.addEventListener('DOMContentLoaded', () => {
        updateWishlistCount();
        loadNotifications(); // Load unread badge on start
        fetch('actions/cart_action.php?action=get')
            .then(res => res.json())
            .then(data => {
                const countBadge = document.getElementById('cart-count');
                if(data.count > 0) {
                    countBadge.innerText = data.count;
                    countBadge.classList.remove('hidden');
                }
            });
    });
    </script>
