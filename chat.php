<?php 
require_once 'config/db.php';

// Auth check BEFORE any HTML output
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$chat_with = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Fetch other user info if chatting with someone specific
$other_user_name = "Select a Chat";
if($chat_with > 0) {
    $res = $conn->query("SELECT name FROM users WHERE id = $chat_with");
    if($res->num_rows > 0) $other_user_name = $res->fetch_assoc()['name'];
}
?>

<section class="h-[80vh] px-6 py-10 max-w-7xl mx-auto">
    <div class="glass-nav rounded-[2.5rem] border border-white/10 h-full overflow-hidden flex">
        
        <!-- Sidebar: Contacts -->
        <div class="w-full md:w-80 border-r border-white/10 flex flex-col <?php echo $chat_with > 0 ? 'hidden md:flex' : ''; ?>">
            <div class="p-6 border-b border-white/10">
                <h2 class="text-xl font-bold">Messages</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-2" id="contacts-list">
                <!-- Contacts will be loaded here via JS -->
                <p class="text-gray-500 text-sm p-4">Loading chats...</p>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="<?php echo $chat_with > 0 ? 'flex' : 'hidden md:flex'; ?> flex-1 flex-col relative">
            <?php if($chat_with > 0): ?>
                <!-- Chat Header -->
                <div class="p-6 border-b border-white/10 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="chat.php" class="md:hidden p-2 -ml-2 text-gray-400 hover:text-[#bcff00] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                        <div class="w-10 h-10 rounded-full bg-[#bcff00] text-black flex items-center justify-center font-bold">
                            <?php echo strtoupper(substr($other_user_name, 0, 1)); ?>
                        </div>
                        <h3 class="font-bold text-lg"><?php echo $other_user_name; ?></h3>
                    </div>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto p-8 space-y-6" id="messages-container">
                    <!-- Messages will be loaded here via JS -->
                    <div class="flex justify-center py-20">
                        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-[#bcff00]"></div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-6 bg-black/40 border-t border-white/10 relative">
                    <form id="chat-form" class="flex items-center space-x-4">
                        <input type="hidden" id="receiver_id" value="<?php echo $chat_with; ?>">
                        <input type="hidden" id="context_product_id" value="<?php echo $product_id > 0 ? $product_id : ''; ?>">
                        
                        <!-- File Upload Button -->
                        <label class="cursor-pointer p-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl transition text-gray-400 hover:text-white" title="Attach Image">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <input type="file" id="chat-image" accept="image/*" class="hidden">
                        </label>
                        <span id="file-name-display" class="hidden absolute top-0 left-6 -mt-8 bg-[#bcff00]/20 text-[#bcff00] px-4 py-1 rounded-full text-xs font-bold border border-[#bcff00]/30 shadow-lg"></span>

                        <input type="text" id="message-input" placeholder="Type a message..."
                            class="flex-1 bg-white/5 border border-white/10 rounded-2xl px-6 py-4 focus:outline-none focus:border-[#bcff00] transition">
                        <button type="submit" class="neon-btn font-bold px-8 py-4 rounded-2xl">
                            Send
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="flex-1 flex flex-col items-center justify-center text-center p-12">
                    <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold mb-2">Your Conversations</h2>
                    <p class="text-gray-500">Select a contact from the left to start chatting.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const receiverId = document.getElementById('receiver_id')?.value;
    const msgContainer = document.getElementById('messages-container');
    const contactsList = document.getElementById('contacts-list');
    const chatForm = document.getElementById('chat-form');
    const msgInput = document.getElementById('message-input');

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    function loadContacts() {
        fetch('actions/chat_action.php?action=get_contacts')
            .then(res => res.json())
            .then(data => {
                contactsList.innerHTML = data.map(c => `
                    <a href="chat.php?user_id=${c.id}" class="block p-4 rounded-2xl ${c.id == receiverId ? 'bg-[#bcff00]/10 border border-[#bcff00]/20' : 'hover:bg-white/5'} transition">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center font-bold text-xs">
                                ${c.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm truncate">${escapeHTML(c.name)}</p>
                                <p class="${c.unread_count > 0 && c.id != receiverId ? 'text-white font-bold' : 'text-gray-500'} text-xs truncate">${escapeHTML(c.last_message) || 'Start chatting...'}</p>
                            </div>
                            ${c.unread_count > 0 && c.id != receiverId ? `<div class="w-5 h-5 rounded-full bg-[#bcff00] text-black text-[10px] font-bold flex items-center justify-center">${c.unread_count}</div>` : ''}
                        </div>
                    </a>
                `).join('');
            });
    }

    function loadMessages() {
        if(!receiverId) return;
        fetch(`actions/chat_action.php?action=get_messages&receiver_id=${receiverId}`)
            .then(res => res.json())
            .then(data => {
                const isAtBottom = msgContainer.scrollHeight - msgContainer.scrollTop <= msgContainer.clientHeight + 100;
                msgContainer.innerHTML = data.map(m => {
                    let productPreview = '';
                    if (m.product_id && m.p_title) {
                        productPreview = `
                            <a href="product_details.php?id=${m.product_id}" class="block bg-black/20 border border-[#bcff00]/20 rounded-xl p-3 mb-3 flex items-center space-x-3 hover:bg-white/5 transition">
                                <img src="assets/img/products/${escapeHTML(m.p_images)}" class="w-12 h-12 rounded-lg object-cover border border-[#bcff00]/20">
                                <div>
                                    <p class="text-xs text-[#bcff00] font-bold uppercase tracking-widest mb-1">Discussing</p>
                                    <p class="text-sm font-bold text-white truncate max-w-[150px]">${escapeHTML(m.p_title)}</p>
                                    <p class="text-xs text-white font-bold">₹${parseFloat(m.p_price).toLocaleString('en-IN')}</p>
                                </div>
                            </a>
                        `;
                    }
                    return `
                    <div class="flex ${m.sender_id == <?php echo $user_id; ?> ? 'justify-end' : 'justify-start'}">
                        <div class="max-w-[70%] ${m.sender_id == <?php echo $user_id; ?> ? 'bg-[#bcff00]/10 border border-[#bcff00]/30 text-white rounded-tr-none' : 'bg-white/10 border border-white/10 text-white rounded-tl-none'} p-4 rounded-2xl shadow-lg backdrop-blur-sm">
                            ${productPreview}
                            ${m.image_path ? `<img src="assets/img/chats/${m.image_path}" class="w-full rounded-xl mb-2 object-cover max-h-60 cursor-pointer" onclick="window.open(this.src)">` : ''}
                            ${m.message ? `<p class="text-sm font-medium text-white">${escapeHTML(m.message)}</p>` : ''}
                            <div class="flex justify-between items-center mt-2 space-x-4">
                                <p class="text-[10px] text-gray-400 font-bold">${m.time}</p>
                                ${m.sender_id == <?php echo $user_id; ?> ? `<p class="text-[10px] ${m.is_read == 1 ? 'text-[#bcff00]' : 'text-gray-400'} font-bold">${m.is_read == 1 ? '✓✓ Read' : '✓ Sent'}</p>` : ''}
                            </div>
                        </div>
                    </div>
                    `;
                }).join('');
                if(isAtBottom) msgContainer.scrollTop = msgContainer.scrollHeight;
            });
    }

    const imageInput = document.getElementById('chat-image');
    const fileNameDisplay = document.getElementById('file-name-display');

    if(imageInput) {
        imageInput.onchange = function() {
            if(this.files && this.files[0]) {
                fileNameDisplay.textContent = this.files[0].name;
                fileNameDisplay.classList.remove('hidden');
            } else {
                fileNameDisplay.classList.add('hidden');
            }
        };
    }

    if(chatForm) {
        chatForm.onsubmit = function(e) {
            e.preventDefault();
            const msg = msgInput.value.trim();
            const file = imageInput ? imageInput.files[0] : null;
            const contextProductId = document.getElementById('context_product_id');
            
            if(!msg && !file) return;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', receiverId);
            formData.append('message', msg);
            if(contextProductId && contextProductId.value) {
                formData.append('product_id', contextProductId.value);
            }
            if(file) formData.append('chat_image', file);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

            fetch('actions/chat_action.php', { method: 'POST', body: formData })
                .then(() => {
                    msgInput.value = '';
                    if(imageInput) imageInput.value = '';
                    if(fileNameDisplay) fileNameDisplay.classList.add('hidden');
                    if(contextProductId) contextProductId.value = ''; // clear so it only sends on first message
                    loadMessages();
                });
        };
    }

    loadContacts();
    loadMessages();
    setInterval(loadMessages, 3000); // Poll every 3 seconds
});
</script>

<?php include 'includes/footer.php'; ?>
