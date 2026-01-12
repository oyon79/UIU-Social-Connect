<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Messages - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body { background: var(--gray-light); }
    .main-container { margin-left: 280px; min-height: 100vh; }
    
    .messages-container { display: flex; height: calc(100vh - 80px); margin-top: 80px; }
    
    .conversations-sidebar { width: 380px; background: white; border-right: 2px solid var(--gray-light); display: flex; flex-direction: column; }
    
    .conversations-header { padding: 1.5rem; border-bottom: 2px solid var(--gray-light); }
    .conversations-header h2 { margin-bottom: 1rem; }
    
    .search-box { position: relative; }
    .search-box input { width: 100%; padding: 0.75rem 1rem 0.75rem 3rem; border: 2px solid var(--gray-medium); border-radius: 12px; }
    .search-box svg { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray-dark); }
    
    .conversations-list { flex: 1; overflow-y: auto; }
    
    .conversation-item { padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-light); cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 1rem; }
    .conversation-item:hover { background: var(--gray-light); }
    .conversation-item.active { background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 102, 0.1)); border-left: 4px solid var(--primary-orange); }
    
    .conversation-avatar { position: relative; }
    .online-indicator { position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px; background: var(--success); border: 2px solid white; border-radius: 50%; }
    
    .conversation-info { flex: 1; min-width: 0; }
    .conversation-name { font-weight: 600; margin-bottom: 0.25rem; }
    .conversation-preview { font-size: 0.875rem; color: var(--gray-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .conversation-meta { text-align: right; }
    .conversation-time { font-size: 0.75rem; color: var(--gray-dark); }
    .unread-badge { background: var(--primary-orange); color: white; border-radius: 12px; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 700; margin-top: 0.25rem; display: inline-block; }
    
    .chat-area { flex: 1; display: flex; flex-direction: column; background: white; }
    
    .chat-header { padding: 1.5rem; border-bottom: 2px solid var(--gray-light); display: flex; align-items: center; justify-content: space-between; }
    .chat-user-info { display: flex; align-items: center; gap: 1rem; }
    
    .chat-messages { flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
    
    .message { display: flex; gap: 1rem; max-width: 70%; }
    .message.sent { margin-left: auto; flex-direction: row-reverse; }
    
    .message-content { background: var(--gray-light); padding: 0.875rem 1.25rem; border-radius: 16px; }
    .message.sent .message-content { background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light)); color: white; }
    
    .message-time { font-size: 0.75rem; color: var(--gray-dark); margin-top: 0.25rem; }
    
    .chat-input-container { padding: 1.5rem; border-top: 2px solid var(--gray-light); }
    .chat-input-wrapper { display: flex; gap: 1rem; align-items: flex-end; }
    .chat-input { flex: 1; padding: 0.875rem 1.25rem; border: 2px solid var(--gray-medium); border-radius: 16px; resize: none; max-height: 120px; }
    
    .empty-chat { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--gray-dark); }
    
    @media (max-width: 768px) {
        .main-container { margin-left: 0; }
        .conversations-sidebar { width: 100%; }
        .chat-area { display: none; }
        .chat-area.active { display: flex; }
        .conversations-sidebar.hide { display: none; }
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
                    <input type="text" id="searchConversations" placeholder="Search messages...">
                </div>
            </div>
            
            <div class="conversations-list" id="conversationsList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading conversations...</p>
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

    document.addEventListener('DOMContentLoaded', () => {
        loadConversations();
        
        // Check URL parameter for direct user chat
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('user');
        if (userId) {
            openChat(parseInt(userId));
        }
    });

    async function loadConversations() {
        try {
            const response = await fetch('../api/messages.php?action=get_conversations');
            const data = await response.json();
            
            const container = document.getElementById('conversationsList');
            
            if (data.success && data.conversations && data.conversations.length > 0) {
                container.innerHTML = data.conversations.map(conv => `
                    <div class="conversation-item animate-slide-left" onclick="openChat(${conv.other_user_id})">
                        <div class="conversation-avatar">
                            <div class="avatar">
                                <span>${conv.other_user_name.charAt(0).toUpperCase()}</span>
                            </div>
                            ${conv.is_online ? '<div class="online-indicator"></div>' : ''}
                        </div>
                        <div class="conversation-info">
                            <div class="conversation-name">${escapeHtml(conv.other_user_name)}</div>
                            <div class="conversation-preview">${escapeHtml(conv.last_message || 'No messages yet')}</div>
                        </div>
                        <div class="conversation-meta">
                            <div class="conversation-time">${conv.last_message_time || ''}</div>
                            ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="text-center" style="padding: 3rem;">
                        <p style="color: var(--gray-dark);">No conversations yet</p>
                        <p style="font-size: 0.875rem; color: var(--gray-dark); margin-top: 0.5rem;">
                            Start chatting with friends!
                        </p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
        }
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
                document.getElementById('chatAvatar').innerHTML = `<span>${user.full_name.charAt(0).toUpperCase()}</span>`;
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async function loadMessages(userId) {
        try {
            const response = await fetch(`../api/messages.php?action=get_messages&user_id=${userId}`);
            const data = await response.json();
            
            const container = document.getElementById('chatMessages');
            const wasScrolledToBottom = container.scrollHeight - container.scrollTop === container.clientHeight;
            
            if (data.success && data.messages) {
                container.innerHTML = data.messages.map(msg => `
                    <div class="message ${msg.is_sent ? 'sent' : 'received'}">
                        <div class="avatar" style="flex-shrink: 0;">
                            <span>${msg.sender_name.charAt(0).toUpperCase()}</span>
                        </div>
                        <div>
                            <div class="message-content">${escapeHtml(msg.content)}</div>
                            <div class="message-time">${getTimeAgo(msg.created_at)}</div>
                        </div>
                    </div>
                `).join('');
                
                if (wasScrolledToBottom || data.messages.length === 1) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async function sendMessage() {
        const input = document.getElementById('messageInput');
        const content = input.value.trim();
        
        if (!content || !currentChatUserId) return;
        
        try {
            const response = await fetch('../api/messages.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    recipient_id: currentChatUserId,
                    content: content
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                input.value = '';
                loadMessages(currentChatUserId);
                loadConversations();
            }
        } catch (error) {
            console.error('Error:', error);
        }
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
