-- Keep the database default aligned with Auth::can(): a permission must be
-- granted explicitly. Existing rows and their current values are preserved.
ALTER TABLE user_permissions
    MODIFY is_allowed TINYINT(1) NOT NULL DEFAULT 0;
