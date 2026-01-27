# Profile Data Architecture - UIU Social Connect

## Overview

This document explains the centralized profile data management system where **user_id** is the single source of truth for all user-related data across the platform.

---

## Core Principle: Single Source of Truth

### ✅ Centralized User Data

All user profile information (name, profile image, bio, etc.) is stored **ONLY** in the `users` table.

```sql
users table:
- id (PRIMARY KEY) - Unique identifier
- full_name - User's display name
- profile_image - Profile photo path
- cover_image - Cover photo path
- email, role, bio, skills, etc.
```

### ❌ No Data Duplication

Profile data is **NEVER** duplicated in other tables. All modules reference users via `user_id` foreign key.

**WRONG Approach:**

```sql
-- DON'T DO THIS
CREATE TABLE posts (
    id INT,
    user_id INT,
    author_name VARCHAR(255),  -- ❌ WRONG: Duplicates data from users table
    author_image VARCHAR(255)   -- ❌ WRONG: Duplicates data from users table
);
```

**CORRECT Approach:**

```sql
-- ✅ CORRECT
CREATE TABLE posts (
    id INT,
    user_id INT,  -- ✅ Only store the foreign key reference
    content TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## How It Works

### 1. Database Schema (Implemented ✅)

All tables use `user_id` as a foreign key:

```sql
-- Posts reference users
CREATE TABLE posts (
    user_id INT NOT NULL,
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Comments reference users
CREATE TABLE comments (
    user_id INT NOT NULL,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages reference users
CREATE TABLE messages (
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Documents reference users
CREATE TABLE documents (
    user_id INT NOT NULL,
    CONSTRAINT fk_documents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Marketplace items reference users
CREATE TABLE marketplace_items (
    user_id INT NOT NULL,
    CONSTRAINT fk_marketplace_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Groups reference users
CREATE TABLE groups (
    creator_id INT NOT NULL,
    CONSTRAINT fk_groups_creator FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Group members reference users
CREATE TABLE group_members (
    user_id INT NOT NULL,
    CONSTRAINT fk_group_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Events reference users
CREATE TABLE events (
    user_id INT NOT NULL,
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Jobs reference users
CREATE TABLE jobs (
    user_id INT NOT NULL,
    CONSTRAINT fk_jobs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notices reference users
CREATE TABLE notices (
    user_id INT NOT NULL,
    CONSTRAINT fk_notices_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 2. Fetching Data with JOINs (Implemented ✅)

Always use `INNER JOIN` or `LEFT JOIN` to get user data dynamically:

**Example: Posts API**

```php
// api/posts.php - getAllPosts()
$sql = "SELECT
            p.*,
            u.full_name as author_name,      -- ✅ Fetch name from users table
            u.role as author_role,
            u.profile_image as author_image  -- ✅ Fetch image from users table
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id  -- ✅ JOIN on user_id
        WHERE p.is_approved = 1
        ORDER BY p.created_at DESC";
```

**Example: Comments**

```php
// When fetching comments
$sql = "SELECT
            c.*,
            u.full_name as user_name,        -- ✅ Always JOIN with users
            u.profile_image
        FROM comments c
        INNER JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC";
```

**Example: Documents**

```php
// api/documents.php
$sql = "SELECT
            d.*,
            u.full_name as uploader_name,    -- ✅ Real-time user data
            u.profile_image
        FROM documents d
        INNER JOIN users u ON d.user_id = u.id
        WHERE d.is_approved = 1";
```

**Example: Group Members**

```php
// api/groups.php - getMembers()
$sql = "SELECT
            u.id,
            u.full_name,                      -- ✅ Always from users table
            u.profile_image,
            u.role,
            gm.role as member_role,
            gm.joined_at
        FROM group_members gm
        INNER JOIN users u ON gm.user_id = u.id
        WHERE gm.group_id = ?";
```

**Example: Messages**

```php
// api/messages.php
$sql = "SELECT
            m.*,
            sender.full_name as sender_name,      -- ✅ JOIN for sender
            sender.profile_image as sender_image,
            receiver.full_name as receiver_name   -- ✅ JOIN for receiver
        FROM messages m
        INNER JOIN users sender ON m.sender_id = sender.id
        INNER JOIN users receiver ON m.receiver_id = receiver.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC";
```

### 3. Automatic Updates (Already Working ✅)

When a user updates their profile, changes reflect **everywhere automatically** because all data is fetched via JOINs.

**User Profile Update Flow:**

```
User edits profile in dashboard/profile.php
        ↓
Request sent to api/users.php?action=update_profile
        ↓
UPDATE users SET full_name = ?, profile_image = ? WHERE id = ?
        ↓
Next page load: All JOINs fetch the NEW data automatically
        ↓
✅ Updated name/image appears in:
   - Posts (author name)
   - Comments (commenter name)
   - Messages (sender/receiver name)
   - Documents (uploader name)
   - Groups (member name)
   - Marketplace (seller name)
   - Events (organizer name)
   - Everywhere else!
```

**No manual updates needed** in other tables because there's no duplicated data to update!

---

## Security Implementation (Implemented ✅)

### Profile Owner Permissions

**Location:** `api/users.php`

```php
function updateProfile($db)
{
    // ✅ SECURITY: Only logged-in user can update their OWN profile
    $userId = $_SESSION['user_id'];  // Only update session user's profile

    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    $fullName = trim($data['full_name'] ?? '');
    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
        return;
    }

    // ✅ WHERE id = ? ensures user can only update their own data
    $sql = "UPDATE users SET full_name = ?, bio = ?, student_id = ? WHERE id = ?";
    $result = $db->query($sql, [$fullName, $bio, $studentId, $userId]);

    if ($result) {
        $_SESSION['user_name'] = $fullName;  // Update session
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    }
}

function uploadPhoto($db)
{
    // ✅ SECURITY: Only logged-in user can upload their OWN photo
    $userId = $_SESSION['user_id'];  // Only update session user's photo

    // Validation
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        return;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
        return;
    }

    // ✅ WHERE id = ? ensures user can only update their own image
    $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
    $result = $db->query($sql, [$dbPath, $userId]);
}
```

### View Permissions

**Anyone can view approved user profiles:**

```php
function getProfile($db)
{
    // ✅ Can view any user's profile (if approved)
    $userId = intval($_GET['user_id'] ?? $_SESSION['user_id']);

    $sql = "SELECT id, full_name, email, role, bio, skills, profile_image, cover_image, student_id, created_at
            FROM users WHERE id = ? AND is_approved = 1";  // ✅ Only approved users

    $user = $db->query($sql, [$userId]);

    echo json_encode(['success' => true, 'user' => $user[0]]);
}
```

### Access Control Summary

| Action             | Permission                   | Implementation                     |
| ------------------ | ---------------------------- | ---------------------------------- |
| **View Profile**   | Anyone (if user is approved) | `WHERE id = ? AND is_approved = 1` |
| **Edit Profile**   | Owner only                   | `WHERE id = $_SESSION['user_id']`  |
| **Upload Photo**   | Owner only                   | `WHERE id = $_SESSION['user_id']`  |
| **Delete Account** | Owner only or Admin          | Check in deletion logic            |

---

## Validation Rules (Implemented ✅)

### Profile Update Validation

```php
// In updateProfile()

// 1. Name validation
$fullName = trim($data['full_name'] ?? '');
if (empty($fullName)) {
    return error('Name cannot be empty');
}

// 2. Bio validation (optional but has character limit in DB)
$bio = trim($data['bio'] ?? '');
// TEXT field in DB allows up to 65,535 characters

// 3. Student ID validation (optional)
$studentId = trim($data['student_id'] ?? '');
// VARCHAR(50) in DB
```

### Photo Upload Validation

```php
// In uploadPhoto()

// 1. File type validation
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    return error('Invalid file type');
}

// 2. File size validation
if ($file['size'] > 5 * 1024 * 1024) {  // 5MB limit
    return error('File too large (max 5MB)');
}

// 3. Upload error check
if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    return error('No file uploaded');
}

// 4. Filename sanitization
$fileName = uniqid() . '_' . $userId . '.' . $fileExt;  // Unique + secure filename

// 5. Old file cleanup
if ($oldImage && $oldImage !== 'default-avatar.png' && file_exists('../' . $oldImage)) {
    unlink('../' . $oldImage);  // Delete old image
}
```

---

## Frontend Integration (Already Implemented ✅)

### Displaying User Names as Clickable Links

All user names across the dashboard link to profile pages:

**Pattern:**

```html
<a
  href="profile.php?id=${user_id}"
  style="color: var(--text-color); text-decoration: none;"
  onmouseover="this.style.color='var(--primary-orange)'"
  onmouseout="this.style.color='var(--text-color)'"
>
  ${escapeHtml(userName)}
</a>
```

**Implemented in:**

- ✅ Posts (author names) - `dashboard/newsfeed.php`
- ✅ Comments (commenter names) - `dashboard/newsfeed.php`
- ✅ Documents (uploader names) - `dashboard/documents.php`
- ✅ Groups (member names) - `dashboard/groups.php`
- ✅ Messages (conversation names) - `dashboard/messages.php`
- ✅ Marketplace (seller names) - `dashboard/marketplace.php`
- ✅ Profile posts (author names) - `dashboard/profile.php`
- ✅ Friends list - `dashboard/newsfeed.php`
- ✅ Teachers list - `dashboard/newsfeed.php`

### Profile View Logic

```javascript
// dashboard/profile.php

// Load user profile
async function loadProfile(userId) {
    const response = await fetch(`../api/users.php?action=get_profile&user_id=${userId}`);
    const data = await response.json();

    if (data.success) {
        const user = data.user;

        // Display profile data
        document.getElementById('profileName').textContent = user.full_name;
        document.getElementById('profileImage').src = user.profile_image;
        document.getElementById('profileBio').textContent = user.bio;

        // ✅ Show edit button ONLY if viewing own profile
        const isOwnProfile = userId == <?php echo $_SESSION['user_id']; ?>;
        if (isOwnProfile) {
            document.getElementById('editProfileBtn').style.display = 'block';
        } else {
            document.getElementById('editProfileBtn').style.display = 'none';
        }
    }
}
```

---

## Benefits of This Architecture

### 1. **Data Consistency** ✅

- Single source of truth eliminates data conflicts
- No need to update multiple tables
- No risk of outdated cached data

### 2. **Automatic Updates** ✅

- Profile changes reflect everywhere instantly
- No manual synchronization needed
- Real-time data via JOINs

### 3. **Storage Efficiency** ✅

- No duplicate data
- Reduced database size
- Lower storage costs

### 4. **Easy Maintenance** ✅

- Update logic in one place (users table)
- Simple to add new features
- Clear data relationships

### 5. **Scalability** ✅

- Database normalization (3NF)
- Indexed foreign keys for fast JOINs
- ON DELETE CASCADE handles cleanup

### 6. **Security** ✅

- Clear ownership boundaries
- Session-based access control
- Parameterized queries prevent SQL injection

---

## Database Performance Optimization

### Indexes Created

```sql
-- Users table indexes
INDEX idx_email (email)
INDEX idx_approved (is_approved)
INDEX idx_role (role)

-- Foreign key indexes on all tables
INDEX idx_user_id (user_id)  -- On posts, comments, documents, etc.
INDEX idx_sender_id (sender_id)  -- On messages
INDEX idx_receiver_id (receiver_id)  -- On messages
```

### JOIN Performance

- `INNER JOIN` on indexed `user_id` columns is very fast
- Foreign key constraints ensure referential integrity
- `ON DELETE CASCADE` automatically cleans up orphaned records

---

## Testing Scenarios

### Scenario 1: User Updates Name

```
1. User "John Doe" updates name to "John Smith"
2. System executes: UPDATE users SET full_name = 'John Smith' WHERE id = 123
3. Next page refresh:
   ✅ All posts show "John Smith" as author
   ✅ All comments show "John Smith"
   ✅ All messages show "John Smith"
   ✅ All documents show "John Smith" as uploader
   ✅ Group member list shows "John Smith"
   ✅ Marketplace items show "John Smith" as seller
```

### Scenario 2: User Updates Profile Image

```
1. User uploads new profile photo
2. System executes: UPDATE users SET profile_image = 'new_image.jpg' WHERE id = 123
3. Next page refresh:
   ✅ Profile page shows new image
   ✅ Posts show new image for author avatar
   ✅ Comments show new image
   ✅ Messages show new image
   ✅ Group members show new image
   ✅ All avatars updated automatically
```

### Scenario 3: Unauthorized Edit Attempt

```
1. User A (id=123) tries to edit User B's profile (id=456)
2. JavaScript calls: api/users.php?action=update_profile
3. Server checks: $_SESSION['user_id'] == 123
4. Server executes: UPDATE users SET ... WHERE id = 123 (session user only)
5. Result: ✅ User can only edit their own profile
```

### Scenario 4: Profile Viewing

```
1. User clicks on "John Smith" anywhere in dashboard
2. Redirects to: profile.php?id=123
3. System loads: SELECT * FROM users WHERE id = 123 AND is_approved = 1
4. Result: ✅ Anyone can view approved profiles
5. Edit button: ❌ Hidden for others, ✅ Shown only to profile owner
```

---

## Code Example: Adding New Module

When creating a new feature that needs user data:

```php
// ❌ WRONG: Don't duplicate user data
CREATE TABLE new_feature (
    id INT,
    user_id INT,
    user_name VARCHAR(255),  -- ❌ DON'T DO THIS
    user_image VARCHAR(255)   -- ❌ DON'T DO THIS
);

// ✅ CORRECT: Only store user_id
CREATE TABLE new_feature (
    id INT,
    user_id INT,
    feature_data TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

// ✅ Fetch data with JOIN
$sql = "SELECT
            f.*,
            u.full_name,      -- ✅ Get from users table
            u.profile_image   -- ✅ Always real-time data
        FROM new_feature f
        INNER JOIN users u ON f.user_id = u.id
        WHERE f.some_condition = ?";
```

---

## Summary

### ✅ What We Have

1. **Centralized user data** in `users` table
2. **All modules** use `user_id` foreign keys
3. **JOINs everywhere** to fetch real-time data
4. **Security**: Only owners can edit profiles
5. **Validation**: File type, size, required fields
6. **Automatic updates** across entire platform
7. **Clickable user names** linking to profiles
8. **No data duplication**
9. **Scalable architecture**
10. **Performance optimized** with indexes

### ✅ What Happens When User Updates Profile

- One UPDATE query on users table
- Changes reflect everywhere automatically
- No additional code needed
- Real-time consistency

### ✅ Security Model

- Edit: **Owner only** (`WHERE id = $_SESSION['user_id']`)
- View: **Anyone** (if user is approved)
- Upload: **Owner only** with file validation
- All actions require login session

---

## Conclusion

The UIU Social Connect platform follows **database normalization best practices** with **user_id as the single source of truth**. This architecture ensures:

- **Data integrity** through foreign key constraints
- **Automatic updates** via JOIN queries
- **Security** through session-based access control
- **Scalability** with indexed relationships
- **No over-engineering** - simple, clean, and effective

✅ **The system is already properly implemented!**
