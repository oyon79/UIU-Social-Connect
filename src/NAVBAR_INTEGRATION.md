# Dynamic Navbar Integration - Implementation Summary

## Overview

Integrated dynamic notifications and message counts in the navbar, replacing static placeholders with real-time data from the database.

## Files Created

### 1. **api/notifications.php** (New)

Complete notification API with the following endpoints:

#### Endpoints:

- `GET ?action=get_notifications` - Fetch user notifications (limit & offset supported)
- `GET ?action=get_unread_count` - Get unread notification count
- `POST ?action=mark_read` - Mark single notification as read
- `POST ?action=mark_all_read` - Mark all notifications as read
- `POST ?action=delete_notification` - Delete a notification

#### Features:

- Fetches notifications with user info and relative time
- Returns avatar initials for display
- Supports pagination (limit/offset)
- Includes reference_id and reference_type for navigation

---

### 2. **database/sample_notifications.sql** (New)

SQL script to insert sample notifications for testing

- Includes 5 sample notifications of different types
- Uses realistic timestamps (2 hours ago, 5 hours ago, etc.)
- Ready to test immediately after execution

---

## Files Modified

### 1. **api/messages.php**

**Changes:**

- Added `get_unread_count` action to switch statement
- Created `getUnreadMessageCount()` function
  - Queries unread messages for current user
  - Returns JSON with count

**Code Added:**

```php
case 'get_unread_count':
    getUnreadMessageCount($db);
    break;

function getUnreadMessageCount($db) {
    $sql = "SELECT COUNT(*) as count FROM messages
            WHERE receiver_id = ? AND is_read = 0";
    // Returns: {'success': true, 'count': 5}
}
```

---

### 2. **includes/navbar.php**

**Major Changes:**

#### A. Badge Elements (Made Dynamic)

- Replaced static notification badge with ID `notificationCount`
- Replaced static message badge with ID `messageCount`
- Both badges now hidden by default (`display: none`)
- Show only when count > 0

#### B. Notifications Dropdown

- Replaced static HTML with dynamic loading container
- Added loading spinner
- Added "Mark all read" button with ID `markAllReadBtn`
- Added CSS styles for notification items:
  - `.notification-item` - Base notification style
  - `.notification-item.unread` - Unread notification highlight
  - Hover effects and orange accent bar for unread items

#### C. JavaScript Functions Added

1. **`loadNotificationCount()`**
   - Fetches unread notification count
   - Updates badge visibility and text
   - Called on page load and every 30 seconds

2. **`loadMessageCount()`**
   - Fetches unread message count
   - Updates badge visibility and text
   - Called on page load and every 30 seconds

3. **`loadNotifications()`**
   - Fetches notifications with limit 20
   - Renders notification items with avatars
   - Shows empty state if no notifications
   - Handles errors gracefully

4. **`markNotificationRead(notificationId, referenceType, referenceId)`**
   - Marks notification as read via POST
   - Refreshes counts and list
   - Navigates to reference if exists

5. **`navigateToReference(type, id)`**
   - Routes user to correct page based on notification type
   - Supports: post, event, job, notice, message, profile

6. **`getNotificationColor(type)`**
   - Returns color CSS based on notification type
   - 8 different colors for different notification types

#### D. Auto-refresh Implementation

```javascript
// Refresh counts every 30 seconds
setInterval(() => {
  loadNotificationCount();
  loadMessageCount();
}, 30000);
```

---

### 3. **includes/functions.php**

**Changes:**

- Added `createNotification()` function
- Added `createBulkNotifications()` function

#### `createNotification()` Function

```php
function createNotification($userId, $type, $title, $message, $referenceId = null, $referenceType = null)
```

**Parameters:**

- `$userId` - User to notify
- `$type` - Notification type (like, comment, friend_request, message, event, job, notice, approval)
- `$title` - Notification title
- `$message` - Notification message (supports HTML)
- `$referenceId` - Optional reference ID (post_id, event_id, etc.)
- `$referenceType` - Optional reference type (post, event, job, etc.)

**Usage Example:**

```php
createNotification(
    $userId,
    'like',
    'New Like',
    "<strong>John Doe</strong> liked your post",
    $postId,
    'post'
);
```

#### `createBulkNotifications()` Function

Same parameters but accepts array of user IDs for mass notifications

---

### 4. **api/posts.php**

**Changes:**

- Updated `toggleLike()` function to create notifications
- Updated `addComment()` function to create notifications

#### Like Notification Logic

```php
// After creating like
if ($postData[0]['user_id'] != $userId) {
    createNotification(
        $postData[0]['user_id'],
        'like',
        'New Like',
        "<strong>{$userName}</strong> liked your post",
        $postId,
        'post'
    );
}
```

#### Comment Notification Logic

```php
// After creating comment
if ($postData[0]['user_id'] != $userId) {
    createNotification(
        $postData[0]['user_id'],
        'comment',
        'New Comment',
        "<strong>{$userName}</strong> commented on your post",
        $postId,
        'post'
    );
}
```

**Note:** Both check if post owner is different from the action user (don't notify yourself)

---

## Database Schema

### Existing `notifications` Table

```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('like', 'comment', 'friend_request', 'message', 'event', 'job', 'notice', 'approval'),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT,
    reference_type VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Existing `messages` Table

```sql
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## How It Works

### 1. Page Load

```
User opens dashboard page
    ↓
navbar.php loads
    ↓
DOMContentLoaded event fires
    ↓
loadNotificationCount() → API call → Update badge
loadMessageCount() → API call → Update badge
    ↓
Auto-refresh starts (every 30 seconds)
```

### 2. Opening Notifications

```
User clicks notification bell icon
    ↓
Dropdown opens
    ↓
loadNotifications() called
    ↓
Shows loading spinner
    ↓
API fetches notifications
    ↓
Renders notification items
    ↓
User clicks notification
    ↓
markNotificationRead() called
    ↓
Navigates to reference page
```

### 3. Creating Notifications

```
User likes a post
    ↓
toggleLike() in posts.php
    ↓
Checks if post owner != current user
    ↓
createNotification() called
    ↓
Notification inserted to database
    ↓
Post owner's navbar badge updates (on next refresh)
```

---

## Notification Types & Colors

| Type           | Color            | Use Case                          |
| -------------- | ---------------- | --------------------------------- |
| like           | Red (#EF4444)    | Someone liked your content        |
| comment        | Blue (#3B82F6)   | Someone commented on your content |
| friend_request | Green (#10B981)  | New friend request                |
| message        | Purple (#8B5CF6) | New direct message                |
| event          | Orange (#F59E0B) | New event posted                  |
| job            | Cyan (#06B6D4)   | New job posting                   |
| notice         | Pink (#EC4899)   | New notice/announcement           |
| approval       | Green (#10B981)  | Content approved by admin         |

---

## Testing Steps

### 1. Test Notification Count

1. Login to the system
2. Check navbar - should see badges if unread notifications/messages exist
3. Open browser console
4. Check for API calls: `notifications.php?action=get_unread_count`
5. Should return: `{"success":true,"count":3}`

### 2. Test Notification List

1. Click the notification bell icon
2. Dropdown should open with loading spinner
3. Notifications should load with:
   - Avatar with initial
   - Message text (with HTML formatting)
   - Relative time ("2 hours ago")
   - Orange dot for unread items
4. Empty state should show if no notifications

### 3. Test Mark as Read

1. Click any unread notification (with orange dot)
2. Should mark as read and redirect
3. Badge count should decrease by 1
4. Notification item should lose orange dot on next open

### 4. Test Mark All Read

1. Click "Mark all read" button in dropdown
2. All notifications should be marked read
3. Badge should disappear
4. All orange dots should disappear

### 5. Test Auto-Refresh

1. Open dashboard in two browser windows (different users)
2. In window 1, like a post by user in window 2
3. In window 2, wait up to 30 seconds
4. Badge should update automatically

### 6. Test Message Count

1. Send a message to a user
2. Recipient's navbar should show message badge
3. Count should reflect unread message count
4. Clicking should navigate to messages page

---

## Integration Points

### Where Notifications Are Created

1. **api/posts.php**
   - Like action → Notification to post owner
   - Comment action → Notification to post owner

2. **api/approvals.php** (Already integrated)
   - User approval → Notification to user
   - Post approval → Notification to post owner
   - Content rejection → Notification to content owner

3. **Future Integration Points** (To be added):
   - `api/events.php` - Event creation/approval
   - `api/jobs.php` - Job posting approval
   - `api/notices.php` - New notice posted
   - `api/messages.php` - New message received
   - Friend system - Friend request sent/accepted

---

## API Response Formats

### Get Unread Count

```json
{
  "success": true,
  "count": 5
}
```

### Get Notifications

```json
{
  "success": true,
  "notifications": [
    {
      "id": 1,
      "user_id": 2,
      "type": "like",
      "title": "New Like",
      "message": "<strong>John Doe</strong> liked your post",
      "reference_id": 5,
      "reference_type": "post",
      "is_read": false,
      "created_at": "2026-01-26 10:30:00",
      "time_ago": "2 hours ago",
      "full_name": "John Doe",
      "profile_image": "profile.jpg",
      "avatar_initial": "J"
    }
  ]
}
```

### Mark as Read

```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

## Performance Considerations

1. **Auto-refresh Interval**: 30 seconds
   - Balance between real-time updates and server load
   - Can be adjusted in navbar.php JavaScript

2. **Notification Limit**: 20 per fetch
   - Prevents loading too much data at once
   - Older notifications can be loaded with pagination

3. **Index Optimization**:
   - `idx_user_read` on (user_id, is_read) for fast unread count
   - `idx_created` on created_at for sorting

4. **Caching Considerations**:
   - Badge counts cached for 30 seconds (auto-refresh interval)
   - Notification list fetched fresh each time dropdown opens

---

## Security Notes

1. **Authorization**: All API endpoints check `$_SESSION['user_id']`
2. **SQL Injection**: All queries use prepared statements
3. **XSS Protection**: Notification messages support HTML but should sanitize user input
4. **CSRF**: Consider adding CSRF tokens for POST requests in production

---

## Future Enhancements

1. **Real-time Updates**: WebSocket/SSE for instant notifications
2. **Push Notifications**: Browser push notification support
3. **Notification Preferences**: User settings for notification types
4. **Notification Grouping**: "John and 5 others liked your post"
5. **Notification Archive**: View all notifications (with pagination)
6. **Sound Alerts**: Audio notification for new items
7. **Desktop Notifications**: OS-level notification support

---

## Troubleshooting

### Badge not showing

- Check if user has unread notifications: `SELECT * FROM notifications WHERE user_id=? AND is_read=0`
- Check browser console for API errors
- Verify `loadNotificationCount()` is called on page load

### Notifications not loading

- Check API endpoint: `api/notifications.php?action=get_notifications`
- Verify database has notifications for the user
- Check browser console for JavaScript errors
- Ensure `loadNotifications()` is called when dropdown opens

### Auto-refresh not working

- Check if `setInterval` is running (shouldn't have console errors)
- Verify API endpoints are accessible
- Check if user session is still valid

### Notification colors wrong

- Check `getNotificationColor()` function for type mapping
- Verify notification type in database matches enum values

---

## Testing with Sample Data

Run the SQL script to add test notifications:

```bash
# Login to MySQL
mysql -u root -p

# Select database
USE uiu_social_connect;

# Run sample data script
SOURCE c:/xampp/htdocs/UIU-Social-Connect-main/src/database/sample_notifications.sql;

# Verify
SELECT * FROM notifications WHERE user_id = 2 ORDER BY created_at DESC;
```

**Note:** Change `user_id = 2` to an actual user ID from your users table.

---

## Conclusion

The navbar is now fully dynamic with:

- ✅ Real-time notification counts
- ✅ Real-time message counts
- ✅ Interactive notification dropdown
- ✅ Auto-refresh every 30 seconds
- ✅ Mark as read functionality
- ✅ Navigation to referenced content
- ✅ Automatic notification creation on likes/comments
- ✅ Integration with admin approval system
- ✅ Beautiful UI with color coding
- ✅ Empty states and loading indicators
- ✅ Mobile responsive

All static placeholders have been replaced with dynamic, database-driven content!
