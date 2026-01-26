<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Documents Management - Admin Panel';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    <style>
        body { background: var(--gray-light); }
        .admin-container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        .admin-header { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-md); }
        .admin-nav { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .admin-nav-link { padding: 0.875rem 1.75rem; background: white; border-radius: 12px; text-decoration: none; color: var(--dark-text); font-weight: 600; transition: all 0.3s ease; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 0.5rem; }
        .admin-nav-link:hover { background: var(--primary-orange); color: white; transform: translateY(-2px); }
        .admin-nav-link.active { background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light)); color: white; }
        
        .documents-table { background: white; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-md); }
        .table { width: 100%; border-collapse: collapse; }
        .table thead { background: var(--gray-light); }
        .table th { padding: 1rem; text-align: left; font-weight: 600; color: var(--dark-text); border-bottom: 2px solid var(--gray-medium); }
        .table td { padding: 1rem; border-bottom: 1px solid var(--gray-light); }
        .table tr:hover { background: var(--gray-light); }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending { background: #F59E0B; color: white; }
        .status-approved { background: #10B981; color: white; }
        .status-rejected { background: #EF4444; color: white; }
        
        .action-btns { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header animate-fade-in">
            <h1>📚 Documents Management</h1>
            <p style="color: var(--gray-dark);">Review and approve submitted documents</p>
        </div>

        <div class="admin-nav">
            <a href="index.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>
            <a href="approvals.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                </svg>
                Approvals
            </a>
            <a href="users.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                Users
            </a>
            <a href="content.php" class="admin-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                </svg>
                Content
            </a>
            <a href="documents.php" class="admin-nav-link active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Documents
            </a>
        </div>

        <div class="documents-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Document Name</th>
                        <th>Uploaded By</th>
                        <th>File Size</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="documentsBody">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem;">
                            <div class="spinner"></div>
                            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading documents...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        loadDocuments();

        async function loadDocuments() {
            try {
                const response = await fetch('../api/admin.php?action=get_documents');
                const data = await response.json();
                
                const tbody = document.getElementById('documentsBody');
                
                if (data.success && data.documents && data.documents.length > 0) {
                    tbody.innerHTML = data.documents.map(doc => {
                        let statusClass = 'status-pending';
                        let statusText = 'Pending';
                        
                        if (doc.is_approved == 1) {
                            statusClass = 'status-approved';
                            statusText = 'Approved';
                        } else if (doc.rejection_reason) {
                            statusClass = 'status-rejected';
                            statusText = 'Rejected';
                        }
                        
                        return `
                            <tr>
                                <td>${doc.id}</td>
                                <td>${escapeHtml(doc.note_type)}</td>
                                <td>
                                    <strong>${escapeHtml(doc.note_name)}</strong>
                                    ${doc.description ? '<br><small style="color: var(--gray-dark);">' + escapeHtml(doc.description) + '</small>' : ''}
                                </td>
                                <td>${escapeHtml(doc.uploader_name)}</td>
                                <td>${doc.file_size_formatted}</td>
                                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                                <td>${formatDate(doc.created_at)}</td>
                                <td>
                                    <div class="action-btns">
                                        ${doc.is_approved == 0 && !doc.rejection_reason ? `
                                            <button class="btn btn-success btn-sm" onclick="approveDocument(${doc.id})">✓ Approve</button>
                                            <button class="btn btn-danger btn-sm" onclick="rejectDocument(${doc.id})">✗ Reject</button>
                                        ` : ''}
                                        <button class="btn btn-danger btn-sm" onclick="deleteDocument(${doc.id})">🗑️ Delete</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--gray-dark);">
                                No documents found
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Error loading documents:', error);
                document.getElementById('documentsBody').innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--error);">
                            Failed to load documents
                        </td>
                    </tr>
                `;
            }
        }

        async function approveDocument(docId) {
            if (!confirm('Approve this document?')) return;

            try {
                const response = await fetch('../api/admin.php?action=approve_document', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ doc_id: docId })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadDocuments();
                } else {
                    alert(data.message || 'Failed to approve document');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to approve document');
            }
        }

        async function rejectDocument(docId) {
            const reason = prompt('Enter rejection reason:');
            if (!reason) return;

            try {
                const response = await fetch('../api/admin.php?action=reject_document', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ doc_id: docId, reason: reason })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadDocuments();
                } else {
                    alert(data.message || 'Failed to reject document');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to reject document');
            }
        }

        async function deleteDocument(docId) {
            if (!confirm('Delete this document permanently?')) return;

            try {
                const response = await fetch('../api/admin.php?action=delete_document', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ doc_id: docId })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadDocuments();
                } else {
                    alert(data.message || 'Failed to delete document');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete document');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
    </script>
</body>
</html>
