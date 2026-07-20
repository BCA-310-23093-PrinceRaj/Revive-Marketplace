<aside id="admin-sidebar" class="fixed left-0 top-0 h-screen w-72 bg-[#0a0a0a] border-r border-white/5 z-[60] transition-transform -translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-8 flex flex-col min-h-full">
        <a href="index.php" class="text-2xl font-bold tracking-tighter mb-12 block">
            REVIVE<span class="neon-text">.</span>
        </a>

        <nav class="space-y-2 flex-1">
            <p class="text-[10px] uppercase tracking-widest text-gray-600 font-bold mb-4 ml-4">Main Menu</p>
            
            <?php $active_section = $_GET['section'] ?? 'dashboard'; ?>
            
            <a href="admin_dashboard.php?section=dashboard" class="flex items-center space-x-4 px-4 py-3 rounded-2xl <?php echo $active_section == 'dashboard' ? 'bg-[#bcff00] text-black font-bold shadow-lg shadow-[#bcff00]/10' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>

            <?php $pending_count = $conn->query("SELECT COUNT(*) as c FROM products WHERE status = 'pending'")->fetch_assoc()['c'] ?? 0; ?>
            <a href="admin_dashboard.php?section=inventory" class="flex items-center space-x-4 px-4 py-3 rounded-2xl <?php echo $active_section == 'inventory' ? 'bg-[#bcff00] text-black font-bold shadow-lg shadow-[#bcff00]/10' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span class="flex-1">Inventory</span>
                <?php if ($pending_count > 0): ?>
                <span class="bg-yellow-400 text-black text-[9px] font-black px-2 py-0.5 rounded-full"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>

            <a href="admin_dashboard.php?section=orders" class="flex items-center space-x-4 px-4 py-3 rounded-2xl <?php echo $active_section == 'orders' ? 'bg-[#bcff00] text-black font-bold shadow-lg shadow-[#bcff00]/10' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span>Orders</span>
            </a>

            <a href="admin_dashboard.php?section=reports" class="flex items-center space-x-4 px-4 py-3 rounded-2xl <?php echo $active_section == 'reports' ? 'bg-[#bcff00] text-black font-bold shadow-lg shadow-[#bcff00]/10' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span>Reports</span>
            </a>

            <a href="admin_dashboard.php?section=categories" class="flex items-center space-x-4 px-4 py-3 rounded-2xl <?php echo $active_section == 'categories' ? 'bg-[#bcff00] text-black font-bold shadow-lg shadow-[#bcff00]/10' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <span>Categories</span>
            </a>

            <a href="admin_dashboard.php?section=community" class="flex items-center space-x-4 px-4 py-3 rounded-2xl <?php echo $active_section == 'community' ? 'bg-[#bcff00] text-black font-bold shadow-lg shadow-[#bcff00]/10' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 01-12 0v1zm0 0h6v-1a6 6 0 01-12 0v1z" />
                </svg>
                <span>Community</span>
            </a>

            <a href="admin_dashboard.php?section=messages" class="flex items-center space-x-4 px-4 py-3 rounded-2xl <?php echo $active_section == 'messages' ? 'bg-[#bcff00] text-black font-bold shadow-lg shadow-[#bcff00]/10' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <span>Inquiries</span>
            </a>

            <a href="admin_dashboard.php?section=disputes" class="flex items-center space-x-4 px-4 py-3 rounded-2xl <?php echo $active_section == 'disputes' ? 'bg-[#bcff00] text-black font-bold shadow-lg shadow-[#bcff00]/10' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Disputes</span>
            </a>

        </nav>

        <!-- Bottom Section: Profile & Logout -->
        <div class="mt-auto pt-8 space-y-4 border-t border-white/5">
            <a href="logout.php" class="flex items-center space-x-4 px-4 py-3 rounded-2xl text-red-500/60 hover:bg-red-500/5 hover:text-red-500 transition group border border-red-500/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="text-sm font-bold">Terminate Session</span>
            </a>

            <div class="p-4 rounded-3xl bg-white/5 border border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#bcff00] flex items-center justify-center font-bold text-black">
                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="overflow-hidden">
                        <p class="font-bold text-sm truncate"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-widest">Platform Admin</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
