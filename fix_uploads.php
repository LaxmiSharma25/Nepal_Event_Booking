<?php
// Script to check and create upload directories with proper permissions
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define required directories
$directories = [
    'assets/images',
    'assets/images/services',
    'uploads',
    'uploads/profile_pictures',
    'uploads/services',
    'uploads/events'
];

$results = [];

// Function to check and create directory
function check_directory($dir) {
    $baseDir = __DIR__;
    $fullPath = $baseDir . '/' . $dir;
    
    $result = [
        'directory' => $dir,
        'full_path' => $fullPath,
        'status' => 'OK',
        'message' => 'Directory exists and is writable',
        'action_taken' => 'None'
    ];
    
    // Check if directory exists
    if (!file_exists($fullPath)) {
        $result['status'] = 'FIXED';
        $result['message'] = 'Directory did not exist';
        
        // Create directory
        if (mkdir($fullPath, 0755, true)) {
            $result['action_taken'] = 'Created directory';
        } else {
            $result['status'] = 'ERROR';
            $result['action_taken'] = 'Failed to create directory';
        }
    } 
    // Check if directory is writable
    elseif (!is_writable($fullPath)) {
        $result['status'] = 'FIXED';
        $result['message'] = 'Directory was not writable';
        
        // Make directory writable
        if (chmod($fullPath, 0755)) {
            $result['action_taken'] = 'Fixed permissions';
        } else {
            $result['status'] = 'ERROR';
            $result['action_taken'] = 'Failed to fix permissions';
        }
    }
    
    return $result;
}

// Check each directory
foreach ($directories as $dir) {
    $results[] = check_directory($dir);
}

// Create a test file in each directory to verify write access
foreach ($results as &$result) {
    if ($result['status'] !== 'ERROR') {
        $testFile = $result['full_path'] . '/test_write.txt';
        $writeSuccess = file_put_contents($testFile, 'Test write access');
        
        if ($writeSuccess) {
            $result['write_test'] = 'PASSED';
            // Clean up test file
            unlink($testFile);
        } else {
            $result['write_test'] = 'FAILED';
            $result['status'] = 'ERROR';
            $result['message'] .= '. Cannot write to directory.';
        }
    } else {
        $result['write_test'] = 'SKIPPED';
    }
}

// Output results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Upload Directories - Nepali Event Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #1d3557;
            margin-bottom: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .status-ok {
            color: green;
            font-weight: bold;
        }
        .status-fixed {
            color: blue;
            font-weight: bold;
        }
        .status-error {
            color: red;
            font-weight: bold;
        }
        .test-passed {
            color: green;
        }
        .test-failed {
            color: red;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #1d3557;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background-color: #2a4d6e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Directories Status</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Directory</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Action Taken</th>
                    <th>Write Test</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                <tr>
                    <td><?php echo htmlspecialchars($result['directory']); ?></td>
                    <td class="status-<?php echo strtolower($result['status']); ?>"><?php echo $result['status']; ?></td>
                    <td><?php echo htmlspecialchars($result['message']); ?></td>
                    <td><?php echo htmlspecialchars($result['action_taken']); ?></td>
                    <td class="test-<?php echo strtolower($result['write_test']); ?>"><?php echo $result['write_test']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>What to do next?</h2>
        <p>If all directories show "OK" or "FIXED" status and "PASSED" write tests, your upload functionality should work now.</p>
        <p>Try uploading images in the admin panel again.</p>
        
        <a href="admin/dashboard.php" class="back-link">Return to Admin Dashboard</a>
    </div>
</body>
</html> 