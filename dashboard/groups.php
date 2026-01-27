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
    body {
        background: var(--gray-light);
    }

    .main-container {
        margin-left: 280px;
        min-height: 100vh;
        padding: 0;
    }

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
        transition: all 0.3s ease;
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
        min-width: 400px;
        max-width: 400px;
        background: white;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    .groups-sidebar.hidden {
        width: 0;
        min-width: 0;
        max-width: 0;
        opacity: 0;
        overflow: hidden;
        pointer-events: none;
        border-left: none;
    }

    .sidebar-toggle-btn {
        position: fixed;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: var(--primary-orange);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s ease;
        z-index: 100;
    }

    .sidebar-toggle-btn:hover {
        background: var(--primary-orange-dark);
        transform: translateY(-50%) scale(1.1);
    }

    .sidebar-toggle-btn.sidebar-visible {
        display: none;
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
        .main-container {
            margin-left: 0;
        }

        .groups-layout {
            flex-direction: column;
        }

        .chat-area {
            height: 50vh;
        }

        .groups-sidebar {
            width: 100%;
            height: 50vh;
        }

        .members-panel {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            z-index: 10;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
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
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-primary btn-sm" id="addMemberBtn" onclick="openAddMemberModal()" style="display: none;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                                Add
                            </button>
                            <button class="btn btn-ghost btn-sm" onclick="toggleMembersPanel()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
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
        <div class="groups-sidebar" id="groupsSidebar">
            <div class="groups-sidebar-header">
                <h2 style="margin: 0;">Groups</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Create
                    </button>
                    <button class="btn btn-ghost btn-sm" onclick="toggleGroupsSidebar()" title="Hide sidebar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div style="padding: 0.75rem 1rem; border-bottom: 2px solid var(--gray-light); display: flex; gap: 0.5rem;">
                <button class="filter-tab active" data-filter="joined" onclick="switchGroupFilter('joined')" style="padding: 0.5rem 1rem; border: none; background: var(--primary-orange); color: white; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                    My Groups
                </button>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Student'): ?>
                    <button class="filter-tab" data-filter="my_courses" onclick="switchGroupFilter('my_courses')" style="padding: 0.5rem 1rem; border: 2px solid var(--gray-medium); background: white; color: var(--dark-text); border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        Course Groups
                    </button>
                <?php endif; ?>
            </div>

            <div class="groups-list" id="groupsList">
                <div class="text-center" style="padding: 3rem;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--gray-dark);">Loading groups...</p>
                </div>
            </div>
        </div>

        <!-- Sidebar Toggle Button (shown when sidebar is hidden) -->
        <button class="sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleGroupsSidebar()" title="Show groups" style="display: none;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </button>
    </div>
</div>

<!-- Create Group Modal -->
<div id="createGroupModal" class="modal">
    <div class="modal-backdrop" onclick="closeCreateModal()"></div>
    <div class="modal-content" style="max-width: 650px;">
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

                <!-- Add Members Section -->
                <div class="form-group">
                    <label class="form-label">Add Members (Optional)</label>
                    <p style="font-size: 0.875rem; color: var(--gray-dark); margin-bottom: 0.5rem;">Search and add members to your group</p>
                    <div style="position: relative;">
                        <input type="text" id="memberSearch" class="form-control" placeholder="Search users by name or email..." onkeyup="searchMembersToAdd()">
                        <div id="memberSearchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--gray); border-top: none; border-radius: 0 0 8px 8px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
                    </div>
                </div>

                <!-- Selected Members Display -->
                <div id="selectedMembersContainer" style="display: none; margin-top: 1rem;">
                    <label class="form-label">Selected Members <span id="selectedMembersCount" style="color: var(--primary-orange);">(0)</span></label>
                    <div id="selectedMembersList" style="display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0.75rem; background: var(--gray-light); border-radius: 8px; max-height: 150px; overflow-y: auto;"></div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button class="btn btn-primary" onclick="createGroup()">Create</button>
        </div>
    </div>
</div>

<!-- Add Members to Existing Group Modal -->
<div id="addMemberModal" class="modal">
    <div class="modal-backdrop" onclick="closeAddMemberModal()"></div>
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Add Members to Group</h3>
            <button class="modal-close" onclick="closeAddMemberModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="color: var(--gray-dark); margin-bottom: 1rem;">Search and add new members to this group</p>

            <div class="form-group">
                <label class="form-label">Search Users</label>
                <div style="position: relative;">
                    <input type="text" id="addMemberSearch" class="form-control" placeholder="Search users by name or email..." onkeyup="searchUsersToAdd()">
                    <div id="addMemberSearchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--gray); border-top: none; border-radius: 0 0 8px 8px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
                </div>
            </div>

            <!-- Selected Members for Adding -->
            <div id="addMembersContainer" style="display: none; margin-top: 1rem;">
                <label class="form-label">Selected to Add <span id="addMembersCount" style="color: var(--primary-orange);">(0)</span></label>
                <div id="addMembersList" style="display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0.75rem; background: var(--gray-light); border-radius: 8px; max-height: 150px; overflow-y: auto;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAddMemberModal()">Cancel</button>
            <button class="btn btn-primary" onclick="addMembersToCurrentGroup()" id="addMembersSubmitBtn">Add Members</button>
        </div>
    </div>
</div>

<script>
    let currentGroupId = null;
    let messageCheckInterval = null;
    let currentFilter = 'joined'; // Default filter
    let currentGroupRole = null; // Track current user's role in the group

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
            const endpoint = currentFilter === 'joined' ?
                '../api/groups.php?action=get_user_groups' :
                `../api/groups.php?action=get_all&filter=${currentFilter}`;

            const response = await fetch(endpoint);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            const container = document.getElementById('groupsList');

            if (data.success && data.groups && data.groups.length > 0) {
                container.innerHTML = data.groups.map(group => {
                    const badgeHtml = group.is_auto_created ?
                        '<span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #3B82F6; color: white; border-radius: 6px; margin-left: 0.5rem;">📚 Course</span>' :
                        '';
                    const memberBadge = group.is_member ?
                        '<span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; background: #10B981; color: white; border-radius: 6px; margin-left: 0.5rem;">✓ Joined</span>' :
                        '';

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
                const message = currentFilter === 'my_courses' ?
                    'No course groups available yet. They will be created automatically based on your batch and trimester.' :
                    currentFilter === 'joined' ?
                    'You haven\'t joined any groups yet. Browse available groups to get started!' :
                    'No groups available. Create one to get started!';

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
                            <div class="member-name">
                                <a href="profile.php?id=${member.id}" style="color: var(--text-color); text-decoration: none;" 
                                   onmouseover="this.style.color='var(--primary-orange)'" 
                                   onmouseout="this.style.color='var(--text-color)'">${escapeHtml(member.full_name)}</a>
                            </div>
                            <div class="member-role">${escapeHtml(member.role)}</div>
                        </div>
                        ${member.member_role === 'admin' ? '<span class="member-badge">Admin</span>' : member.member_role === 'moderator' ? '<span class="member-badge" style="background: var(--success);">Mod</span>' : ''}
                    </div>
                `).join('');

                // Check if current user is admin/moderator to show add button
                const currentUserMember = data.members.find(m => m.id === <?php echo $_SESSION['user_id']; ?>);
                if (currentUserMember) {
                    currentGroupRole = currentUserMember.member_role;
                    const addBtn = document.getElementById('addMemberBtn');
                    if (currentGroupRole === 'admin' || currentGroupRole === 'moderator') {
                        addBtn.style.display = 'inline-flex';
                    } else {
                        addBtn.style.display = 'none';
                    }
                }
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
                headers: {
                    'Content-Type': 'application/json'
                },
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
        selectedMembers = []; // Reset selected members
        updateSelectedMembersDisplay();
    }

    function closeCreateModal() {
        document.getElementById('createGroupModal').classList.remove('active');
        document.getElementById('memberSearchResults').style.display = 'none';
        document.getElementById('memberSearch').value = '';
        selectedMembers = [];
    }

    // Selected members management
    let selectedMembers = [];
    let searchTimeout;

    async function searchMembersToAdd() {
        const query = document.getElementById('memberSearch').value.trim();
        const resultsDiv = document.getElementById('memberSearchResults');

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`../api/users.php?action=search_users&query=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.success && data.users && data.users.length > 0) {
                    resultsDiv.innerHTML = data.users.map(user => {
                        const isSelected = selectedMembers.some(m => m.id === user.id);
                        return `
                            <div class="search-result-item" style="padding: 0.75rem; border-bottom: 1px solid var(--gray-light); cursor: pointer; display: flex; align-items: center; gap: 0.75rem; ${isSelected ? 'background: var(--gray-light); opacity: 0.6;' : ''}" 
                                 onclick="${isSelected ? '' : `addMemberToGroup(${user.id}, '${user.full_name.replace(/'/g, "\\'")}', '${user.profile_image || 'default-avatar.png'}')`}"
                                 style="pointer-events: ${isSelected ? 'none' : 'auto'};">
                                <img src="../assets/uploads/profiles/${user.profile_image || 'default-avatar.png'}" 
                                     style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;"
                                     onerror="this.src='../assets/uploads/profiles/default-avatar.png'">
                                <div style="flex: 1;">
                                    <div style="font-weight: 500;">${user.full_name}</div>
                                    <div style="font-size: 0.875rem; color: var(--gray-dark);">${user.email}</div>
                                </div>
                                ${isSelected ? '<span style="color: var(--success); font-size: 0.875rem;">✓ Added</span>' : ''}
                            </div>
                        `;
                    }).join('');
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--gray-dark);">No users found</div>';
                    resultsDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Error searching users:', error);
            }
        }, 300);
    }

    function addMemberToGroup(userId, userName, profileImage) {
        // Check if already added
        if (selectedMembers.some(m => m.id === userId)) {
            return;
        }

        selectedMembers.push({
            id: userId,
            name: userName,
            image: profileImage
        });

        updateSelectedMembersDisplay();

        // Refresh search results to show as selected
        searchMembersToAdd();
    }

    function removeMemberFromSelection(userId) {
        selectedMembers = selectedMembers.filter(m => m.id !== userId);
        updateSelectedMembersDisplay();

        // Refresh search results
        if (document.getElementById('memberSearch').value.trim().length >= 2) {
            searchMembersToAdd();
        }
    }

    function updateSelectedMembersDisplay() {
        const container = document.getElementById('selectedMembersContainer');
        const list = document.getElementById('selectedMembersList');
        const count = document.getElementById('selectedMembersCount');

        if (selectedMembers.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        count.textContent = `(${selectedMembers.length})`;

        list.innerHTML = selectedMembers.map(member => `
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: white; border-radius: 20px; border: 1px solid var(--gray);">
                <img src="../assets/uploads/profiles/${member.image}" 
                     style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;"
                     onerror="this.src='../assets/uploads/profiles/default-avatar.png'">
                <span style="font-size: 0.875rem;">${member.name}</span>
                <button onclick="removeMemberFromSelection(${member.id})" 
                        style="background: none; border: none; color: var(--error); cursor: pointer; font-size: 1.125rem; padding: 0; margin-left: 0.25rem; line-height: 1;">
                    ×
                </button>
            </div>
        `).join('');
    }

    // Close search results when clicking outside
    document.addEventListener('click', function(event) {
        const searchInput = document.getElementById('memberSearch');
        const resultsDiv = document.getElementById('memberSearchResults');
        if (searchInput && resultsDiv && !searchInput.contains(event.target) && !resultsDiv.contains(event.target)) {
            resultsDiv.style.display = 'none';
        }
    });

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

            // Add selected member IDs
            if (selectedMembers.length > 0) {
                formData.append('member_ids', JSON.stringify(selectedMembers.map(m => m.id)));
            }

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
                selectedMembers = [];
                loadGroups(); // Reload groups list
            } else {
                showAlert(data.message || 'Failed to create group', 'error');
            }
        } catch (error) {
            console.error('Error creating group:', error);
            showAlert('Connection error. Please try again.', 'error');
        }
    }

    // Add Members to Existing Group Functions
    let addMembersSelection = [];
    let addMemberSearchTimeout;

    function openAddMemberModal() {
        if (!currentGroupId) {
            showAlert('Please select a group first', 'error');
            return;
        }
        if (currentGroupRole !== 'admin' && currentGroupRole !== 'moderator') {
            showAlert('Only admins and moderators can add members', 'error');
            return;
        }

        document.getElementById('addMemberModal').classList.add('active');
        addMembersSelection = [];
        updateAddMembersDisplay();
    }

    function closeAddMemberModal() {
        document.getElementById('addMemberModal').classList.remove('active');
        document.getElementById('addMemberSearchResults').style.display = 'none';
        document.getElementById('addMemberSearch').value = '';
        addMembersSelection = [];
    }

    async function searchUsersToAdd() {
        const query = document.getElementById('addMemberSearch').value.trim();
        const resultsDiv = document.getElementById('addMemberSearchResults');

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        clearTimeout(addMemberSearchTimeout);
        addMemberSearchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`../api/users.php?action=search_users&query=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.success && data.users && data.users.length > 0) {
                    // Get current group members to exclude them
                    const membersResponse = await fetch(`../api/groups.php?action=get_members&group_id=${currentGroupId}`);
                    const membersData = await membersResponse.json();
                    const existingMemberIds = membersData.success && membersData.members ?
                        membersData.members.map(m => m.id) : [];

                    resultsDiv.innerHTML = data.users.map(user => {
                        const isSelected = addMembersSelection.some(m => m.id === user.id);
                        const isExistingMember = existingMemberIds.includes(user.id);

                        if (isExistingMember) {
                            return `
                                <div class="search-result-item" style="padding: 0.75rem; border-bottom: 1px solid var(--gray-light); display: flex; align-items: center; gap: 0.75rem; background: var(--gray-light); opacity: 0.5;">
                                    <img src="../assets/uploads/profiles/${user.profile_image || 'default-avatar.png'}" 
                                         style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;"
                                         onerror="this.src='../assets/uploads/profiles/default-avatar.png'">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500;">${user.full_name}</div>
                                        <div style="font-size: 0.875rem; color: var(--gray-dark);">${user.email}</div>
                                    </div>
                                    <span style="color: var(--gray-dark); font-size: 0.875rem;">Already member</span>
                                </div>
                            `;
                        }

                        return `
                            <div class="search-result-item" style="padding: 0.75rem; border-bottom: 1px solid var(--gray-light); cursor: pointer; display: flex; align-items: center; gap: 0.75rem; ${isSelected ? 'background: var(--gray-light);' : ''}" 
                                 onclick="${isSelected ? '' : `addToMembersSelection(${user.id}, '${user.full_name.replace(/'/g, "\\'")}', '${user.profile_image || 'default-avatar.png'}')`}">
                                <img src="../assets/uploads/profiles/${user.profile_image || 'default-avatar.png'}" 
                                     style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;"
                                     onerror="this.src='../assets/uploads/profiles/default-avatar.png'">
                                <div style="flex: 1;">
                                    <div style="font-weight: 500;">${user.full_name}</div>
                                    <div style="font-size: 0.875rem; color: var(--gray-dark);">${user.email}</div>
                                </div>
                                ${isSelected ? '<span style="color: var(--success); font-size: 0.875rem;">✓ Selected</span>' : ''}
                            </div>
                        `;
                    }).join('');
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--gray-dark);">No users found</div>';
                    resultsDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Error searching users:', error);
            }
        }, 300);
    }

    function addToMembersSelection(userId, userName, profileImage) {
        if (addMembersSelection.some(m => m.id === userId)) {
            return;
        }

        addMembersSelection.push({
            id: userId,
            name: userName,
            image: profileImage
        });

        updateAddMembersDisplay();
        searchUsersToAdd(); // Refresh search results
    }

    function removeFromMembersSelection(userId) {
        addMembersSelection = addMembersSelection.filter(m => m.id !== userId);
        updateAddMembersDisplay();

        if (document.getElementById('addMemberSearch').value.trim().length >= 2) {
            searchUsersToAdd();
        }
    }

    function updateAddMembersDisplay() {
        const container = document.getElementById('addMembersContainer');
        const list = document.getElementById('addMembersList');
        const count = document.getElementById('addMembersCount');
        const submitBtn = document.getElementById('addMembersSubmitBtn');

        if (addMembersSelection.length === 0) {
            container.style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        container.style.display = 'block';
        count.textContent = `(${addMembersSelection.length})`;
        submitBtn.disabled = false;

        list.innerHTML = addMembersSelection.map(member => `
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: white; border-radius: 20px; border: 1px solid var(--gray);">
                <img src="../assets/uploads/profiles/${member.image}" 
                     style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;"
                     onerror="this.src='../assets/uploads/profiles/default-avatar.png'">
                <span style="font-size: 0.875rem;">${member.name}</span>
                <button onclick="removeFromMembersSelection(${member.id})" 
                        style="background: none; border: none; color: var(--error); cursor: pointer; font-size: 1.125rem; padding: 0; margin-left: 0.25rem; line-height: 1;">
                    ×
                </button>
            </div>
        `).join('');
    }

    async function addMembersToCurrentGroup() {
        if (!currentGroupId || addMembersSelection.length === 0) {
            showAlert('No members selected', 'error');
            return;
        }

        const submitBtn = document.getElementById('addMembersSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Adding...';

        try {
            const response = await fetch('../api/groups.php?action=add_members', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    group_id: currentGroupId,
                    member_ids: addMembersSelection.map(m => m.id)
                })
            });

            const data = await response.json();

            if (data.success && data.added_count > 0) {
                showAlert(data.message || `${data.added_count} member(s) added successfully`, 'success');
                closeAddMemberModal();
                loadGroupMembers(currentGroupId); // Refresh member list
                loadGroups(); // Refresh groups to update member count
            } else {
                showAlert(data.message || 'Failed to add members', 'error');
            }
        } catch (error) {
            console.error('Error adding members:', error);
            showAlert('Connection error. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add Members';
        }
    }

    // Close add member search results when clicking outside
    document.addEventListener('click', function(event) {
        const searchInput = document.getElementById('addMemberSearch');
        const resultsDiv = document.getElementById('addMemberSearchResults');
        if (searchInput && resultsDiv && !searchInput.contains(event.target) && !resultsDiv.contains(event.target)) {
            resultsDiv.style.display = 'none';
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

    // Sidebar Toggle Functionality
    function toggleGroupsSidebar() {
        const sidebar = document.getElementById('groupsSidebar');
        const toggleBtn = document.getElementById('sidebarToggleBtn');

        if (sidebar.classList.contains('hidden')) {
            // Show sidebar
            sidebar.classList.remove('hidden');
            toggleBtn.style.display = 'none';
            localStorage.setItem('groupsSidebarVisible', 'true');
        } else {
            // Hide sidebar
            sidebar.classList.add('hidden');
            toggleBtn.style.display = 'flex';
            localStorage.setItem('groupsSidebarVisible', 'false');
        }
    }

    // Restore sidebar state on page load
    document.addEventListener('DOMContentLoaded', () => {
        const sidebarVisible = localStorage.getItem('groupsSidebarVisible');
        if (sidebarVisible === 'false') {
            const sidebar = document.getElementById('groupsSidebar');
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            sidebar.classList.add('hidden');
            toggleBtn.style.display = 'flex';
        }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (messageCheckInterval) clearInterval(messageCheckInterval);
    });
</script>

</body>

</html>