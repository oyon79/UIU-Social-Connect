<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Course Groups Utility - Admin Panel';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        body { background: var(--gray-light); }
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .admin-header { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-md); }
        .card { background: white; border-radius: 16px; padding: 2rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-md); }
        .btn-group { display: flex; gap: 1rem; margin-top: 1rem; }
        #result { margin-top: 1.5rem; padding: 1rem; border-radius: 12px; display: none; }
        #result.success { background: #ECFDF5; border: 2px solid #10B981; color: #065F46; }
        #result.error { background: #FEF2F2; border: 2px solid #EF4444; color: #991B1B; }
        .detail-item { padding: 0.75rem; background: var(--gray-light); border-radius: 8px; margin-bottom: 0.5rem; }
        .loader { border: 3px solid var(--gray-light); border-top: 3px solid var(--primary-orange); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; display: inline-block; margin-right: 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🎓 Course Groups Utility</h1>
            <p style="color: var(--gray-dark);">Create course groups for existing students</p>
            <a href="index.php" class="btn btn-secondary" style="margin-top: 1rem;">← Back to Dashboard</a>
        </div>

        <div class="card">
            <h2>📚 Bulk Create Course Groups</h2>
            <p style="color: var(--gray-dark);">This will create course groups for all approved students based on their department, batch, and trimester.</p>
            
            <div class="btn-group">
                <button class="btn btn-primary" onclick="createGroupsForAll()" id="createAllBtn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Create Groups for All Students
                </button>
            </div>

            <div id="result"></div>
        </div>

        <div class="card">
            <h2>👤 Create Groups for Single Student</h2>
            <p style="color: var(--gray-dark);">Enter a student's user ID to create/join their course groups.</p>
            
            <div class="form-group">
                <label class="form-label">Student User ID</label>
                <input type="number" id="userId" class="form-control" placeholder="Enter user ID" style="max-width: 300px;">
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" onclick="createGroupsForUser()" id="createUserBtn">
                    Create Groups for This Student
                </button>
            </div>

            <div id="userResult"></div>
        </div>

        <div class="card">
            <h2>ℹ️ Information</h2>
            <ul style="line-height: 1.8;">
                <li>Groups are automatically created when students register</li>
                <li>Use this utility for students who registered before the feature was implemented</li>
                <li>Each student gets groups for all courses in their trimesters (Trimester 1 to their current trimester)</li>
                <li>Groups are named: <code>{DEPT} - {COURSE_CODE}: {TITLE}</code></li>
                <li>Students are automatically joined to all their course groups</li>
                <li>Groups are auto-approved and ready to use immediately</li>
            </ul>
        </div>
    </div>

    <script>
        async function createGroupsForAll() {
            const btn = document.getElementById('createAllBtn');
            const result = document.getElementById('result');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Creating groups...';
            result.style.display = 'block';
            result.className = '';
            result.innerHTML = '<div class="loader"></div> Processing all students...';

            try {
                const response = await fetch('../api/create_course_groups.php?action=create_all');
                const data = await response.json();

                if (data.success) {
                    result.className = 'success';
                    let html = `<strong>✓ Success!</strong><br>${data.message}<br><br>`;
                    html += `<strong>Summary:</strong><br>`;
                    html += `Students Processed: ${data.students_processed}<br>`;
                    html += `Groups Created/Joined: ${data.groups_created}<br>`;
                    
                    if (data.details && data.details.length > 0) {
                        html += `<br><strong>Details:</strong><br>`;
                        html += '<div style="max-height: 300px; overflow-y: auto; margin-top: 0.5rem;">';
                        data.details.forEach(detail => {
                            if (detail.error) {
                                html += `<div class="detail-item" style="background: #FEF2F2;">❌ ${detail.user}: ${detail.error}</div>`;
                            } else {
                                html += `<div class="detail-item">✓ ${detail.user}: Created ${detail.created}, Joined ${detail.joined}</div>`;
                            }
                        });
                        html += '</div>';
                    }
                    
                    result.innerHTML = html;
                } else {
                    result.className = 'error';
                    result.innerHTML = `<strong>✗ Error:</strong><br>${data.message}`;
                }
            } catch (error) {
                result.className = 'error';
                result.innerHTML = `<strong>✗ Error:</strong><br>Connection error: ${error.message}`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg> Create Groups for All Students`;
            }
        }

        async function createGroupsForUser() {
            const userId = document.getElementById('userId').value;
            const btn = document.getElementById('createUserBtn');
            const result = document.getElementById('userResult');

            if (!userId || userId < 1) {
                result.style.display = 'block';
                result.className = 'error';
                result.innerHTML = '<strong>✗ Error:</strong><br>Please enter a valid user ID';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Creating groups...';
            result.style.display = 'block';
            result.className = '';
            result.innerHTML = '<div class="loader"></div> Processing...';

            try {
                const response = await fetch(`../api/create_course_groups.php?action=create_for_user&user_id=${userId}`);
                const data = await response.json();

                if (data.success) {
                    result.className = 'success';
                    result.innerHTML = `
                        <strong>✓ Success!</strong><br>
                        ${data.message}<br><br>
                        <strong>Student:</strong> ${data.user_name}<br>
                        <strong>Groups Created:</strong> ${data.groups_created}<br>
                        <strong>Groups Joined:</strong> ${data.groups_joined}
                    `;
                } else {
                    result.className = 'error';
                    result.innerHTML = `<strong>✗ Error:</strong><br>${data.message}`;
                }
            } catch (error) {
                result.className = 'error';
                result.innerHTML = `<strong>✗ Error:</strong><br>Connection error: ${error.message}`;
            } finally {
                btn.disabled = false;
                btn.textContent = 'Create Groups for This Student';
            }
        }
    </script>
</body>
</html>
