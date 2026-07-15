<?php
/**
 * Teacher Data Export Controller Action
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Auth Guard
Auth::requireRole('admin');

$db = Database::connect();

// Filters from query params
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

// Fetch all records matching criteria without limit
$teachers = Teacher::getFilteredList($search, $status, 't.full_name', 'ASC', 100000, 0);

$action = $_GET['action'] ?? 'csv';

if ($action === 'csv') {
    $filename = 'Teacher_List_' . date('Ymd_His');
    $headers = ['Employee ID', 'Full Name', 'Gender', 'Phone', 'Email', 'Qualification', 'Salary', 'Date Joined', 'Status'];
    
    $data = [];
    foreach ($teachers as $t) {
        $data[] = [
            $t['employee_id'],
            $t['full_name'],
            ucfirst($t['gender']),
            $t['phone'],
            $t['email'],
            $t['qualification'],
            $t['salary'],
            $t['date_joined'],
            ucfirst($t['status'])
        ];
    }
    
    ExportHelper::toCSV($filename, $headers, $data);
    
} elseif ($action === 'print') {
    $title = 'Teacher Directory Report';
    $headers = ['Employee ID', 'Full Name', 'Gender', 'Qualification', 'Phone', 'Email', 'Date Joined', 'Status'];
    
    $data = [];
    foreach ($teachers as $t) {
        $data[] = [
            $t['employee_id'],
            $t['full_name'],
            ucfirst($t['gender']),
            $t['qualification'],
            $t['phone'],
            $t['email'],
            $t['date_joined'],
            ucfirst($t['status'])
        ];
    }
    
    ExportHelper::renderPrintLayout($title, $headers, $data);
}
