<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_approved']) {
    header('Location: ../index.php');
    exit;
}

$pageTitle = 'Jobs & Internships - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body { background: var(--gray-light); }
    .main-container { margin-left: 280px; min-height: 100vh; padding: 2rem; }
    .jobs-header { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; }
    .jobs-list { display: flex; flex-direction: column; gap: 1.5rem; }
    .job-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: var(--shadow-md); transition: all 0.3s ease; }
    .job-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
    .job-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
    .job-badge { padding: 0.375rem 0.875rem; border-radius: 20px; font-size: 0.8125rem; font-weight: 600; }
    .job-badge.full-time { background: #ECFDF5; color: var(--success); }
    .job-badge.part-time { background: #FFF7ED; color: var(--warning); }
    .job-badge.internship { background: #EFF6FF; color: #3B82F6; }
    @media (max-width: 768px) { .main-container { margin-left: 0; } }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="jobs-header animate-fade-in">
        <div>
            <h1>💼 Jobs & Internships</h1>
            <p style="color: var(--gray-dark);">Find your next opportunity</p>
        </div>
        <button class="btn btn-primary" onclick="openPostModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Post Job
        </button>
    </div>

    <div class="jobs-list" id="jobsList">
        <div class="text-center" style="padding: 3rem;">
            <div class="spinner"></div>
            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading jobs...</p>
        </div>
    </div>
</div>

<!-- Post Job Modal -->
<div id="postJobModal" class="modal">
    <div class="modal-backdrop" onclick="closePostModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Post Job</h3>
            <button class="modal-close" onclick="closePostModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="postJobForm">
                <div class="form-group">
                    <label class="form-label">Job Title</label>
                    <input type="text" id="jobTitle" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <input type="text" id="jobCompany" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="jobDescription" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select id="jobType" class="form-control">
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Internship">Internship</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" id="jobLocation" class="form-control" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePostModal()">Cancel</button>
            <button class="btn btn-primary" onclick="postJob()">Post Job</button>
        </div>
    </div>
</div>

<script>
    loadJobs();

    async function loadJobs() {
        try {
            const response = await fetch('../api/jobs.php?action=get_all');
            const data = await response.json();
            
            const list = document.getElementById('jobsList');
            
            if (data.success && data.jobs && data.jobs.length > 0) {
                list.innerHTML = data.jobs.map(job => `
                    <div class="job-card animate-slide-up">
                        <div class="job-header">
                            <div>
                                <h3 style="margin-bottom: 0.5rem;">${escapeHtml(job.title)}</h3>
                                <p style="color: var(--gray-dark); margin-bottom: 0.5rem;">${escapeHtml(job.company)}</p>
                                <span class="job-badge ${job.job_type.toLowerCase().replace('-', '')}">${job.job_type}</span>
                            </div>
                            <button class="btn btn-primary" onclick="applyJob(${job.id})">Apply Now</button>
                        </div>
                        <p style="color: var(--gray-dark); margin-bottom: 1rem;">${escapeHtml(job.description)}</p>
                        <div style="display: flex; gap: 1.5rem; font-size: 0.875rem; color: var(--gray-dark);">
                            <span>📍 ${escapeHtml(job.location)}</span>
                            <span>👥 ${job.applications_count || 0} applicants</span>
                            <span>🕒 Posted ${getTimeAgo(job.created_at)}</span>
                        </div>
                    </div>
                `).join('');
            } else {
                list.innerHTML = `
                    <div class="text-center" style="padding: 3rem;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <h3>No Jobs Available</h3>
                        <p style="color: var(--gray-dark);">Check back later for new opportunities</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function openPostModal() {
        document.getElementById('postJobModal').classList.add('active');
    }

    function closePostModal() {
        document.getElementById('postJobModal').classList.remove('active');
    }

    async function postJob() {
        const title = document.getElementById('jobTitle').value.trim();
        const company = document.getElementById('jobCompany').value.trim();
        const description = document.getElementById('jobDescription').value.trim();
        const jobType = document.getElementById('jobType').value;
        const location = document.getElementById('jobLocation').value.trim();

        if (!title || !company || !description || !location) {
            alert('Please fill all fields');
            return;
        }

        try {
            const response = await fetch('../api/jobs.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, company, description, job_type: jobType, location })
            });

            const data = await response.json();
            if (data.success) {
                closePostModal();
                alert('Job posted! Waiting for admin approval.');
                document.getElementById('postJobForm').reset();
                loadJobs();
            } else {
                alert(data.message || 'Failed to post job');
            }
        } catch (error) {
            alert('Connection error');
        }
    }

    async function applyJob(jobId) {
        if (!confirm('Apply for this job?')) return;

        try {
            const response = await fetch('../api/jobs.php?action=apply', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ job_id: jobId })
            });

            const data = await response.json();
            if (data.success) {
                alert('Application submitted!');
                loadJobs();
            }
        } catch (error) {
            alert('Connection error');
        }
    }

    function getTimeAgo(timestamp) {
        const now = new Date();
        const postTime = new Date(timestamp);
        const diffInSeconds = Math.floor((now - postTime) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
        return postTime.toLocaleDateString();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

</body>
</html>
