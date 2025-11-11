<?php
require_once '../../db.php';
session_start();

// Check if user is admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: ../../login/unified_login.php');
    exit();
}

// Get filter parameters
$userType = $_GET['user_type'] ?? 'all';
$search = $_GET['search'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default to start of month
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get activity statistics
function getActivityStats($pdo, $startDate, $endDate) {
    $stats = [
        'total_logins' => 0,
        'unique_users' => 0,
        'admin_logins' => 0,
        'student_logins' => 0,
        'active_sessions' => 0
    ];

    try {
        // Total logins
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM activity_logs 
            WHERE action = 'login' 
            AND DATE(timestamp) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats['total_logins'] = $stmt->fetchColumn();

        // Unique users
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT user_id) 
            FROM activity_logs 
            WHERE action = 'login' 
            AND DATE(timestamp) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats['unique_users'] = $stmt->fetchColumn();

        // Admin logins
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM activity_logs al
            JOIN users u ON al.user_id = u.user_id
            WHERE al.action = 'login' 
            AND u.role IN ('admin', 'super_admin')
            AND DATE(al.timestamp) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats['admin_logins'] = $stmt->fetchColumn();

        // Student logins
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM activity_logs al
            JOIN users u ON al.user_id = u.user_id
            WHERE al.action = 'login' 
            AND u.role = 'student'
            AND DATE(al.timestamp) BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        $stats['student_logins'] = $stmt->fetchColumn();

        // Active sessions (users logged in within last 30 minutes)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT user_id) 
            FROM activity_logs 
            WHERE action = 'login' 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->execute();
        $stats['active_sessions'] = $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("Error getting activity stats: " . $e->getMessage());
    }

    return $stats;
}

// Get filtered activities
function getFilteredActivities($pdo, $userType, $search, $startDate, $endDate) {
    $params = [];
    $where = [];

    // Base query with joins
    $query = "
        SELECT 
            al.*, 
            u.username, 
            u.role,
            COALESCE(a.position, 'Student') as position,
            (SELECT timestamp FROM activity_logs 
             WHERE user_id = al.user_id AND action = 'logout' 
             AND timestamp > al.timestamp 
             ORDER BY timestamp ASC LIMIT 1) as logout_time
        FROM activity_logs al
        JOIN users u ON al.user_id = u.user_id
        LEFT JOIN admins a ON u.user_id = a.user_id
    ";

    // Add filters
    $where[] = "al.action = 'login' AND DATE(al.timestamp) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;

    if ($userType === 'admin') {
        $where[] = "u.role IN ('admin', 'super_admin')";
    } elseif ($userType === 'student') {
        $where[] = "u.role = 'student'";
    }

    if (!empty($search)) {
        $where[] = "(u.username LIKE ? OR u.email LIKE ? OR a.full_name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Add WHERE clause if there are conditions
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }

    // Add sorting
    $query .= " ORDER BY al.timestamp DESC LIMIT 100";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting filtered activities: " . $e->getMessage());
        return [];
    }
}

// Get stats and activities
$stats = getActivityStats($pdo, $startDate, $endDate);
$activities = getFilteredActivities($pdo, $userType, $search, $startDate, $endDate);

// Format duration
function formatDuration($start, $end) {
    if (!$end) return 'Active';
    $diff = strtotime($end) - strtotime($start);
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    $seconds = $diff % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    } else {
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Report - FeedLoop</title>
    <link rel="stylesheet" href="../../assets/css/homepage/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .filter-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .activity-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .table td {
            vertical-align: middle;
        }
        .badge-admin {
            background-color: #6f42c1;
        }
        .badge-student {
            background-color: #20c997;
        }
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        .status-inactive {
            color: #6c757d;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        .user-role {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line me-2"></i>User Activity Report</h2>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-download me-2"></i>Export Report
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportReport('csv')"><i class="fas fa-file-csv me-2"></i>Export as CSV</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportReport('excel')"><i class="fas fa-file-excel me-2"></i>Export as Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')"><i class="fas fa-file-pdf me-2"></i>Export as PDF</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Report</a></li>
                </ul>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_logins']); ?></div>
                    <div class="stat-label">Total Logins</div>
                    <div class="text-muted small mt-2">
                        <i class="fas fa-arrow-up text-success"></i> 12% from last month
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['unique_users']); ?></div>
                    <div class="stat-label">Unique Users</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['admin_logins']); ?></div>
                    <div class="stat-label">Admin Logins</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['student_logins']); ?></div>
                    <div class="stat-label">Student Logins</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['active_sessions']); ?></div>
                    <div class="stat-label">Active Sessions</div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo min(100, ($stats['active_sessions'] / max(1, $stats['unique_users'])) * 100); ?>%" 
                             aria-valuenow="<?php echo $stats['active_sessions']; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="<?php echo max(1, $stats['unique_users']); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card mb-4">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">User Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="user_type" id="allUsers" value="all" 
                                <?php echo $userType === 'all' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="allUsers">All</label>
                            
                            <input type="radio" class="btn-check" name="user_type" id="adminsOnly" value="admin" 
                                <?php echo $userType === 'admin' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="adminsOnly">Admins</label>
                            
                            <input type="radio" class="btn-check" name="user_type" id="studentsOnly" value="student" 
                                <?php echo $userType === 'student' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="studentsOnly">Students</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search User</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or username">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Activity Table -->
        <div class="activity-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Type</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Duration</th>
                            <th>IP Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activities)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                                    <p class="mb-0">No activity records found for the selected filters.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): 
                                $isActive = empty($activity['logout_time']);
                                $loginTime = new DateTime($activity['timestamp']);
                                $logoutTime = $activity['logout_time'] ? new DateTime($activity['logout_time']) : null;
                                $duration = formatDuration($activity['timestamp'], $activity['logout_time']);
                                $userTypeClass = in_array($activity['role'], ['admin', 'super_admin']) ? 'admin' : 'student';
                            ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="user-name"><?php echo htmlspecialchars($activity['username']); ?></div>
                                                <div class="user-role"><?php echo ucfirst($userTypeClass); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $userTypeClass === 'admin' ? 'primary' : 'success'; ?>">
                                            <?php echo ucfirst($userTypeClass); ?>
                                        </span>
                                        <?php if ($activity['role'] === 'super_admin'): ?>
                                            <span class="badge bg-warning text-dark">Super Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $loginTime->format('M d, Y h:i A'); ?></td>
                                    <td><?php echo $logoutTime ? $logoutTime->format('M d, Y h:i A') : 'Active'; ?></td>
                                    <td><?php echo $duration; ?></td>
                                    <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                    <td>
                                        <span class="status-<?php echo $isActive ? 'active' : 'inactive'; ?>">
                                            <i class="fas fa-circle me-1"></i>
                                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        // Function to export report in different formats
        function exportReport(format) {
            const data = [
                ['User', 'Type', 'Login Time', 'Logout Time', 'Duration', 'IP Address', 'Status']
            ];
            
            // Get table data
            document.querySelectorAll('table tbody tr').forEach(row => {
                if (row.cells.length > 1) { // Skip empty rows
                    const user = row.cells[0].querySelector('.user-name')?.textContent.trim() || '';
                    const type = row.cells[1].textContent.trim();
                    const loginTime = row.cells[2].textContent.trim();
                    const logoutTime = row.cells[3].textContent.trim();
                    const duration = row.cells[4].textContent.trim();
                    const ip = row.cells[5].textContent.trim();
                    const status = row.cells[6].textContent.trim();
                    
                    data.push([user, type, loginTime, logoutTime, duration, ip, status]);
                }
            });

            if (format === 'csv') {
                exportToCSV(data, 'user_activity_report');
            } else if (format === 'excel') {
                exportToExcel(data, 'user_activity_report');
            } else if (format === 'pdf') {
                exportToPDF(data, 'User Activity Report');
            }
        }

        function exportToCSV(data, filename) {
            let csvContent = "data:text/csv;charset=utf-8,";
            data.forEach(row => {
                csvContent += row.join(",") + "\r\n";
            });
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `${filename}_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function exportToExcel(data, filename) {
            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "User Activity");
            XLSX.writeFile(wb, `${filename}_${new Date().toISOString().split('T')[0]}.xlsx`);
        }

        async function exportToPDF(data, title) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });

            // Add logo (you'll need to replace this with your actual logo path)
            try {
                const logoUrl = '../../assets/img/logo/feedloop.jpg';
                const logoResponse = await fetch(logoUrl);
                const logoBlob = await logoResponse.blob();
                const logoDataUrl = await new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.readAsDataURL(logoBlob);
                });
                
                // Add logo to header
                doc.addImage(logoDataUrl, 'JPEG', 10, 5, 30, 15);
            } catch (e) {
                console.error('Error loading logo:', e);
            }

            // Add title and metadata
            doc.setFontSize(20);
            doc.setTextColor(40, 62, 80);
            doc.setFont('helvetica', 'bold');
            doc.text('FeedLoop - User Activity Report', doc.internal.pageSize.getWidth() / 2, 15, { align: 'center' });
            
            // Add date and time
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.setFont('helvetica', 'normal');
            const dateStr = new Date().toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            doc.text(`Generated on: ${dateStr}`, doc.internal.pageSize.getWidth() - 10, 10, { align: 'right' });

            // Add summary section
            doc.setFontSize(12);
            doc.setTextColor(40, 62, 80);
            doc.text('Report Summary', 15, 35);
            
            // Add summary boxes
            doc.setFillColor(234, 246, 255);
            doc.roundedRect(15, 40, 50, 20, 3, 3, 'F');
            doc.setFontSize(10);
            doc.text('TOTAL LOGINS', 25, 47);
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text(document.querySelector('.stat-value').textContent, 25, 55);
            
            doc.setFillColor(234, 255, 238);
            doc.roundedRect(70, 40, 50, 20, 3, 3, 'F');
            doc.setFontSize(10);
            doc.text('ACTIVE USERS', 80, 47);
            doc.setFontSize(16);
            doc.text(document.querySelectorAll('.stat-value')[4].textContent, 80, 55);
            
            // Add charts
            const chartCanvas = document.createElement('canvas');
            chartCanvas.width = 400;
            chartCanvas.height = 200;
            const ctx = chartCanvas.getContext('2d');
            
            // Create a simple bar chart
            const chartData = {
                labels: ['Admins', 'Students', 'Total Logins', 'Active Now'],
                datasets: [{
                    label: 'Activity Summary',
                    data: [
                        document.querySelectorAll('.stat-value')[2].textContent.replace(/,/g, ''),
                        document.querySelectorAll('.stat-value')[3].textContent.replace(/,/g, ''),
                        document.querySelector('.stat-value').textContent.replace(/,/g, ''),
                        document.querySelectorAll('.stat-value')[4].textContent.replace(/,/g, '')
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            };
            
            new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Activity Overview',
                            font: {
                                size: 14
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Convert chart to image and add to PDF
            const chartImage = chartCanvas.toDataURL('image/png');
            doc.addImage(chartImage, 'PNG', 15, 70, 180, 90);
            
            // Add detailed activity table
            doc.setFontSize(14);
            doc.setTextColor(40, 62, 80);
            doc.setFont('helvetica', 'bold');
            doc.text('Detailed Activity Log', 15, 170);
            
            // Add table
            doc.autoTable({
                head: [data[0]],
                body: data.slice(1, 10), // Limit to first 10 rows for demo
                startY: 175,
                theme: 'grid',
                headStyles: {
                    fillColor: [41, 128, 185],
                    textColor: 255,
                    fontStyle: 'bold',
                    fontSize: 8
                },
                bodyStyles: {
                    fontSize: 7,
                    cellPadding: 1.5,
                    overflow: 'linebreak',
                    lineWidth: 0.1,
                    textColor: [60, 60, 60]
                },
                alternateRowStyles: {
                    fillColor: [248, 250, 252]
                },
                margin: { top: 10 },
                styles: {
                    cellWidth: 'wrap',
                    halign: 'left',
                    valign: 'middle'
                },
                columnStyles: {
                    0: { cellWidth: 30 },
                    1: { cellWidth: 20 },
                    2: { cellWidth: 25 },
                    3: { cellWidth: 25 },
                    4: { cellWidth: 15 },
                    5: { cellWidth: 20 },
                    6: { cellWidth: 15 }
                },
                didDrawPage: function(data) {
                    // Footer
                    const pageCount = doc.internal.getNumberOfPages();
                    doc.setFontSize(8);
                    doc.setTextColor(100);
                    doc.text(
                        `Page ${data.pageNumber} of ${pageCount}`, 
                        doc.internal.pageSize.getWidth() / 2,
                        doc.internal.pageSize.getHeight() - 5,
                        { align: 'center' }
                    );
                    
                    // Add watermark
                    doc.setFontSize(60);
                    doc.setTextColor(230, 230, 230);
                    doc.setGradient(0, 0, 0, 0, doc.internal.pageSize.width, doc.internal.pageSize.height, [255, 255, 255], [240, 240, 240]);
                    doc.text(
                        'CONFIDENTIAL',
                        doc.internal.pageSize.getWidth() / 2,
                        doc.internal.pageSize.getHeight() / 2,
                        { angle: 45, align: 'center', opacity: 0.1 }
                    );
                }
            });
            
            // Add footer on each page
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(150);
                doc.text(
                    '© ' + new Date().getFullYear() + ' FeedLoop - All Rights Reserved',
                    doc.internal.pageSize.getWidth() - 10,
                    doc.internal.pageSize.getHeight() - 5,
                    { align: 'right' }
                );
            }
            
            // Generate PDF and open in new tab
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-')
            const filename = `Feedloop_Admin_Report_${timestamp}.pdf`
            
            // Create a blob URL for the PDF
            const blob = doc.output('blob')
            const blobUrl = URL.createObjectURL(blob)
            
            // Create a new window with minimal content
            const newWindow = window.open('about:blank', '_blank')
            
            // Write a simple HTML page that auto-downloads the PDF
            newWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Opening Report...</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            display: flex; 
                            justify-content: center; 
                            align-items: center; 
                            height: 100vh; 
                            margin: 0; 
                            background: #f5f5f5;
                            text-align: center;
                            padding: 20px;
                        }
                        .message {
                            background: white;
                            padding: 30px;
                            border-radius: 8px;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                            max-width: 500px;
                        }
                    </style>
                </head>
                <body>
                    <div class="message">
                        <h2>Your report is ready</h2>
                        <p>If the PDF doesn't open automatically, <a href="${blobUrl}" download="${filename}">click here to download</a>.</p>
                    </div>
                    <script>
                        // Try to open PDF in the same tab
                        window.location.href = '${blobUrl}';
                        
                        // Also provide a download link as fallback
                        const link = document.createElement('a');
                        link.href = '${blobUrl}';
                        link.download = '${filename}';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    </script>
                </body>
                </html>
            `);
        }
        // Auto-refresh the page every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 5 * 60 * 1000);
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
