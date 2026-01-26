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

$pageTitle = 'Events & Workshops - UIU Social Connect';
require_once '../includes/header.php';
?>

<style>
    body { background: var(--gray-light); }
    .main-container { margin-left: 280px; min-height: 100vh; padding: 2rem; }
    .events-header { background: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; }
    .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
    .event-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-md); transition: all 0.3s ease; }
    .event-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
    .event-image { width: 100%; height: 200px; object-fit: cover; background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-light)); }
    .event-content { padding: 1.5rem; }
    .event-date { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--gray-dark); margin-bottom: 0.75rem; }
    .event-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
    .event-meta { display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; color: var(--gray-dark); margin-bottom: 1rem; }
    .event-actions { display: flex; gap: 0.75rem; }
    @media (max-width: 768px) { .main-container { margin-left: 0; } }
</style>

<?php include '../includes/sidebar.php'; ?>

<div class="main-container">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="events-header animate-fade-in">
        <div>
            <h1>📅 Events & Workshops</h1>
            <p style="color: var(--gray-dark);">Discover and join upcoming events</p>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create Event
        </button>
    </div>

    <div class="events-grid" id="eventsGrid">
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
            <div class="spinner"></div>
            <p style="margin-top: 1rem; color: var(--gray-dark);">Loading events...</p>
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div id="createEventModal" class="modal">
    <div class="modal-backdrop" onclick="closeCreateModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Create Event</h3>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="createEventForm">
                <div class="form-group">
                    <label class="form-label">Event Title</label>
                    <input type="text" id="eventTitle" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="eventDescription" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Date & Time</label>
                    <input type="datetime-local" id="eventDate" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" id="eventLocation" class="form-control" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitEvent()">Create Event</button>
        </div>
    </div>
</div>

<script>
    loadEvents();

    async function loadEvents() {
        try {
            const response = await fetch('../api/events.php?action=get_all');
            const data = await response.json();
            
            console.log('Events API Response:', data); // Debug log
            
            const grid = document.getElementById('eventsGrid');
            
            if (data.success && data.events && data.events.length > 0) {
                grid.innerHTML = data.events.map(event => {
                    // Format date and time
                    const eventDate = new Date(event.event_date + ' ' + (event.event_time || '00:00:00'));
                    const formattedDate = eventDate.toLocaleDateString('en-US', { 
                        weekday: 'short', 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    const formattedTime = event.event_time ? 
                        new Date('2000-01-01 ' + event.event_time).toLocaleTimeString('en-US', { 
                            hour: 'numeric', 
                            minute: '2-digit',
                            hour12: true 
                        }) : '';
                    
                    return `
                    <div class="event-card animate-scale-in">
                        <div class="event-image"></div>
                        <div class="event-content">
                            <div class="event-date">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                </svg>
                                ${formattedDate}${formattedTime ? ' at ' + formattedTime : ''}
                            </div>
                            <h3 class="event-title">${escapeHtml(event.title)}</h3>
                            <p style="color: var(--gray-dark); margin-bottom: 1rem;">${escapeHtml(event.description)}</p>
                            <div class="event-meta">
                                <span>📍 ${escapeHtml(event.location)}</span>
                                <span>👥 ${event.attendees_count || 0} going</span>
                            </div>
                            <div class="event-actions">
                                <button class="btn btn-primary" onclick="rsvpEvent(${event.id}, 'going')">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    I'm Going
                                </button>
                                <button class="btn btn-outline" onclick="rsvpEvent(${event.id}, 'interested')">Interested</button>
                            </div>
                        </div>
                    </div>
                `;
                }).join('');
            } else {
                console.log('No events found. Data:', data); // Debug log
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; color: var(--gray-dark);">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                        </svg>
                        <h3>No Events Yet</h3>
                        <p style="color: var(--gray-dark);">Be the first to create an event!</p>
                        ${!data.success ? `<p style="color: var(--error); margin-top: 1rem;">Error: ${data.message || 'Failed to load events'}</p>` : ''}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading events:', error);
            const grid = document.getElementById('eventsGrid');
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <h3>Error Loading Events</h3>
                    <p style="color: var(--error);">${error.message}</p>
                    <button class="btn btn-primary" onclick="loadEvents()" style="margin-top: 1rem;">Retry</button>
                </div>
            `;
        }
    }

    function openCreateModal() {
        document.getElementById('createEventModal').classList.add('active');
    }

    function closeCreateModal() {
        document.getElementById('createEventModal').classList.remove('active');
    }

    async function submitEvent() {
        const title = document.getElementById('eventTitle').value.trim();
        const description = document.getElementById('eventDescription').value.trim();
        const eventDate = document.getElementById('eventDate').value;
        const location = document.getElementById('eventLocation').value.trim();

        if (!title || !description || !eventDate || !location) {
            alert('Please fill all fields');
            return;
        }

        try {
            const response = await fetch('../api/events.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, description, event_date: eventDate, location })
            });

            const data = await response.json();
            if (data.success) {
                closeCreateModal();
                alert('Event created! Waiting for admin approval.');
                document.getElementById('createEventForm').reset();
                loadEvents();
            } else {
                alert(data.message || 'Failed to create event');
            }
        } catch (error) {
            alert('Connection error');
        }
    }

    async function rsvpEvent(eventId, status) {
        try {
            const response = await fetch('../api/events.php?action=rsvp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event_id: eventId, status })
            });

            const data = await response.json();
            
            if (data.success) {
                // Show success message
                showAlert(data.message || 'RSVP updated!', 'success');
                // Reload events to update attendee counts
                loadEvents();
            } else {
                showAlert(data.message || 'Failed to update RSVP', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Connection error. Please try again.', 'error');
        }
    }

    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} animate-slide-down`;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

</body>
</html>
