# Quick Reference: Profile Data System

## Core Concept

**user_id is the ONLY identifier. Never duplicate profile data (name, image) in other tables.**

---

## ✅ DO's

### 1. Store Only user_id

```sql
CREATE TABLE your_table (
    id INT PRIMARY KEY,
    user_id INT NOT NULL,
    your_data TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

### 2. Always JOIN with users table

```php
$sql = "SELECT
            t.*,
            u.full_name,
            u.profile_image,
            u.role
        FROM your_table t
        INNER JOIN users u ON t.user_id = u.id
        WHERE some_condition = ?";
```

### 3. Secure Updates (Owner Only)

```php
function updateProfile($db) {
    $userId = $_SESSION['user_id'];  // ✅ Use session user_id

    // Validate input
    $fullName = trim($data['full_name'] ?? '');
    if (empty($fullName)) {
        return error('Name cannot be empty');
    }

    // Update ONLY own profile
    $sql = "UPDATE users SET full_name = ? WHERE id = ?";
    $db->query($sql, [$fullName, $userId]);
}
```

### 4. Validate File Uploads

```php
// File type validation
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    return error('Invalid file type');
}

// File size validation (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    return error('File too large');
}
```

---

## ❌ DON'Ts

### 1. Never Store Profile Data in Other Tables

```sql
-- ❌ WRONG
CREATE TABLE posts (
    id INT,
    user_id INT,
    author_name VARCHAR(255),  -- ❌ DON'T DO THIS
    author_image VARCHAR(255)   -- ❌ DON'T DO THIS
);
```

### 2. Never Manually Copy Data

```php
// ❌ WRONG
$userName = "John Doe";
$sql = "INSERT INTO posts (user_id, author_name, content)
        VALUES (?, ?, ?)";  // ❌ Don't store author_name
```

### 3. Never Allow Editing Other Users' Profiles

```php
// ❌ WRONG - Uses user_id from request (can be manipulated)
$userId = $_GET['user_id'];  // ❌ INSECURE!
$sql = "UPDATE users SET full_name = ? WHERE id = ?";

// ✅ CORRECT - Always use session user_id
$userId = $_SESSION['user_id'];
$sql = "UPDATE users SET full_name = ? WHERE id = ?";
```

---

## Common Tasks

### Display User Name (Clickable)

```html
<a
  href="profile.php?id=<?php echo $userId; ?>"
  style="color: var(--text-color); text-decoration: none;"
  onmouseover="this.style.color='var(--primary-orange)'"
  onmouseout="this.style.color='var(--text-color)'"
>
  <?php echo htmlspecialchars($userName); ?>
</a>
```

### Check if Viewing Own Profile

```php
$profileUserId = intval($_GET['id'] ?? $_SESSION['user_id']);
$isOwnProfile = ($profileUserId == $_SESSION['user_id']);

if ($isOwnProfile) {
    // Show edit button
} else {
    // Hide edit button
}
```

### Fetch User Data

```php
// Single user
$sql = "SELECT id, full_name, profile_image, role, bio
        FROM users
        WHERE id = ? AND is_approved = 1";
$user = $db->query($sql, [$userId])[0];

// Multiple users with JOIN
$sql = "SELECT
            p.id,
            p.content,
            u.full_name as author_name,
            u.profile_image
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.is_approved = 1";
$posts = $db->query($sql);
```

---

## API Endpoints (Already Implemented)

### Get Profile (Anyone)

```javascript
fetch("../api/users.php?action=get_profile&user_id=123");
```

### Update Profile (Owner Only)

```javascript
fetch("../api/users.php?action=update_profile", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    full_name: "New Name",
    bio: "New bio text",
    student_id: "011221234",
  }),
});
```

### Upload Photo (Owner Only)

```javascript
const formData = new FormData();
formData.append("photo", fileInput.files[0]);
formData.append("type", "profile"); // or 'cover'

fetch("../api/users.php?action=upload_photo", {
  method: "POST",
  body: formData,
});
```

---

## Security Checklist

- ✅ Session check: `if (!isset($_SESSION['user_id'])) exit;`
- ✅ Owner check: `WHERE id = $_SESSION['user_id']`
- ✅ Input validation: `trim()`, `empty()` checks
- ✅ File type validation: whitelist allowed types
- ✅ File size validation: max 5MB
- ✅ SQL injection prevention: parameterized queries
- ✅ XSS prevention: `htmlspecialchars()` or `escapeHtml()`

---

## Why This Works

1. **One UPDATE** on users table → Changes everywhere
2. **No data duplication** → No sync issues
3. **Real-time data** via JOINs → Always current
4. **Clear ownership** → Secure by design
5. **Scalable** → Standard database normalization

---

## Files Reference

- **Schema:** `database/schema.sql`
- **API:** `api/users.php`
- **Profile Page:** `dashboard/profile.php`
- **Full Docs:** `PROFILE_DATA_ARCHITECTURE.md`

---

## Quick Test

**Update user name:**

1. Edit profile → Change name to "Test User"
2. Refresh posts page → Author name updated ✅
3. Check messages → Sender name updated ✅
4. Check documents → Uploader name updated ✅
5. Check groups → Member name updated ✅

**Result:** All data updated automatically with ONE database query!
