<?php 
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revive | Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; }
        .admin-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-white tracking-tight">REVIVE <span class="text-purple-500">ADMIN</span></h1>
            <p class="text-slate-400 mt-2">Enter your credentials to access the control center.</p>
        </div>

        <div class="admin-card p-8 rounded-3xl shadow-2xl">
            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 text-sm p-4 rounded-xl mb-6 text-center">
                    Invalid credentials or unauthorized access.
                </div>
            <?php endif; ?>

            <form action="actions/auth_action.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="admin_login">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Admin Email</label>
                    <input type="email" name="email" required
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-purple-500 transition text-white"
                        placeholder="admin@revive.com">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Secret Password</label>
                    <input type="password" name="password" required
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-purple-500 transition text-white"
                        placeholder="••••••••">
                </div>

                <button type="submit" 
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl transition shadow-lg shadow-purple-500/20">
                    Authenticate
                </button>
            </form>
        </div>

        <div class="text-center mt-8">
            <a href="index.php" class="text-slate-500 hover:text-slate-300 text-sm transition">&larr; Back to Public Site</a>
        </div>
    </div>

</body>
</html>
