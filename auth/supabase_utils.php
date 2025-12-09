<?php
// supabase_utils.php

// --- Supabase Configuration (Constants) ---
// These are defined here to ensure they are available to all utility functions.
// If these are already reliably defined in '../auth/config.php', you may choose to remove them here.
if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', 'https://drogypndtmqhpohoedzl.supabase.co');
}
if (!defined('SUPABASE_KEY')) {
    // Service key for administrative actions (should be protected server-side)
    define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRyb2d5cG5kdG1xaHBvaG9lZHpsIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2NDIwOTc4NiwiZXhwIjoyMDc5Nzg1Nzg2fQ.7dOEhqDnQQwzc6uwwQPhKkY7hbfdqLow-h6rYtqKnQA'); 
}



?>