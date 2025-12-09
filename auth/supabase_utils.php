<?php
// D:\xammp\htdocs\project\auth\supabase_utils.php
// This file simulates the actual Supabase/Database interaction functions 
// using mocked data for the citizen dashboard functionality.

/**
 * Mocks the Supabase raw SQL query, primarily used for COUNT operations.
 * @param string $sql The SQL query (used for logic simulation).
 * @param array $params The parameters for the query (not used in mock).
 * @return array The mocked result data.
 */
function supabase_raw_sql(string $sql, array $params = []): array
{
    // Supabase returns an array of objects/rows, even for single count queries.
    // The format is typically: [['count' => N]]

    // 1. Total Bins Count (WHERE area_id = ?)
    if (str_contains($sql, 'COUNT(*) as count FROM bins') && str_contains($sql, 'area_id')) {
        // Mocked as 1 total bin in the user's area
        return [['count' => 1]]; 
    }

    // 2. Nearly Full Alerts Count
    if (str_contains($sql, 'COUNT(*) as count FROM bin_alerts') && str_contains($sql, "'nearly_full'")) {
        // Mocked as 4 bins that are nearly full
        return [['count' => 4]]; 
    }

    // 3. Critical Alerts Count (full, sensor_error, overload)
    if (str_contains($sql, 'COUNT(*) as count FROM bin_alerts') && str_contains($sql, "'full'")) {
        // Mocked as 0 critical alerts
        return [['count' => 0]]; 
    }
    
    // 4. Next Collection Schedule (ORDER BY created_at ASC LIMIT 1)
    if (str_contains($sql, 'collection_route') && str_contains($sql, 'status = \'pending\'')) {
        // Simulating a pending route 12 hours from now
        $future_time = date('Y-m-d H:i:s', strtotime('+12 hours'));
        return [[
            'route_name' => 'Main Street Route',
            // Return a standard timestamp for the format function to handle
            'scheduled_time' => $future_time, 
            'status' => 'pending'
        ]];
    }
    
    return [['count' => 0]]; // Default fallback for count queries
}


/**
 * Mocks fetching a single row from the database (e.g., nearest bin data).
 * @param string $table The table name.
 * @param array $filter The filter conditions (not used in mock).
 * @param string|null $orderBy The column and direction to order by.
 * @return array The mocked result data (single row).
 */
function supabase_fetch_one(string $table, array $filter = [], ?string $orderBy = null): array
{
    // Mock data for the nearest bin
    if ($table === 'bins') {
        return [
            'id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'bin_code' => 'BIN-007',
            'location_name' => '123 Oak Street, City Park Entrance',
            'latitude' => 14.6190,
            'longitude' => 121.0180,
            'last_communication' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        ];
    }
    
    // Mock the latest sensor reading 
    if ($table === 'sensor_readings' && str_contains($orderBy ?? '', 'timestamp DESC')) {
        return [
            'ultrasonic_distance_cm' => 22, // Simulating 78% fill (based on common max height of ~100cm)
            'load_cell_weight_kg' => 8.2,
            'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
        ];
    }
    
    // Mock the last collection log
    if ($table === 'collection_logs' && str_contains($orderBy ?? '', 'collected_at DESC')) {
        return [
            'collected_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ];
    }

    // Mock last citizen report
    if ($table === 'citizen_reports' && str_contains($orderBy ?? '', 'created_at DESC')) {
        return [
            'status' => 'in_progress', // Example: Report is currently being handled
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ];
    }
    
    return [];
}


/**
 * Formats a timestamp into a friendly "Time Ago" string.
 * @param string|null $datetime_str The timestamp string from the database.
 * @return string A human-readable time difference.
 */
function supabase_time_ago(?string $datetime_str): string
{
    if (empty($datetime_str) || !strtotime($datetime_str)) {
        return 'Never';
    }

    $now = new DateTime();
    $then = new DateTime($datetime_str);
    $diff = $now->diff($then);

    $time_units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
    ];

    foreach ($time_units as $prop => $unit) {
        if ($diff->$prop > 0) {
            $value = $diff->$prop;
            $plural = ($value > 1) ? 's' : '';
            return "$value $unit$plural ago";
        }
    }
    
    return 'Just now';
}


/**
 * Formats a timestamp into a friendly date/time string (Today/Tomorrow/Date).
 * NOTE: The logic for handling an interval string like '12 hours' was removed
 * since `supabase_raw_sql` was updated to return a `scheduled_time` timestamp.
 * @param string|null $datetime_str The timestamp string from the database.
 * @return string A formatted date/time string.
 */
function supabase_format_time(?string $datetime_str): string
{
    try {
        if (empty($datetime_str)) {
            return 'N/A';
        }
        
        $dt = new DateTime($datetime_str);
        
        // Define boundaries for today and tomorrow
        $today = new DateTime('today');
        $tomorrow = new DateTime('tomorrow');
        
        $date_part = $dt->format('Y-m-d');
        
        if ($date_part === $today->format('Y-m-d')) {
            return 'Today, ' . $dt->format('g:i A');
        } elseif ($date_part === $tomorrow->format('Y-m-d')) {
            return 'Tomorrow, ' . $dt->format('g:i A');
        } else {
            // General format for future or past dates
            return $dt->format('M j, g:i A');
        }
    } catch (\Exception $e) {
        return 'Invalid Time'; 
    }
}
?>