# Profile System Testing Checklist

## Prerequisites

- [ ] Apache and MySQL running (XAMPP)
- [ ] Database `uiu_social_connect` created
- [ ] At least 2 test users registered and approved
- [ ] User logged in to dashboard

---

## Test 1: View Profile (Anyone Can View)

### Steps:

1. [ ] Log in as User A
2. [ ] Navigate to dashboard/profile.php
3. [ ] Click on any other user's name (e.g., User B)
4. [ ] URL changes to: profile.php?id=<User_B_ID>

### Expected Results:

- [ ] ✅ User B's profile loads correctly
- [ ] ✅ Shows User B's name, image, bio, skills
- [ ] ✅ Shows User B's posts
- [ ] ✅ "Edit Profile" button is HIDDEN (not your profile)
- [ ] ✅ Can view but cannot edit

### Database Check:

```sql
-- Check if data is fetched correctly
SELECT id, full_name, profile_image, bio
FROM users
WHERE id = <User_B_ID> AND is_approved = 1;
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 2: View Own Profile

### Steps:

1. [ ] Navigate to dashboard/profile.php (no ?id parameter)
2. [ ] Or click on your own name anywhere

### Expected Results:

- [ ] ✅ Your profile loads
- [ ] ✅ Shows your name, image, bio, skills
- [ ] ✅ Shows your posts
- [ ] ✅ "Edit Profile" button is VISIBLE
- [ ] ✅ Can edit your data

### Code Check:

```php
// In profile.php, verify this logic exists:
$profileUserId = intval($_GET['id'] ?? $_SESSION['user_id']);
$isOwnProfile = ($profileUserId == $_SESSION['user_id']);

if ($isOwnProfile) {
    // Show edit button
}
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 3: Update Profile Name

### Steps:

1. [ ] Navigate to your profile page
2. [ ] Click "Edit Profile" button
3. [ ] Change name from "John Doe" to "John Smith"
4. [ ] Click "Save"
5. [ ] Wait for success message
6. [ ] Page reloads

### Expected Results:

- [ ] ✅ Success alert: "Profile updated successfully!"
- [ ] ✅ Profile page shows new name: "John Smith"
- [ ] ✅ Navigate to newsfeed
- [ ] ✅ All YOUR posts show author: "John Smith"
- [ ] ✅ All YOUR comments show name: "John Smith"
- [ ] ✅ Messages show sender: "John Smith"
- [ ] ✅ Documents show uploader: "John Smith"
- [ ] ✅ Group members list shows: "John Smith"
- [ ] ✅ Marketplace items show seller: "John Smith"

### Database Check:

```sql
-- Verify name was updated
SELECT full_name FROM users WHERE id = <Your_User_ID>;
-- Should return: John Smith

-- Check if posts reference user correctly
SELECT p.id, p.content, u.full_name as author_name
FROM posts p
INNER JOIN users u ON p.user_id = u.id
WHERE p.user_id = <Your_User_ID>;
-- author_name should be "John Smith" for all your posts
```

### API Test:

```bash
# Send update request (use browser console)
fetch('../api/users.php?action=update_profile', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        full_name: 'John Smith',
        bio: 'Test bio',
        student_id: '011221234'
    })
})
.then(r => r.json())
.then(data => console.log(data));

# Expected response:
# { success: true, message: "Profile updated successfully" }
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 4: Update Profile Image

### Steps:

1. [ ] Navigate to your profile page
2. [ ] Click on profile avatar
3. [ ] Select a new image (JPEG, PNG, or GIF < 5MB)
4. [ ] Wait for upload
5. [ ] Refresh page

### Expected Results:

- [ ] ✅ Success alert: "Photo uploaded successfully!"
- [ ] ✅ Profile shows new image
- [ ] ✅ Navigate to newsfeed
- [ ] ✅ All YOUR post avatars show new image
- [ ] ✅ All YOUR comment avatars show new image
- [ ] ✅ Messages show new sender image
- [ ] ✅ Group members show new image

### File System Check:

```bash
# Check uploads directory
cd assets/uploads/profiles/
ls -lh
# Should see new file: <timestamp>_<user_id>.jpg
```

### Database Check:

```sql
-- Verify image path was updated
SELECT profile_image FROM users WHERE id = <Your_User_ID>;
-- Should return: assets/uploads/profiles/<filename>.jpg
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 5: Security - Cannot Edit Other User's Profile

### Steps:

1. [ ] Log in as User A (id=123)
2. [ ] Open browser console
3. [ ] Try to update User B's profile (id=456):

```javascript
fetch("../api/users.php?action=update_profile", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    full_name: "Hacked Name",
    bio: "Hacked bio",
  }),
})
  .then((r) => r.json())
  .then((data) => console.log(data));
```

### Expected Results:

- [ ] ✅ Request is accepted (200 status)
- [ ] ✅ BUT only User A's profile is updated (security works!)
- [ ] ✅ User B's profile remains unchanged
- [ ] ✅ Check User A's profile: name changed to "Hacked Name"
- [ ] ✅ Check User B's profile: name UNCHANGED

### Why This Works:

```php
// In api/users.php updateProfile():
$userId = $_SESSION['user_id'];  // Always uses session user_id (User A)
// NOT using $_GET['user_id'] or $_POST['user_id']
// So User A can only update their own profile
```

### Database Check:

```sql
-- User A should have changed name
SELECT full_name FROM users WHERE id = 123;
-- Returns: "Hacked Name"

-- User B should have original name
SELECT full_name FROM users WHERE id = 456;
-- Returns: Original name (unchanged)
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 6: Validation - Empty Name

### Steps:

1. [ ] Navigate to your profile
2. [ ] Click "Edit Profile"
3. [ ] Clear the name field (make it empty)
4. [ ] Click "Save"

### Expected Results:

- [ ] ✅ Error alert: "Name cannot be empty"
- [ ] ✅ Profile is NOT updated
- [ ] ✅ Original name remains in database

### Code Check:

```php
// In api/users.php updateProfile():
if (empty($fullName)) {
    echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
    return;
}
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 7: File Upload Validation

### Test 7a: Invalid File Type

1. [ ] Try to upload .txt file as profile image
2. [ ] Expected: ❌ Error: "Invalid file type"
3. [ ] Profile image NOT updated

### Test 7b: File Too Large

1. [ ] Try to upload image > 5MB
2. [ ] Expected: ❌ Error: "File too large (max 5MB)"
3. [ ] Profile image NOT updated

### Test 7c: Valid Image

1. [ ] Upload valid JPEG < 5MB
2. [ ] Expected: ✅ Success: "Photo uploaded successfully!"
3. [ ] Profile image updated

### Code Check:

```php
// In api/users.php uploadPhoto():
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    return error('Invalid file type');
}

if ($file['size'] > 5 * 1024 * 1024) {
    return error('File too large (max 5MB)');
}
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 8: Data Consistency Across Dashboard

### Setup:

1. [ ] Log in as User A
2. [ ] Update your name to "Test User 123"
3. [ ] Create a post in newsfeed
4. [ ] Comment on someone's post
5. [ ] Upload a document
6. [ ] Join a group
7. [ ] Send a message
8. [ ] List an item in marketplace

### Verification Steps:

Navigate to each page and verify your name shows as "Test User 123":

| Page            | Location          | Expected           |
| --------------- | ----------------- | ------------------ |
| **Newsfeed**    | Post author       | ✅ "Test User 123" |
| **Newsfeed**    | Comment author    | ✅ "Test User 123" |
| **Newsfeed**    | Friends list      | ✅ "Test User 123" |
| **Documents**   | Uploader name     | ✅ "Test User 123" |
| **Groups**      | Member name       | ✅ "Test User 123" |
| **Messages**    | Conversation list | ✅ "Test User 123" |
| **Messages**    | Chat header       | ✅ "Test User 123" |
| **Marketplace** | Seller name       | ✅ "Test User 123" |
| **Profile**     | Profile name      | ✅ "Test User 123" |

### Database Verification:

```sql
-- Verify data consistency
-- All JOINs should return updated name

-- Posts
SELECT p.id, u.full_name as author_name
FROM posts p
INNER JOIN users u ON p.user_id = u.id
WHERE p.user_id = <Your_User_ID>;

-- Comments
SELECT c.id, u.full_name as user_name
FROM comments c
INNER JOIN users u ON c.user_id = u.id
WHERE c.user_id = <Your_User_ID>;

-- Documents
SELECT d.id, u.full_name as uploader_name
FROM documents d
INNER JOIN users u ON d.user_id = u.id
WHERE d.user_id = <Your_User_ID>;

-- Group Members
SELECT gm.group_id, u.full_name as member_name
FROM group_members gm
INNER JOIN users u ON gm.user_id = u.id
WHERE gm.user_id = <Your_User_ID>;

-- Messages
SELECT m.id, u.full_name as sender_name
FROM messages m
INNER JOIN users u ON m.sender_id = u.id
WHERE m.sender_id = <Your_User_ID>;

-- All queries should return "Test User 123"
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 9: Clickable User Names

### Steps:

1. [ ] Navigate to newsfeed
2. [ ] Find a post by another user (User B)
3. [ ] Click on User B's name in post header
4. [ ] Verify redirects to: profile.php?id=<User_B_ID>
5. [ ] Repeat for:
   - [ ] Comment author names
   - [ ] Friends list names
   - [ ] Document uploader names
   - [ ] Group member names
   - [ ] Message conversation names
   - [ ] Marketplace seller names

### Expected Results:

- [ ] ✅ All user names are clickable links
- [ ] ✅ Clicking navigates to correct profile page
- [ ] ✅ Hover effect: text color changes to orange
- [ ] ✅ URL format: profile.php?id=<User_ID>

### Code Pattern Check:

```html
<!-- All user names should follow this pattern -->
<a
  href="profile.php?id=${user_id}"
  style="color: var(--text-color); text-decoration: none;"
  onmouseover="this.style.color='var(--primary-orange)'"
  onmouseout="this.style.color='var(--text-color)'"
>
  ${escapeHtml(userName)}
</a>
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 10: Session Security

### Test 10a: Not Logged In

1. [ ] Log out of dashboard
2. [ ] Try to access: api/users.php?action=get_profile
3. [ ] Expected: `{ success: false, message: "Unauthorized" }`

### Test 10b: Session Timeout

1. [ ] Delete browser cookies / clear session
2. [ ] Try to update profile
3. [ ] Expected: Redirect to login or "Unauthorized"

### Code Check:

```php
// At top of api/users.php
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 11: Database Integrity

### Foreign Key Constraints Test:

```sql
-- Test 1: Try to create post for non-existent user
INSERT INTO posts (user_id, content) VALUES (99999, 'Test');
-- Expected: ❌ Foreign key constraint fails

-- Test 2: Delete user and check cascading
-- Create test user
INSERT INTO users (full_name, email, password, role, is_approved)
VALUES ('Delete Test', 'delete@test.com', 'hash', 'Student', 1);
SET @test_user_id = LAST_INSERT_ID();

-- Create post by test user
INSERT INTO posts (user_id, content, is_approved)
VALUES (@test_user_id, 'Test post', 1);

-- Delete test user
DELETE FROM users WHERE id = @test_user_id;

-- Check if post was automatically deleted (CASCADE)
SELECT * FROM posts WHERE user_id = @test_user_id;
-- Expected: ✅ No rows (post auto-deleted)
```

**Status:** [ ] PASS / [ ] FAIL

---

## Test 12: Performance Test

### Setup:

1. [ ] Create 100 posts in database
2. [ ] Each post by different user

### Test Query Performance:

```sql
-- Measure JOIN performance
SET @start = NOW(6);

SELECT p.*, u.full_name, u.profile_image
FROM posts p
INNER JOIN users u ON p.user_id = u.id
WHERE p.is_approved = 1
ORDER BY p.created_at DESC
LIMIT 100;

SET @end = NOW(6);
SELECT TIMESTAMPDIFF(MICROSECOND, @start, @end) / 1000 as execution_time_ms;

-- Expected: < 50ms for 100 rows
```

### Index Verification:

```sql
-- Check if indexes exist
SHOW INDEX FROM posts WHERE Column_name = 'user_id';
SHOW INDEX FROM comments WHERE Column_name = 'user_id';
SHOW INDEX FROM documents WHERE Column_name = 'user_id';
SHOW INDEX FROM group_members WHERE Column_name = 'user_id';

-- All should have indexes for fast JOINs
```

**Status:** [ ] PASS / [ ] FAIL

---

## Final Checklist

### Architecture ✅

- [ ] users table is single source of truth
- [ ] All tables use user_id foreign key
- [ ] No profile data duplication
- [ ] All queries use JOINs

### Security ✅

- [ ] Only owner can edit profile
- [ ] Anyone can view approved profiles
- [ ] Session-based access control
- [ ] File upload validation
- [ ] Input validation

### Functionality ✅

- [ ] Profile update works
- [ ] Image upload works
- [ ] Changes reflect everywhere automatically
- [ ] User names are clickable
- [ ] Validation prevents invalid data

### Database ✅

- [ ] Foreign key constraints exist
- [ ] ON DELETE CASCADE works
- [ ] Indexes for performance
- [ ] No orphaned records

### Performance ✅

- [ ] JOINs are fast (< 50ms)
- [ ] No N+1 query problems
- [ ] Proper indexing

---

## Bug Report Template

If any test fails, use this template:

````
BUG REPORT
──────────

Test Number: Test #X
Test Name: [Name]
Status: ❌ FAIL

Steps to Reproduce:
1. [Step 1]
2. [Step 2]
3. [Step 3]

Expected Result:
[What should happen]

Actual Result:
[What actually happened]

Error Message (if any):
[Error text]

Database State:
```sql
-- Relevant query
SELECT ...
````

Affected Files:

- [File 1]
- [File 2]

Severity: [ ] Critical / [ ] High / [ ] Medium / [ ] Low

```

---

## Test Summary

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | View Other Profile | [ ] PASS / [ ] FAIL | |
| 2 | View Own Profile | [ ] PASS / [ ] FAIL | |
| 3 | Update Name | [ ] PASS / [ ] FAIL | |
| 4 | Update Image | [ ] PASS / [ ] FAIL | |
| 5 | Security Test | [ ] PASS / [ ] FAIL | |
| 6 | Empty Name Validation | [ ] PASS / [ ] FAIL | |
| 7 | File Upload Validation | [ ] PASS / [ ] FAIL | |
| 8 | Data Consistency | [ ] PASS / [ ] FAIL | |
| 9 | Clickable Names | [ ] PASS / [ ] FAIL | |
| 10 | Session Security | [ ] PASS / [ ] FAIL | |
| 11 | Database Integrity | [ ] PASS / [ ] FAIL | |
| 12 | Performance | [ ] PASS / [ ] FAIL | |

**Overall Status:** [ ] ALL PASS / [ ] SOME FAILURES

**Date Tested:** _______________
**Tested By:** _______________
**Environment:** [ ] Local / [ ] Staging / [ ] Production

---

## Notes
```
