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

$pageTitle = 'Notes & Documents - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body {
        background: var(--gray-light);
    }

    .main-container {
        margin-left: 280px;
        min-height: 100vh;
        padding: 2rem;
    }

    .documents-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-md);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .search-filter-bar {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .documents-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table thead {
        background: var(--gray-light);
    }

    .table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--dark-text);
        border-bottom: 2px solid var(--gray-medium);
    }

    .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-light);
    }

    .table tr:hover {
        background: var(--gray-light);
    }

    .doc-type-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        background: var(--primary-orange-light);
        color: var(--primary-orange);
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-approved {
        background: #10B981;
        color: white;
    }

    .status-pending {
        background: #F59E0B;
        color: white;
    }

    .status-rejected {
        background: #EF4444;
        color: white;
    }

    .action-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .action-btn-download {
        background: var(--primary-orange);
        color: white;
    }

    .action-btn-download:hover {
        background: var(--primary-orange-light);
    }

    .action-btn-delete {
        background: var(--error);
        color: white;
    }

    .action-btn-delete:hover {
        background: #DC2626;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1;
    }

    .modal-content {
        position: relative;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        z-index: 2;
        animation: scaleIn 0.3s ease;
    }

    .modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid var(--gray-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        margin: 0;
        font-size: 1.5rem;
        color: var(--dark-text);
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 2rem;
        color: var(--gray-dark);
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .modal-close:hover {
        background: var(--gray-light);
        color: var(--dark-text);
    }

    .modal-body {
        padding: 2rem;
    }

    .modal-footer {
        padding: 1.5rem 2rem;
        border-top: 1px solid var(--gray-light);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .main-container {
            margin-left: 0;
        }

        .table {
            font-size: 0.875rem;
        }

        .table th,
        .table td {
            padding: 0.75rem 0.5rem;
        }

        .modal-content {
            width: 95%;
            max-height: 95vh;
        }

        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 1.25rem;
        }
    }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>

    <div class="documents-header animate-fade-in">
        <div>
            <h1>📚 Notes & Documents</h1>
            <p style="color: var(--gray-dark);">Share and download academic resources</p>
        </div>
        <button class="btn btn-primary" onclick="openUploadModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            Upload Document
        </button>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-bar">
        <input type="text" id="searchInput" class="form-control" placeholder="Search documents..." style="flex: 1; min-width: 250px;">
        <select id="noteTypeFilter" class="form-control" style="min-width: 200px;">
            <option value="">All Types</option>
            <option value="Lecture Notes">Lecture Notes</option>
            <option value="Class Slides (PPT)">Class Slides (PPT)</option>
            <option value="Lab Manual">Lab Manual</option>
            <option value="Assignment Solution">Assignment Solution</option>
            <option value="Project Documentation">Project Documentation</option>
            <option value="Research Paper">Research Paper</option>
            <option value="Cheat Sheet">Cheat Sheet</option>
            <option value="Book / PDF">Book / PDF</option>
            <option value="Question Bank">Question Bank</option>
            <option value="Resume / CV Template">Resume / CV Template</option>
            <option value="Design Resource">Design Resource</option>
            <option value="Code Snippet / Zip">Code Snippet / Zip</option>
            <option value="Other">Other</option>
        </select>
        <select id="sortByFilter" class="form-control" style="min-width: 150px;">
            <option value="recent">Most Recent</option>
            <option value="downloads">Most Downloaded</option>
            <option value="name">Name (A-Z)</option>
        </select>
        <button class="btn btn-secondary" onclick="loadDocuments()">Apply Filters</button>
    </div>

    <!-- Documents Table -->
    <div class="documents-table">
        <table class="table" id="documentsTable">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Document Name</th>
                    <th>Description</th>
                    <th>Uploaded By</th>
                    <th>Size</th>
                    <th>Downloads</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="documentsBody">
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem;">
                        <div class="spinner"></div>
                        <p style="margin-top: 1rem; color: var(--gray-dark);">Loading documents...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Document Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-backdrop" onclick="closeUploadModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Upload Document</h3>
            <button class="modal-close" onclick="closeUploadModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Note Type *</label>
                    <select id="noteType" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="Lecture Notes">Lecture Notes</option>
                        <option value="Class Slides (PPT)">Class Slides (PPT)</option>
                        <option value="Lab Manual">Lab Manual</option>
                        <option value="Assignment Solution">Assignment Solution</option>
                        <option value="Project Documentation">Project Documentation</option>
                        <option value="Research Paper">Research Paper</option>
                        <option value="Cheat Sheet">Cheat Sheet</option>
                        <option value="Book / PDF">Book / PDF</option>
                        <option value="Question Bank">Question Bank</option>
                        <option value="Resume / CV Template">Resume / CV Template</option>
                        <option value="Design Resource">Design Resource</option>
                        <option value="Code Snippet / Zip">Code Snippet / Zip</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Document Name *</label>
                    <input type="text" id="noteName" class="form-control" placeholder="e.g., CSE 3811 Lecture Notes" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="noteDescription" class="form-control" rows="3" placeholder="Brief description of the document..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Upload File * (Max: 100MB)</label>
                    <input type="file" id="documentFile" class="form-control" required>
                    <small style="color: var(--gray-dark); font-size: 0.875rem;">Supported formats: PDF, DOCX, PPTX, ZIP, PNG, JPG, etc.</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeUploadModal()">Cancel</button>
            <button class="btn btn-primary" id="uploadBtn" onclick="submitUpload()">Upload</button>
        </div>
    </div>
</div>

<script>
    let currentUserId = <?php echo $_SESSION['user_id']; ?>;

    // Load documents on page load
    loadDocuments();

    async function loadDocuments() {
        const search = document.getElementById('searchInput').value;
        const noteType = document.getElementById('noteTypeFilter').value;
        const sortBy = document.getElementById('sortByFilter').value;

        try {
            const params = new URLSearchParams({
                action: 'get_all',
                search: search,
                note_type: noteType,
                sort_by: sortBy
            });

            const response = await fetch(`../api/documents.php?${params}`);
            const data = await response.json();

            const tbody = document.getElementById('documentsBody');

            if (data.success && data.documents.length > 0) {
                tbody.innerHTML = data.documents.map(doc => {
                    const isOwn = doc.user_id == <?php echo $_SESSION['user_id']; ?>;
                    const statusBadge = !doc.is_approved && isOwn ? '<span style="display: inline-block; margin-left: 0.5rem; padding: 0.25rem 0.5rem; background: #FEF3C7; color: #D97706; border-radius: 4px; font-size: 0.7rem; font-weight: 600;">Pending Approval</span>' : '';
                    const ownBadge = isOwn ? '<span style="display: inline-block; margin-left: 0.5rem; padding: 0.25rem 0.5rem; background: rgba(255, 122, 0, 0.1); color: var(--primary-orange); border-radius: 4px; font-size: 0.7rem; font-weight: 600;">Your Upload</span>' : '';

                    return `
                    <tr style="${!doc.is_approved && isOwn ? 'background: rgba(255, 235, 59, 0.05);' : ''}">
                        <td><span class="doc-type-badge">${doc.note_type}</span></td>
                        <td>
                            <strong>${escapeHtml(doc.note_name)}</strong>
                            ${statusBadge}${ownBadge}
                        </td>
                        <td>${escapeHtml(doc.description || 'No description')}</td>
                        <td><a href="profile.php?id=${doc.user_id}" style="color: var(--text-color); text-decoration: none; font-weight: 500;" onmouseover="this.style.color='var(--primary-orange)';this.style.textDecoration='underline'" onmouseout="this.style.color='var(--text-color)';this.style.textDecoration='none'">${escapeHtml(doc.uploader_name)}</a></td>
                        <td>${doc.file_size_formatted}</td>
                        <td>${doc.download_count}</td>
                        <td>
                            <button class="action-btn action-btn-download" onclick="downloadDocument(${doc.id})" title="Download ${escapeHtml(doc.note_name)}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Download
                            </button>
                        </td>
                    </tr>
                `
                }).join('');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--gray-dark);">
                            No documents found
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error('Error loading documents:', error);
            document.getElementById('documentsBody').innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--error);">
                        Failed to load documents
                    </td>
                </tr>
            `;
        }
    }

    function openUploadModal() {
        document.getElementById('uploadModal').style.display = 'flex';
        document.getElementById('uploadForm').reset();
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
    }

    async function submitUpload() {
        const noteType = document.getElementById('noteType').value;
        const noteName = document.getElementById('noteName').value;
        const noteDescription = document.getElementById('noteDescription').value;
        const fileInput = document.getElementById('documentFile');

        if (!noteType || !noteName || !fileInput.files[0]) {
            alert('Please fill in all required fields');
            return;
        }

        const formData = new FormData();
        formData.append('note_type', noteType);
        formData.append('note_name', noteName);
        formData.append('description', noteDescription);
        formData.append('file', fileInput.files[0]);

        document.getElementById('uploadBtn').disabled = true;
        document.getElementById('uploadBtn').textContent = 'Uploading...';

        try {
            const response = await fetch('../api/documents.php?action=upload', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                closeUploadModal();
                loadDocuments();
            } else {
                alert(data.message || 'Failed to upload document');
            }
        } catch (error) {
            console.error('Error uploading document:', error);
            alert('Failed to upload document');
        } finally {
            document.getElementById('uploadBtn').disabled = false;
            document.getElementById('uploadBtn').textContent = 'Upload';
        }
    }

    function downloadDocument(docId) {
        window.location.href = `../api/documents.php?action=download&doc_id=${docId}`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Real-time search
    document.getElementById('searchInput').addEventListener('input', debounce(loadDocuments, 500));

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
</script>
</body>

</html>