# Fix Course Groups Feature - Instructions

## Problem

The system was creating too many course groups (56+) because:

1. Groups were not batch-specific (all students shared the same groups)
2. The system was creating groups for ALL trimesters from 1 to the student's current trimester

## Solution

1. Made groups batch-specific so each batch has their own course groups
2. **Changed to show ONLY current trimester courses** (not cumulative from all previous trimesters)

## Steps to Apply the Fix

### Step 1: Run Database Migration

Run this SQL command in phpMyAdmin or MySQL:

```sql
USE uiu_social_connect;

-- Add batch column to groups table
ALTER TABLE groups
ADD COLUMN batch VARCHAR(20) AFTER department;

-- Add index for batch
ALTER TABLE groups
ADD INDEX idx_batch (batch);
```

Or simply import the file: `database/add_batch_to_groups.sql`

### Step 2: Clean Up Old Groups (Optional but Recommended)

Since old course groups don't have batch information, you should delete them and recreate:

```sql
-- Delete all auto-created course groups
DELETE FROM groups WHERE is_auto_created = 1;
```

### Step 3: Recreate Course Groups

1. Go to Admin Dashboard
2. Navigate to "Course Groups Utility"
3. Click "Create Groups for All Students" button
4. This will create batch-specific groups for all students

## Expected Results

### Students Will See ONLY Their Current Trimester Courses:

- **Trimester 1 Student**: 4 courses only
- **Trimester 5 Student**: 5 courses only (not 23 from all previous trimesters)
- **Trimester 12 Student**: 4 courses only (not all 58 courses)

**Key Change**: Students now see ONLY their current trimester's courses, not cumulative courses from all previous trimesters.

### Course Count Per Trimester (CSE):

- Trimester 1: 4 courses
- Trimester 2: 4 courses
- Trimester 3: 5 courses
- Trimester 4: 5 courses
- Trimester 5: 5 courses
- Trimester 6: 5 courses
- Trimester 7: 5 courses
- Trimester 8: 6 courses
- Trimester 9: 5 courses
- Trimester 10: 5 courses
- Trimester 11: 5 courses
- Trimester 12: 4 courses

## Verification

1. Check that group names now include batch: `CSE 58 - CSE 1110: Introduction to Computer Systems`
2. Students only see groups for their own batch and current trimester
3. "My Courses" filter shows only courses for the student's current trimester
4. A trimester 5 student should see exactly 5 courses, not 23
5. When student updates their trimester, old groups remain but new trimester groups are created

## Files Changed

1. `database/schema.sql` - Added batch column to groups table
2. `database/add_batch_to_groups.sql` - Migration file (NEW)
3. `api/create_course_groups.php` - Updated to create ONLY current trimester groups (batch-specific)
4. `api/auth.php` - Updated registration to create ONLY current trimester groups (batch-specific)
5. `api/groups.php` - Updated to filter groups by batch and current trimester only
6. `admin/create_course_groups.php` - Updated information section

## Notes

- Groups are now batch-specific: students in Batch 58 won't see Batch 59 groups
- **Students see ONLY their current trimester courses** - no cumulative groups from previous trimesters
- Old groups without batch will still be visible (batch IS NULL condition)
- Each trimester has 3-7 courses as expected
- Total groups per student = courses in their current trimester (4-6 courses typically)
- When a student advances to a new trimester, they need to have new groups created for that trimester
