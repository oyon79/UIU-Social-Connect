# Profile Data System - Visual Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                      USERS TABLE (SINGLE SOURCE OF TRUTH)        │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ id | full_name | email | profile_image | cover_image | ... │ │
│  ├────────────────────────────────────────────────────────────┤ │
│  │ 1  | John Doe  | ...   | john.jpg      | cover.jpg   | ... │ │
│  │ 2  | Jane Smith| ...   | jane.jpg      | cover2.jpg  | ... │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
           ▲         ▲         ▲         ▲         ▲
           │         │         │         │         │
           │ JOIN    │ JOIN    │ JOIN    │ JOIN    │ JOIN
           │         │         │         │         │
    ┌──────┴───┬─────┴────┬────┴─────┬───┴──────┬──┴────────┐
    │          │          │          │          │           │
┌───▼────┐ ┌──▼─────┐ ┌──▼──────┐ ┌─▼────────┐ ┌▼─────────┐ ┌▼────────┐
│ posts  │ │comments│ │messages │ │documents │ │groups    │ │ jobs    │
├────────┤ ├────────┤ ├─────────┤ ├──────────┤ ├──────────┤ ├─────────┤
│user_id │ │user_id │ │sender_id│ │user_id   │ │creator_id│ │user_id  │
└────────┘ └────────┘ │recv_id  │ └──────────┘ └──────────┘ └─────────┘
                      └─────────┘

    ┌────────────┐  ┌────────────┐  ┌──────────────┐
    │marketplace │  │  events    │  │   notices    │
    ├────────────┤  ├────────────┤  ├──────────────┤
    │  user_id   │  │  user_id   │  │   user_id    │
    └────────────┘  └────────────┘  └──────────────┘
```

## Data Flow: Profile Update

```
┌─────────────────────────────────────────────────────────────┐
│  User edits profile in dashboard/profile.php                │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  JavaScript sends POST to api/users.php?action=update_profile│
│  Body: { full_name: "John Smith", bio: "...", ... }         │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  Security Check: $_SESSION['user_id']                       │
│  ✅ User can only edit their OWN profile                     │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  Validation:                                                 │
│  ✓ Name not empty                                           │
│  ✓ Input sanitized                                          │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  Execute ONE SQL UPDATE:                                     │
│  UPDATE users                                                │
│  SET full_name = 'John Smith', bio = '...'                  │
│  WHERE id = $_SESSION['user_id']                            │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  ✅ SUCCESS! Changes saved to database                       │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  Next page load: ALL JOINs fetch NEW data automatically     │
│                                                              │
│  ✅ Posts:       author_name = "John Smith"                 │
│  ✅ Comments:    user_name = "John Smith"                   │
│  ✅ Messages:    sender_name = "John Smith"                 │
│  ✅ Documents:   uploader_name = "John Smith"               │
│  ✅ Groups:      member_name = "John Smith"                 │
│  ✅ Marketplace: seller_name = "John Smith"                 │
│  ✅ Events:      organizer_name = "John Smith"              │
│  ✅ Everywhere:  Updated automatically!                      │
└─────────────────────────────────────────────────────────────┘
```

## Data Fetch: Posts with Author Info

```
Frontend Request:
┌──────────────────────────────────────┐
│  fetch('api/posts.php?action=get')   │
└──────────────┬───────────────────────┘
               │
               ▼
Backend Query:
┌─────────────────────────────────────────────────────────┐
│  SELECT p.*, u.full_name, u.profile_image, u.role       │
│  FROM posts p                                           │
│  INNER JOIN users u ON p.user_id = u.id                │
│  WHERE p.is_approved = 1                                │
│  ORDER BY p.created_at DESC                             │
└──────────────┬──────────────────────────────────────────┘
               │
               ▼
Result:
┌─────────────────────────────────────────────────────────┐
│ [                                                        │
│   {                                                      │
│     id: 1,                                               │
│     content: "Hello world!",                             │
│     user_id: 123,                                        │
│     full_name: "John Doe",      ← From users table      │
│     profile_image: "john.jpg",  ← From users table      │
│     role: "Student"             ← From users table      │
│   }                                                      │
│ ]                                                        │
└─────────────────────────────────────────────────────────┘
```

## Security Model

```
┌────────────────────────────────────────────────────────────┐
│                        USER ACTIONS                        │
└────────────────────────────────────────────────────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
        ▼                 ▼                 ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ View Profile │  │ Edit Profile │  │ Upload Photo │
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       │                 │                 │
       ▼                 ▼                 ▼
   ┌────────┐      ┌──────────┐     ┌──────────┐
   │ALLOWED │      │  CHECK   │     │  CHECK   │
   │Everyone│      │ Session  │     │ Session  │
   │(if user│      │ user_id  │     │ user_id  │
   │approved)│     └────┬─────┘     └────┬─────┘
   └────────┘           │                 │
                        ▼                 ▼
                   ┌─────────┐       ┌─────────┐
                   │ Owner?  │       │ Owner?  │
                   ├─────────┤       ├─────────┤
                   │✅ YES   │       │✅ YES   │
                   │  ALLOW  │       │  ALLOW  │
                   │❌ NO    │       │❌ NO    │
                   │  DENY   │       │  DENY   │
                   └─────────┘       └─────────┘

SQL Implementation:
─────────────────
View:   WHERE id = ? AND is_approved = 1
Edit:   WHERE id = $_SESSION['user_id']
Upload: WHERE id = $_SESSION['user_id']
```

## Why No Data Duplication?

```
❌ BAD APPROACH (Data Duplication):
═════════════════════════════════════════════

posts table:
┌────┬─────────┬────────────┬──────────────┬──────────┐
│ id │ user_id │author_name │ author_image │ content  │
├────┼─────────┼────────────┼──────────────┼──────────┤
│ 1  │  123    │ John Doe   │  john.jpg    │ Hello!   │
│ 2  │  123    │ John Doe   │  john.jpg    │ Hi!      │
└────┴─────────┴────────────┴──────────────┴──────────┘
                    ▲              ▲
                    │              │
            DUPLICATED DATA    DUPLICATED DATA

comments table:
┌────┬─────────┬───────────┬─────────────┬──────────┐
│ id │ user_id │ user_name │ user_image  │ comment  │
├────┼─────────┼───────────┼─────────────┼──────────┤
│ 1  │  123    │ John Doe  │  john.jpg   │ Nice!    │
│ 2  │  123    │ John Doe  │  john.jpg   │ Cool!    │
└────┴─────────┴───────────┴─────────────┴──────────┘
                    ▲             ▲
                    │             │
            DUPLICATED DATA   DUPLICATED DATA

Problem: User updates name to "John Smith"
─────────────────────────────────────────
Need to UPDATE:
✗ users table
✗ posts table (all rows)
✗ comments table (all rows)
✗ messages table (all rows)
✗ documents table (all rows)
✗ groups table (all rows)
✗ marketplace table (all rows)
✗ ... and every other table!

Result:
❌ Complex synchronization
❌ Risk of data inconsistency
❌ Wasted storage space
❌ Slow updates
❌ Maintenance nightmare


✅ GOOD APPROACH (Single Source of Truth):
════════════════════════════════════════════

users table (SINGLE SOURCE):
┌────┬────────────┬─────────────┬───────────────┐
│ id │ full_name  │profile_image│    ...        │
├────┼────────────┼─────────────┼───────────────┤
│123 │ John Doe   │  john.jpg   │    ...        │
└────┴────────────┴─────────────┴───────────────┘
  ▲
  │ Referenced by user_id (foreign key)
  │
  ├─────────┬──────────┬──────────┬──────────┐
  │         │          │          │          │
posts    comments   messages  documents   groups
┌────┐   ┌────┐    ┌────┐    ┌────┐     ┌────┐
│123 │   │123 │    │123 │    │123 │     │123 │
└────┘   └────┘    └────┘    └────┘     └────┘
user_id  user_id   sender_id  user_id   creator_id

User updates name to "John Smith"
──────────────────────────────────
UPDATE users SET full_name = 'John Smith' WHERE id = 123

Result:
✅ ONE UPDATE query
✅ Changes reflect everywhere automatically
✅ No data duplication
✅ Fast and efficient
✅ Easy maintenance
```

## Table Relationships

```
                    ┌───────────────┐
                    │     users     │
                    │   (CENTRAL)   │
                    ├───────────────┤
                    │ id (PK)       │
                    │ full_name     │
                    │ profile_image │
                    │ cover_image   │
                    │ email         │
                    │ role          │
                    │ bio           │
                    └───────┬───────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│    posts      │   │   comments    │   │   messages    │
├───────────────┤   ├───────────────┤   ├───────────────┤
│ user_id (FK) ─┼──►│ user_id (FK) ─┼──►│ sender_id(FK)─┤
│ content       │   │ post_id       │   │ receiver_id   │
│ image_url     │   │ content       │   │ message       │
└───────────────┘   └───────────────┘   └───────────────┘

        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│  documents    │   │    groups     │   │  marketplace  │
├───────────────┤   ├───────────────┤   ├───────────────┤
│ user_id (FK) ─┼──►│ creator_id(FK)┼──►│ user_id (FK) ─┤
│ note_name     │   │ name          │   │ title         │
│ file_path     │   │ description   │   │ price         │
└───────────────┘   └───────────────┘   └───────────────┘

FK = Foreign Key (references users.id)
All tables use ON DELETE CASCADE
```

## Performance: JOIN vs Duplication

```
Scenario: Fetch 100 posts with author info
───────────────────────────────────────────

❌ With Data Duplication:
SELECT * FROM posts WHERE is_approved = 1 LIMIT 100
└─ Returns: 100 rows with duplicated author data
   Size: ~50 KB
   Problem: If author name changes, data becomes stale

✅ With JOIN:
SELECT p.*, u.full_name, u.profile_image
FROM posts p
INNER JOIN users u ON p.user_id = u.id
WHERE p.is_approved = 1
LIMIT 100
└─ Returns: 100 rows with real-time author data
   Size: ~50 KB
   Benefit: Always current data + indexed JOIN is fast

Performance:
─────────────
JOIN on indexed user_id: < 0.01s for 1000s of rows
└─ Foreign key creates automatic index
```

## Example: Complete Flow

```
┌──────────────────────────────────────────────────────────┐
│ 1. User "John Doe" logs in                               │
│    Session: user_id = 123, user_name = "John Doe"       │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 2. User navigates to profile page                        │
│    Opens: profile.php?id=123                             │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 3. Check: Is this own profile?                           │
│    if ($_GET['id'] == $_SESSION['user_id'])             │
│    ✅ YES → Show edit button                             │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 4. User clicks "Edit Profile"                            │
│    Shows modal with current data                         │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 5. User changes name to "John Smith" and saves          │
│    POST to api/users.php?action=update_profile          │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 6. API validates:                                        │
│    ✓ Session exists                                      │
│    ✓ Name not empty                                      │
│    ✓ Input sanitized                                     │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 7. Execute UPDATE:                                       │
│    UPDATE users SET full_name = 'John Smith'            │
│    WHERE id = 123                                        │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 8. Page reloads                                          │
│    Profile now shows "John Smith"                        │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 9. User checks newsfeed                                  │
│    ALL posts now show author: "John Smith"               │
│    (Fetched via JOIN with users table)                   │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 10. User checks messages                                 │
│     Sender name: "John Smith"                            │
│     (Fetched via JOIN with users table)                  │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ 11. User checks documents                                │
│     Uploader: "John Smith"                               │
│     (Fetched via JOIN with users table)                  │
└──────────────────────┴───────────────────────────────────┘

✅ Result: ONE database update → Changes everywhere!
```

---

## Summary

✅ **Single Source of Truth:** users table
✅ **No Duplication:** Only user_id stored elsewhere
✅ **Real-time Data:** Always current via JOINs
✅ **Secure:** Owner-only editing
✅ **Scalable:** Standard database normalization
✅ **Simple:** No manual synchronization needed

**This is the CORRECT way to design a multi-user system!**
