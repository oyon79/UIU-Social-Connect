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

$pageTitle = 'Groups & Clubs - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body { background: var(--gray-light); }
    .main-container { margin-left: 280px; min-height: 100vh; padding: 0; }
    
    .groups-layout {
        display: flex;
        height: calc(100vh - 80px);
    }
    
    /* Chat Area (Left Side) */
    .chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: white;
        border-right: 2px solid var(--gray-light);
    }
    
    .chat-header {
        padding: 1.5rem;
        border-bottom: 2px solid var(--gray-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .members-panel {
        width: 280px;
        background: white;
        border-left: 2px solid var(--gray-light);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    
    .members-panel-header {
        padding: 1.5rem;
        border-bottom: 2px solid var(--gray-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .members-list {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }
    
    .member-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        border-radius: 12px;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .member-item:hover {
        background: var(--gray-light);
    }
    
    .member-info {
        flex: 1;
    }
    
    .member-name {
        font-weight: 600;
        font-size: 0.9375rem;
    }
    
    .member-role {
        font-size: 0.75rem;
        color: var(--gray-dark);
        text-transform: capitalize;
    }
    
    .member-badge {
        background: var(--primary-orange);
        color: white;
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-weight: 600;
    }
    
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        background: #f8f9fa;
    }
    
    .message {
        display: flex;
        gap: 1rem;
        max-width: 75%;
        align-items: flex-start;
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
    
    /* Groups List (Right Side) */
    .groups-sidebar {
        width: 400px;
        background: white;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    
    .groups-sidebar-header {
        padding: 1.5rem;
        border-bottom: 2px solid var(--gray-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .groups-list {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }
    
    .group-item {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .group-item:hover {
        background: var(--gray-light);
    }
    
    .group-item.active {
        background: linear-gradient(135deg, rgba(255, 122, 0, 0.1), rgba(255, 179, 102, 0.1));
        border-color: var(--primary-orange);
    }
    
    .group-item-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .group-item-name {
        font-weight: 600;
        font-size: 1rem;
    }
    
    .group-item-meta {
        font-size: 0.875rem;
        color: var(--gray-dark);
    }
    
    @media (max-width: 768px) {
        .main-container { margin-left: 0; }
        .groups-layout { flex-direction: column; }
        .chat-area { height: 50vh; }
        .groups-sidebar { width: 100%; height: 50vh; }
        .members-panel { 
            position: absolute; 
            right: 0; 
            top: 0; 
            height: 100%; 
            z-index: 10;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        }
    }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="groups-layout">
        <!-- Chat Area (Left) -->
        <div class="chat-area" id="chatArea">
            <div class="empty-chat" id="emptyChat">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>Select a Group</h3>
                <p>Choose a group from the list to start chatting</p>
            </div>

            <div id="activeChat" style="display: none; flex: 1; display: flex; flex-direction: row;">
                <div style="flex: 1; display: flex; flex-direction: column;">
                    <div class="chat-header">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div class="avatar" id="chatGroupAvatar">
                                <span>G</span>
                            </div>
                            <div>
                                <h3 id="chatGroupName" style="margin: 0;">Group Name</h3>
                                <p style="font-size: 0.875rem; color: var(--gray-dark); margin: 0;" id="chatGroupMembers">0 members</p>
                            </div>
                        </div>
                        <button class="btn btn-ghost" onclick="toggleMembersPanel()" id="membersToggleBtn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Members
                        </button>
                    </div>

                <div class="chat-messages" id="chatMessages">
                    <!-- Messages will be loaded here -->
                </div>

                    <div class="chat-input-container">
                        <div class="chat-input-wrapper">
                            <textarea id="messageInput" class="chat-input" placeholder="Type a message..." rows="1"></textarea>
                            <button class="btn btn-primary" onclick="sendGroupMessage()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                                Send
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Members Panel -->
                <div class="members-panel" id="membersPanel">
                    <div class="members-panel-header">
                        <h3 style="margin: 0;">Members</h3>
                        <button class="btn btn-ghost btn-sm" onclick="toggleMembersPanel()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="members-list" id="membersList">
                        <div class="text-center" style="padding: 2rem;">
                            <div class="spinner"></div>
                            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading members...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Groups List (Right) -->
        <div class="groups-sidebar">
            <div class="groups-sidebar-header">
                <h2 style="margin: 0;">Groups</h2>
                <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Create
                </button>
            </div>
            
            <!-- Filter Tabs -->
            <div style="padding: 0.75rem 1rem; border-bottom: 2px solid var(--gray-light); display: flex; gap: 0.5rem;">
                <button class="filter-tab active" data-filter="joined" onclick="switchGroupFilter('joined')" style="padding: 0.5rem 1rem; border: none; background: var(--primary-orange); color: white; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                    My Groups
                </button>
                <button class="filter-tab" data-filter="my_courses" onclick="switchGroupFilter('my_courses')" style="padding: 0.5rem 1rem; border: 2px solid var(--gray-medium); background: white; color: var(--dark-text); border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                    Course Groups
                </button>
                <button class="filter-tab" data-filter="all" onclick="switchGroupFilter('all')" style="padding: 0.5rem 1rem; border: 2px solid var(--gray-medium); background: white; color: var(--dark-text); border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                    All Groups
                </button>
            </div>
            
            <div class="groups-list" id="groupsList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading groups...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Group Modal -->
<div id="createGroupModal" class="modal">
    <div class="modal-backdrop" onclick="closeCreateModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Create Group</h3>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="createGroupForm">
                <div class="form-group">
                    <label class="form-label">Group Name</label>
                    <input type="text" id="groupName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="groupDescription" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Category (Optional)</label>
                    <input type="text" id="groupCategory" class="form-control" placeholder="e.g., Sports, Academic, Social">
                </div>
                <div class="form-group">
                    <label class="form-label">Group Image (Optional)</label>
                    <input type="file" id="groupImage" class="form-control" accept="image/*">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button class="btn btn-primary" onclick="createGroup()">Create</button>
        </div>
    </div>
</div>

<script>
    let currentGroupId = null;
    let messageCheckInterval = null;
    let currentFilter = 'joined'; // Default filter

    document.addEventListener('DOMContentLoaded', () => {
        loadGroups();
        
        // Check URL parameter for direct group chat
        const urlParams = new URLSearchParams(window.location.search);
        const groupId = urlParams.get('group');
        if (groupId) {
            openGroupChat(parseInt(groupId));
        }
    });
    
    function switchGroupFilter(filter) {
        currentFilter = filter;
        
        // Update tab styles
        document.querySelectorAll('.filter-tab').forEach(tab => {
            if (tab.dataset.filter === filter) {
                tab.style.background = 'var(--primary-orange)';
                tab.style.color = 'white';
                tab.style.border = 'none';
                tab.classList.add('active');
            } else {
                tab.style.background = 'white';
                tab.style.color = 'var(--dark-text)';
                tab.style.border = '2px solid var(--gray-medium)';
                tab.classList.remove('active');
            }
        });
        
        // Reload groups with new filter
        loadGroups();
    }

    async function loadGroups() {
        try {
            const endpoint = currentFilter === 'joined' 
                ? '../api/groups.php?action=get_user_groups' 
                : `../api/groups.php?action=get_all&filter=${currentFilter}`;
            
            const response = await fetch(endpoint);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            
            const container = document.getElementById('groupsList');
            
            if (data.success && data.groups && data.groups.length > 0) {
                container.innerHTML = data.groups.map(group => {
                    const badgeHtml = group.is_auto_created 
                        ? '<span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #3B82F6; color: white; border-radius: 6px; margin-left: 0.5rem;">📚 Course</span>'
                        : '';
                    const memberBadge = group.is_member 
                        ? '<span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #10B981; color: white; border-radius: 6px; margin-left: 0.5rem;">✓ Joined</span>'
                        : '';
                    
                    return `
                        <div class="group-item ${currentGroupId === group.id ? 'active' : ''}" onclick="openGroupChat(${group.id})">
                            <div class="group-item-header">
                                <div class="avatar" style="${group.image_url ? `background-image: url(../${group.image_url});` : ''}">
                                    ${!group.image_url ? `<span>${group.name.charAt(0).toUpperCase()}</span>` : ''}
                                </div>
                                <div style="flex: 1;">
                                    <div class="group-item-name">
                                        ${escapeHtml(group.name)}
                                        ${badgeHtml}
                                        ${!group.is_member && currentFilter !== 'joined' ? memberBadge : ''}
                                    </div>
                                    <div class="group-item-meta">👥 ${group.members_count || 0} members</div>
                                </div>
                            </div>
                            ${group.description ? `<p style="font-size: 0.875rem; color: var(--gray-dark); margin: 0;">${escapeHtml(group.description.substring(0, 50))}${group.description.length > 50 ? '...' : ''}</p>` : ''}
                        </div>
                    `;
                }).join('');
            } else {
                const message = currentFilter === 'my_courses' 
                    ? 'No course groups available yet. They will be created automatically based on your batch and trimester.'
                    : currentFilter === 'joined'
                    ? 'You haven\'t joined any groups yet. Browse available groups to get started!'
                    : 'No groups available. Create one to get started!';
                
                container.innerHTML = `
                    <div class="text-center" style="padding: 3rem;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <h3>No Groups Found</h3>
                        <p style="color: var(--gray-dark);">${message}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading groups:', error);
            document.getElementById('groupsList').innerHTML = `
                <div class="text-center" style="padding: 3rem;">
                    <h3 style="color: var(--error);">Failed to load groups</h3>
                    <p style="color: var(--gray-dark);">An error occurred: ${error.message}</p>
                    <button class="btn btn-secondary mt-3" onclick="loadGroups()">Retry</button>
                </div>
            `;
        }
    }
    
    // Keep loadUserGroups for backward compatibility
    async function loadUserGroups() {
        currentFilter = 'joined';
        await loadGroups();
    }

    async function openGroupChat(groupId) {
        currentGroupId = groupId;
        
        // Hide empty state
        document.getElementById('emptyChat').style.display = 'none';
        document.getElementById('activeChat').style.display = 'flex';
        
        // Mark group as active
        document.querySelectorAll('.group-item').forEach(item => {
            item.classList.remove('active');
        });
        event?.target?.closest('.group-item')?.classList.add('active');
        
        // Load group info and check membership
        await loadGroupInfo(groupId);
        await checkMembership(groupId);
        
        // Load members
        loadGroupMembers(groupId);
        
        // Load messages (only if member, otherwise show join message)
        loadGroupMessages(groupId);
        
        // Start polling for new messages
        if (messageCheckInterval) clearInterval(messageCheckInterval);
        messageCheckInterval = setInterval(() => loadGroupMessages(groupId), 3000);
    }
    
    async function checkMembership(groupId) {
        try {
            const response = await fetch(`../api/groups.php?action=check_membership&group_id=${groupId}`);
            const data = await response.json();
            
            const messageInput = document.getElementById('messageInput');
            const sendBtn = document.querySelector('.chat-input-container .btn-primary');
            
            if (data.success && data.is_member) {
                // User is a member - enable messaging
                messageInput.disabled = false;
                messageInput.placeholder = 'Type a message...';
                if (sendBtn) sendBtn.disabled = false;
            } else {
                // User is not a member - disable messaging
                messageInput.disabled = true;
                messageInput.placeholder = 'You must join this group to send messages';
                if (sendBtn) sendBtn.disabled = true;
            }
        } catch (error) {
            console.error('Error checking membership:', error);
        }
    }
    
    function toggleMembersPanel() {
        const panel = document.getElementById('membersPanel');
        panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
    }
    
    async function loadGroupMembers(groupId) {
        try {
            const response = await fetch(`../api/groups.php?action=get_members&group_id=${groupId}`);
            const data = await response.json();
            
            const container = document.getElementById('membersList');
            
            if (data.success && data.members && data.members.length > 0) {
                container.innerHTML = data.members.map(member => `
                    <div class="member-item">
                        <div class="avatar" style="${member.profile_image && member.profile_image !== 'default-avatar.png' ? `background-image: url(../${member.profile_image});` : ''}">
                            ${!member.profile_image || member.profile_image === 'default-avatar.png' ? `<span>${member.full_name.charAt(0).toUpperCase()}</span>` : ''}
                        </div>
                        <div class="member-info">
                            <div class="member-name">${escapeHtml(member.full_name)}</div>
                            <div class="member-role">${escapeHtml(member.role)}</div>
                        </div>
                        ${member.member_role === 'admin' ? '<span class="member-badge">Admin</span>' : member.member_role === 'moderator' ? '<span class="member-badge" style="background: var(--success);">Mod</span>' : ''}
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="text-center" style="padding: 2rem;">
                        <p style="color: var(--gray-dark);">No members found</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading group members:', error);
            document.getElementById('membersList').innerHTML = `
                <div class="text-center" style="padding: 2rem;">
                    <p style="color: var(--error);">Failed to load members</p>
                </div>
            `;
        }
    }

    async function loadGroupInfo(groupId) {
        try {
            const response = await fetch(`../api/groups.php?action=get_by_id&group_id=${groupId}`);
            const data = await response.json();
            
            if (data.success) {
                const group = data.group;
                document.getElementById('chatGroupName').textContent = group.name;
                document.getElementById('chatGroupMembers').textContent = `${group.members_count || 0} members`;
                const avatar = document.getElementById('chatGroupAvatar');
                if (group.image_url) {
                    avatar.style.backgroundImage = `url(../${group.image_url})`;
                    avatar.innerHTML = '';
                } else {
                    avatar.style.backgroundImage = '';
                    avatar.innerHTML = `<span>${group.name.charAt(0).toUpperCase()}</span>`;
                }
            }
        } catch (error) {
            console.error('Error loading group info:', error);
        }
    }

    async function loadGroupMessages(groupId) {
        try {
            const response = await fetch(`../api/groups.php?action=get_messages&group_id=${groupId}`);
            const data = await response.json();
            
            const container = document.getElementById('chatMessages');
            const wasScrolledToBottom = container.scrollHeight - container.scrollTop === container.clientHeight;
            
            if (data.success && data.messages) {
                container.innerHTML = data.messages.map(msg => {
                    const senderInitial = msg.sender_name ? msg.sender_name.charAt(0).toUpperCase() : 'U';
                    const isSent = msg.is_sent === 1 || msg.is_sent === true;
                    return `
                        <div class="message ${isSent ? 'sent' : 'received'}">
                            <div class="avatar" style="flex-shrink: 0;">
                                <span>${senderInitial}</span>
                            </div>
                            <div>
                                <div class="message-content">${escapeHtml(msg.message || '')}</div>
                                <div class="message-time">${getTimeAgo(msg.created_at)}${!isSent ? ' • ' + escapeHtml(msg.sender_name) : ''}</div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                if (wasScrolledToBottom || data.messages.length === 1) {
                    container.scrollTop = container.scrollHeight;
                }
            } else if (!data.success) {
                // User is not a member
                container.innerHTML = `
                    <div class="empty-chat">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <h3>Join to View Messages</h3>
                        <p style="color: var(--gray-dark);">You must be a member of this group to view and send messages.</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading group messages:', error);
        }
    }

    async function sendGroupMessage() {
        const input = document.getElementById('messageInput');
        const content = input.value.trim();
        
        if (!content || !currentGroupId) {
            if (!content) {
                showAlert('Please enter a message', 'error');
            }
            return;
        }
        
        // Check if input is disabled (user not a member)
        if (input.disabled) {
            showAlert('You must join this group to send messages', 'error');
            return;
        }
        
        try {
            const response = await fetch('../api/groups.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    group_id: currentGroupId,
                    message: content
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                input.value = '';
                loadGroupMessages(currentGroupId);
            } else {
                showAlert(data.message || 'Failed to send message', 'error');
                // If error is about membership, disable input
                if (data.message && data.message.includes('member')) {
                    input.disabled = true;
                    input.placeholder = 'You must join this group to send messages';
                }
            }
        } catch (error) {
            console.error('Error sending message:', error);
            showAlert('Connection error. Please try again.', 'error');
        }
    }

    // Send message on Enter
    document.getElementById('messageInput')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendGroupMessage();
        }
    });

    function openCreateModal() {
        document.getElementById('createGroupModal').classList.add('active');
    }

    function closeCreateModal() {
        document.getElementById('createGroupModal').classList.remove('active');
    }

    async function createGroup() {
        const name = document.getElementById('groupName').value.trim();
        const description = document.getElementById('groupDescription').value.trim();
        const category = document.getElementById('groupCategory')?.value.trim() || '';

        if (!name || !description) {
            showAlert('Please fill all required fields', 'error');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('name', name);
            formData.append('description', description);
            if (category) formData.append('category', category);
            
            const imageInput = document.getElementById('groupImage');
            if (imageInput && imageInput.files[0]) {
                formData.append('image', imageInput.files[0]);
            }

            const response = await fetch('../api/groups.php?action=create', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                closeCreateModal();
                showAlert('Group created! Waiting for admin approval.', 'success');
                document.getElementById('createGroupForm').reset();
                loadGroups(); // Reload groups list
            } else {
                showAlert(data.message || 'Failed to create group', 'error');
            }
        } catch (error) {
            console.error('Error creating group:', error);
            showAlert('Connection error. Please try again.', 'error');
        }
    }

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

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (messageCheckInterval) clearInterval(messageCheckInterval);
    });
</script>

</body>
</html>
