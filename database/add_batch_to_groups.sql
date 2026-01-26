-- Migration: Add batch column to groups table
-- Run this if you already have the database set up

USE uiu_social_connect;

-- Add batch column to groups table
ALTER TABLE groups 
ADD COLUMN batch VARCHAR(20) AFTER department;

-- Add index for batch
ALTER TABLE groups 
ADD INDEX idx_batch (batch);

-- Update existing course groups to have NULL batch (they need to be recreated)
-- Or you can manually update them with appropriate batch values
