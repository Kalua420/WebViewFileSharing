<?php
session_start();
require_once 'db_connection.php';

// Check if parameters are present
if (!isset($_POST['filename'])) {
    die("Error: No filename provided");
}

// Get search parameters
$filename = $_POST['filename'];
$date_from = isset($_POST['date_from']) ? $_POST['date_from'] : '';
$date_to = isset($_POST['date_to']) ? $_POST['date_to'] : '';

// Build search query
$searchConditions = [];
$searchParams = [];

if (!empty($filename)) {
    $searchConditions[] = "l.filename LIKE ?";
    $searchParams[] = "%" . $filename . "%";
}

if (!empty($date_from)) {
    $searchConditions[] = "DATE(l.timestamp) >= ?";
    $searchParams[] = $date_from;
}

if (!empty($date_to)) {
    $searchConditions[] = "DATE(l.timestamp) <= ?";
    $searchParams[] = $date_to;
}

// Build the query
$sql = "SELECT l.*, 
         s.id as sender_id, s.email as sender_email, s.phone as sender_phone,
         r.id as receiver_id, r.email as receiver_email, r.phone as receiver_phone,
         b.branch_name as branch_name
         FROM logs l
         LEFT JOIN users s ON l.sender_id = s.id
         LEFT JOIN users r ON l.receiver_id = r.id
         LEFT JOIN branch b ON s.bid = b.id";

if (!empty($searchConditions)) {
    $sql .= " WHERE " . implode(" AND ", $searchConditions);
}

$sql .= " ORDER BY l.timestamp ASC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);

if (!empty($searchParams)) {
    $types = str_repeat("s", count($searchParams));
    $stmt->bind_param($types, ...$searchParams);
}

$stmt->execute();
$result = $stmt->get_result();

// Generate HTML Report
generateHTMLReport($result, $filename, $date_from, $date_to);

/**
 * Generate an HTML report
 */
function generateHTMLReport($result, $filename, $date_from, $date_to) {
    // Set headers for browser display
    header('Content-Type: text/html');
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>File Sharing Report: ' . htmlspecialchars($filename) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            .report-header { margin-bottom: 20px; }
            .report-info { margin-bottom: 15px; border-left: 4px solid #2196F3; padding-left: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
            tr:hover { background-color: #f5f5f5; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
            .sender-cell { color: #4CAF50; }
            .receiver-cell { color: #2196F3; }
            .mac-address { font-family: monospace; color: #555; }
            .btn-container { margin: 20px 0; }
            .btn { padding: 8px 16px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; }
            .btn-print { background-color: #2196F3; }
            .btn:hover { opacity: 0.9; }
            .timestamp-col { white-space: nowrap; }
            .summary-box { background-color: #f9f9f9; border-left: 4px solid #4CAF50; padding: 10px; margin-bottom: 20px; }
            @media print {
                .no-print { display: none; }
                body { margin: 0; padding: 15px; }
                .summary-box { break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="report-header">
            <h1>File Sharing Activity Report</h1>
            <div class="report-info">
                <p><strong>Filename:</strong> ' . htmlspecialchars($filename) . '</p>';
    
    if (!empty($date_from) || !empty($date_to)) {
        echo '<p><strong>Period:</strong> ';
        if (!empty($date_from)) echo 'From ' . htmlspecialchars($date_from) . ' ';
        if (!empty($date_to)) echo 'To ' . htmlspecialchars($date_to);
        echo '</p>';
    }
    
    echo '<p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>
            </div>
        </div>
        
        <div class="no-print btn-container">
            <button onclick="window.print()" class="btn btn-print">Print Report</button>
            <button onclick="window.close()" class="btn">Close</button>
            <button onclick="exportTableToCSV(\'file_sharing_report.csv\')" class="btn">Export to CSV</button>
        </div>';
    
    // Count total records
    $recordCount = $result->num_rows;
    
    if ($recordCount > 0) {
        // Get first and last dates
        $result->data_seek(0);
        $firstRow = $result->fetch_assoc();
        $firstDate = date('Y-m-d H:i:s', strtotime($firstRow['timestamp']));
        
        $result->data_seek($recordCount - 1);
        $lastRow = $result->fetch_assoc();
        $lastDate = date('Y-m-d H:i:s', strtotime($lastRow['timestamp']));
        
        // Reset result pointer
        $result->data_seek(0);
        
        // Display summary
        echo '<div class="summary-box">
                <h3>Report Summary</h3>
                <p><strong>Total file transfers:</strong> ' . $recordCount . '</p>
                <p><strong>First transfer:</strong> ' . $firstDate . '</p>
                <p><strong>Last transfer:</strong> ' . $lastDate . '</p>
              </div>';
        
        echo '<table id="reportTable">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Sender</th>
                        <th>Receiver</th>
                        <th>Branch</th>
                        <th>Filename</th>
                        <th>Source MAC</th>
                        <th>Dest MAC</th>
                    </tr>
                </thead>
                <tbody>';
        
        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td class="timestamp-col">' . $row['timestamp'] . '</td>
                    <td class="sender-cell">' . $row['sender_id'] . ' (' . $row['sender_email'] . ')<br>' . $row['sender_phone'] . '</td>
                    <td class="receiver-cell">' . $row['receiver_id'] . ' (' . $row['receiver_email'] . ')<br>' . $row['receiver_phone'] . '</td>
                    <td>' . $row['branch_name'] . '</td>
                    <td>' . $row['filename'] . '</td>
                    <td class="mac-address">' . $row['source_mac'] . '</td>
                    <td class="mac-address">' . $row['destination_mac'] . '</td>
                </tr>';
        }
        
        echo '</tbody>
            </table>';
    } else {
        echo '<div class="summary-box">
                <h3>No Records Found</h3>
                <p>No records found matching the search criteria.</p>
              </div>';
    }
    
    echo '<div class="footer">
            <p>This report is confidential and for internal use only.</p>
            <p>Generated from the File Sharing System on ' . date('Y-m-d') . '</p>
        </div>
        
        <script>
        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("table tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                
                for (var j = 0; j < cols.length; j++) {
                    // Replace HTML breaks with spaces and clean up text
                    var text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ");
                    // Escape double quotes
                    text = text.replace(/"/g, \'"""\');
                    // Add text
                    row.push(\'"\' + text + \'"\');
                }
                
                csv.push(row.join(","));
            }
            
            // Download CSV file
            downloadCSV(csv.join("\\n"), filename);
        }
        
        function downloadCSV(csv, filename) {
            var csvFile;
            var downloadLink;
            
            // Create CSV file
            csvFile = new Blob([csv], {type: "text/csv"});
            
            // Create download link
            downloadLink = document.createElement("a");
            
            // Set file name
            downloadLink.download = filename;
            
            // Create link to file
            downloadLink.href = window.URL.createObjectURL(csvFile);
            
            // Hide download link
            downloadLink.style.display = "none";
            
            // Add link to DOM
            document.body.appendChild(downloadLink);
            
            // Click download link
            downloadLink.click();
        }
        </script>
    </body>
    </html>';
}
?>