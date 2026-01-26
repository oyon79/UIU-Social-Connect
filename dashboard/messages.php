<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

// Prevent admins from accessing user dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/index.php');
    exit;
}

$pageTitle = 'Messages - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body {
        background: var(--gray-light);
    }

    .main-container {
        margin-left: 280px;
        min-height: 100vh;
    }

    .messages-container {
        display: flex;
        height: calc(100vh - 80px);
    }

    .conversations-sidebar {
        width: 380px;
        background: white;
        border-right: 2px solid var(--gray-light);
        display: flex;
        flex-direction: column;
    }

    .conversations-header {
        padding: 1.5rem;
        border-bottom: 2px solid var(--gray-light);
    }

    .conversations-header h2 {
        margin-bottom: 1rem;
    }

    .search-box {
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 3rem;
        border: 2px solid var(--gray-medium);
        border-radius: 12px;
    }

    .search-box svg {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-dark);
    }

    .conversations-list {
        flex: 1;
        overflow-y: auto;
    }

    .conversation-item {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--gray-light);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .conversation-item:hover {
        background: var(--gray-light);
    }

    .conversation-item.active {
        background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 102, 0.1));
        border-left: 4px solid var(--primary-orange);
    }

    .conversation-avatar {
        position: relative;
    }

    .online-indicator {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        background: var(--success);
        border: 2px solid white;
        border-radius: 50%;
    }

    .conversation-info {
        flex: 1;
        min-width: 0;
    }

    .conversation-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .conversation-preview {
        font-size: 0.875rem;
        color: var(--gray-dark);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conversation-meta {
        text-align: right;
    }

    .conversation-time {
        font-size: 0.75rem;
        color: var(--gray-dark);
    }

    .unread-badge {
        background: var(--primary-orange);
        color: white;
        border-radius: 12px;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 700;
        margin-top: 0.25rem;
        display: inline-block;
    }

    .chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: white;
    }

    .chat-header {
        padding: 1.5rem;
        border-bottom: 2px solid var(--gray-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chat-user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        background: #f8f9fa;
    }

    .message {
        display: flex;
        gap: 1rem;
        max-width: 75%;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .message.received {
        margin-right: auto;
        margin-left: 0;
    }

    .message.sent {
        margin-left: auto;
        margin-right: 0;
        flex-direction: row-reverse;
    }

    .message-content {
        background: var(--gray-light);
        padding: 0.875rem 1.25rem;
        border-radius: 16px;
        word-wrap: break-word;
    }

    .message.sent .message-content {
        background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light));
        color: white;
    }

    .message-time {
        font-size: 0.75rem;
        color: var(--gray-dark);
        margin-top: 0.25rem;
    }

    .message.sent .message-time {
        text-align: right;
    }

    .chat-input-container {
        padding: 1.5rem;
        border-top: 2px solid var(--gray-light);
    }

    .chat-input-wrapper {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
    }

    .chat-input {
        flex: 1;
        padding: 0.875rem 1.25rem;
        border: 2px solid var(--gray-medium);
        border-radius: 16px;
        resize: none;
        max-height: 120px;
    }

    .empty-chat {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--gray-dark);
    }

    @media (max-width: 768px) {
        .main-container {
            margin-left: 0;
        }

        .conversations-sidebar {
            width: 100%;
        }

        .chat-area {
            display: none;
        }

        .chat-area.active {
            display: flex;
        }

        .conversations-sidebar.hide {
            display: none;
        }
    }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>

    <div class="messages-container">
        <!-- Conversations Sidebar -->
        <div class="conversations-sidebar" id="conversationsSidebar">
            <div class="conversations-header">
                <h2>Messages</h2>
                <div class="search-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" id="searchConversations" placeholder="Search users..." oninput="filterUsers(this.value)">
                </div>
            </div>

            <div class="conversations-list" id="conversationsList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading users...</p>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="empty-chat" id="emptyChat">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>Select a conversation</h3>
                <p>Choose from your existing conversations or start a new one</p>
            </div>

            <div id="activeChat" style="display: none; flex: 1; display: flex; flex-direction: column;">
                <div class="chat-header">
                    <div class="chat-user-info">
                        <div class="avatar" id="chatAvatar">
                            <span>U</span>
                        </div>
                        <div>
                            <h3 id="chatUserName">Select a chat</h3>
                            <p style="font-size: 0.875rem; color: var(--gray-dark);" id="chatUserStatus">Online</p>
                        </div>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <!-- Messages will be loaded here -->
                </div>

                <div class="chat-input-container">
                    <div class="chat-input-wrapper">
                        <textarea id="messageInput" class="chat-input" placeholder="Type a message..." rows="1"></textarea>
                        <button class="btn btn-primary" onclick="sendMessage()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentChatUserId = null;
    let messageCheckInterval = null;

    let allUsers = [];
    let allConversations = [];

    document.addEventListener('DOMContentLoaded', () => {
        loadAllUsers();
        loadConversations();

        // Check URL parameter for direct user chat
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('user');
        if (userId) {
            openChat(parseInt(userId));
        }
    });

    async function loadAllUsers() {
        try {
            // Load all approved users (or friends if available)
            const response = await fetch('../api/users.php?action=get_friends');
            const data = await response.json();

            if (data.success && data.friends && data.friends.length > 0) {
                allUsers = data.friends;
                renderUsersList(allUsers);
            } else {
                // If no friends, try to get all users (limited)
                const allUsersResponse = await fetch('../api/messages.php?action=get_all_users');
                const allUsersData = await allUsersResponse.json();

                if (allUsersData.success && allUsersData.users && allUsersData.users.length > 0) {
                    allUsers = allUsersData.users;
                    renderUsersList(allUsers);
                } else {
                    renderUsersList([]);
                }
            }
        } catch (error) {
            console.error('Error loading users:', error);
            renderUsersList([]);
        }
    }

    async function loadConversations() {
        try {
            const response = await fetch('../api/messages.php?action=get_conversations');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.success && data.conversations && data.conversations.length > 0) {
                allConversations = data.conversations;
                // Merge conversations with users list, marking which have conversations
                updateUsersWithConversations();
            } else {
                allConversations = [];
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }

    function updateUsersWithConversations() {
        // Mark users who have conversations
        allUsers.forEach(user => {
            const conv = allConversations.find(c => c.other_user_id === user.id);
            if (conv) {
                user.hasConversation = true;
                user.last_message = conv.last_message;
                user.last_message_time = conv.last_message_time;
                user.unread_count = conv.unread_count;
            } else {
                user.hasConversation = false;
            }
        });
        renderUsersList(allUsers);
    }

    function renderUsersList(users) {
        const container = document.getElementById('conversationsList');

        if (users.length === 0) {
            container.innerHTML = `
                <div class="text-center" style="padding: 3rem;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <p style="color: var(--gray-dark);">No users found</p>
                    <p style="font-size: 0.875rem; color: var(--gray-dark); margin-top: 0.5rem;">
                        Add friends to start messaging!
                    </p>
                </div>
            `;
            return;
        }

        // Sort: users with conversations first, then by name
        const sortedUsers = [...users].sort((a, b) => {
            if (a.hasConversation && !b.hasConversation) return -1;
            if (!a.hasConversation && b.hasConversation) return 1;
            return a.full_name.localeCompare(b.full_name);
        });

        container.innerHTML = sortedUsers.map(user => `
            <div class="conversation-item animate-slide-left ${currentChatUserId === user.id ? 'active' : ''}" onclick="openChat(${user.id})">
                <div class="conversation-avatar">
                    <div class="avatar" style="${user.profile_image && user.profile_image !== 'default-avatar.png' ? `background-image: url(../${user.profile_image});` : ''}">
                        ${!user.profile_image || user.profile_image === 'default-avatar.png' ? `<span>${user.full_name.charAt(0).toUpperCase()}</span>` : ''}
                    </div>
                    ${user.is_online || user.is_active ? '<div class="online-indicator"></div>' : ''}
                </div>
                <div class="conversation-info">
                    <div class="conversation-name">${escapeHtml(user.full_name)}</div>
                    <div class="conversation-preview">
                        ${user.hasConversation ? escapeHtml(user.last_message || 'No messages yet') : escapeHtml(user.email || user.role || 'Click to start chatting')}
                    </div>
                </div>
                <div class="conversation-meta">
                    ${user.hasConversation ? `<div class="conversation-time">${user.last_message_time || ''}</div>` : ''}
                    ${user.unread_count > 0 ? `<span class="unread-badge">${user.unread_count}</span>` : ''}
                </div>
            </div>
        `).join('');
    }

    function filterUsers(query) {
        if (!query.trim()) {
            renderUsersList(allUsers);
            return;
        }

        const filtered = allUsers.filter(user =>
            user.full_name.toLowerCase().includes(query.toLowerCase()) ||
            (user.email && user.email.toLowerCase().includes(query.toLowerCase())) ||
            (user.hasConversation && user.last_message && user.last_message.toLowerCase().includes(query.toLowerCase()))
        );
        renderUsersList(filtered);
    }


    async function openChat(userId) {
        currentChatUserId = userId;

        // Hide empty state
        document.getElementById('emptyChat').style.display = 'none';
        document.getElementById('activeChat').style.display = 'flex';

        // Mark conversation as active
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        event?.target?.closest('.conversation-item')?.classList.add('active');

        // Load user info
        loadChatUserInfo(userId);

        // Load messages
        loadMessages(userId);

        // Start polling for new messages
        if (messageCheckInterval) clearInterval(messageCheckInterval);
        messageCheckInterval = setInterval(() => loadMessages(userId), 3000);
    }

    async function loadChatUserInfo(userId) {
        try {
            const response = await fetch(`../api/users.php?action=get_profile&user_id=${userId}`);
            const data = await response.json();

            if (data.success) {
                const user = data.user;
                document.getElementById('chatUserName').textContent = user.full_name;
                const avatar = document.getElementById('chatAvatar');
                if (user.profile_image && user.profile_image !== 'default-avatar.png') {
                    avatar.style.backgroundImage = `url(../${user.profile_image})`;
                    avatar.innerHTML = '';
                } else {
                    avatar.style.backgroundImage = '';
                    avatar.innerHTML = `<span>${user.full_name.charAt(0).toUpperCase()}</span>`;
                }

                // // Update online status
                // const statusEl = document.getElementById('chatUserStatus');
                // if (statusEl) {
                //     statusEl.textContent = user.is_active ? 'Online' : 'Offline';
                // }
            }
        } catch (error) {
            console.error('Error loading user info:', error);
        }
    }

    async function loadMessages(userId) {
        try {
            const response = await fetch(`../api/messages.php?action=get_messages&user_id=${userId}`);
            const data = await response.json();

            const container = document.getElementById('chatMessages');
            const wasScrolledToBottom = container.scrollHeight - container.scrollTop === container.clientHeight;

            if (data.success && data.messages) {
                container.innerHTML = data.messages.map(msg => {
                    const senderInitial = msg.sender_name ? msg.sender_name.charAt(0).toUpperCase() : 'U';
                    const isSent = msg.is_sent === 1 || msg.is_sent === true;
                    const messageText = msg.content || msg.message || '';
                    return `
                        <div class="message ${isSent ? 'sent' : 'received'}">
                            <div class="avatar" style="flex-shrink: 0;">
                                <span>${senderInitial}</span>
                            </div>
                            <div style="flex: 1;">
                                <div class="message-content">${escapeHtml(messageText)}</div>
                                <div class="message-time">
                                    ${getTimeAgo(msg.created_at)}${!isSent ? ' • ' + escapeHtml(msg.sender_name) : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                if (wasScrolledToBottom || data.messages.length === 1) {
                    container.scrollTop = container.scrollHeight;
                }
            } else if (!data.success) {
                console.error('Error loading messages:', data.message);
                container.innerHTML = `
                    <div class="empty-chat">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <h3>Failed to Load Messages</h3>
                        <p style="color: var(--gray-dark);">${data.message || 'An error occurred'}</p>
                        <button class="btn btn-secondary mt-3" onclick="loadMessages(${userId})">Retry</button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            const container = document.getElementById('chatMessages');
            container.innerHTML = `
                <div class="empty-chat">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--error);">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3 style="color: var(--error);">Connection Error</h3>
                    <p style="color: var(--gray-dark);">Failed to load messages: ${error.message}</p>
                    <button class="btn btn-secondary mt-3" onclick="loadMessages(${userId})">Retry</button>
                </div>
            `;
        }
    }

    async function sendMessage() {
        const input = document.getElementById('messageInput');
        const content = input.value.trim();

        if (!content || !currentChatUserId) {
            if (!content) {
                showAlert('Please enter a message', 'error');
            }
            return;
        }

        // Disable input while sending
        input.disabled = true;
        const sendBtn = document.querySelector('.chat-input-container .btn-primary');
        if (sendBtn) sendBtn.disabled = true;

        try {
            const response = await fetch('../api/messages.php?action=send_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    recipient_id: currentChatUserId,
                    content: content
                })
            });

            const text = await response.text();
            let data;
            try {
                data = text ? JSON.parse(text) : {};
            } catch (parseError) {
                console.error('Invalid JSON response:', text);
                showAlert('Server error. Please try again.', 'error');
                input.disabled = false;
                if (sendBtn) sendBtn.disabled = false;
                return;
            }

            if (data.success) {
                input.value = '';
                loadMessages(currentChatUserId);
                loadConversations();
            } else {
                showAlert(data.message || 'Failed to send message', 'error');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            showAlert('Connection error. Please try again.', 'error');
        } finally {
            // Re-enable input
            input.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
            input.focus();
        }
    }

    async function openNewMessageModal() {
        const modal = document.createElement('div');
        modal.className = 'modal active';
        modal.innerHTML = `
            <div class="modal-backdrop" onclick="this.closest('.modal').remove()"></div>
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3 class="modal-title">New Message</h3>
                    <button class="modal-close" onclick="this.closest('.modal').remove()">×</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Search Users</label>
                        <input type="text" id="searchUsersInput" class="form-control" placeholder="Type to search..." oninput="searchUsersForMessage(this.value)">
                    </div>
                    <div id="searchUsersResults" style="max-height: 400px; overflow-y: auto; margin-top: 1rem;"></div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        document.getElementById('searchUsersInput').focus();
    }

    let searchTimeout;
    async function searchUsersForMessage(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('searchUsersResults');

        if (query.length < 2) {
            resultsDiv.innerHTML = '<p style="text-align: center; color: var(--gray-dark); padding: 2rem;">Type at least 2 characters to search</p>';
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`../api/messages.php?action=search_users&query=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.success && data.users && data.users.length > 0) {
                    resultsDiv.innerHTML = data.users.map(user => `
                        <div class="conversation-item" onclick="startNewChat(${user.id}, '${escapeHtml(user.full_name)}')" style="cursor: pointer;">
                            <div class="conversation-avatar">
                                <div class="avatar" style="${user.profile_image && user.profile_image !== 'default-avatar.png' ? `background-image: url(../${user.profile_image});` : ''}">
                                    ${!user.profile_image || user.profile_image === 'default-avatar.png' ? `<span>${user.full_name.charAt(0).toUpperCase()}</span>` : ''}
                                </div>
                                ${user.is_online ? '<div class="online-indicator"></div>' : ''}
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-name">${escapeHtml(user.full_name)}</div>
                                <div class="conversation-preview" style="font-size: 0.75rem;">${escapeHtml(user.email)}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    resultsDiv.innerHTML = '<p style="text-align: center; color: var(--gray-dark); padding: 2rem;">No users found</p>';
                }
            } catch (error) {
                console.error('Error searching users:', error);
                resultsDiv.innerHTML = '<p style="text-align: center; color: var(--error); padding: 2rem;">Error searching users</p>';
            }
        }, 300);
    }

    function startNewChat(userId, userName) {
        document.querySelector('.modal.active')?.remove();
        openChat(userId);
    }

    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} animate-slide-down`;
        alert.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; padding: 1rem; border-radius: 12px; box-shadow: var(--shadow-lg);';
        alert.style.background = type === 'success' ? 'var(--success)' : 'var(--error)';
        alert.style.color = 'white';
        alert.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    // Send message on Enter
    document.getElementById('messageInput')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function getTimeAgo(timestamp) {
        const now = new Date();
        const msgTime = new Date(timestamp);
        const diffInSeconds = Math.floor((now - msgTime) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        return msgTime.toLocaleDateString();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (messageCheckInterval) clearInterval(messageCheckInterval);
    });
</script>

</body>

</html>