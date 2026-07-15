<?php
/**
 * Student Data Export Controller
 * Actions: excel, csv, pdf (print)
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole('admin');

$db = Database::connect();

// Filters from query params
$search  = trim($_GET['search']   ?? '');
$classId = trim($_GET['class_id'] ?? '');
$status  = trim($_GET['status']   ?? '');
$action  = $_GET['action']        ?? 'excel';

// Fetch all matching records (no pagination limit)
$students = Student::getFilteredList($search, $classId, $status, 's.first_name', 'ASC', 100000, 0);

$schoolName = Setting::get('school_name', 'School');
$filename   = 'Student_List_' . date('Ymd_His');
$subtitle   = 'Student Directory';
if ($search)  $subtitle .= ' | Search: ' . $search;
if ($classId) $subtitle .= ' | Class filtered';
if ($status)  $subtitle .= ' | Status: ' . ucfirst($status);

// Full columns for Excel / CSV
$fullHeaders = [
    'Admission No', 'Student Name', 'ID Card No', 'Gender', 'Date of Birth',
    'Phone', 'Email', 'Parent Name', 'Parent Phone',
    'Class', 'Section', 'Roll No', 'Academic Year', 'Status'
];
$fullData = [];
foreach ($students as $s) {
    $fullData[] = [
        $s['admission_number'],
        $s['first_name'] . ' ' . $s['last_name'],
        $s['student_id_card'] ?? '',
        ucfirst($s['gender'] ?? ''),
        $s['date_of_birth'] ?? '',
        $s['phone'] ?? '',
        $s['email'] ?? '',
        $s['parent_name'] ?? '',
        $s['parent_phone'] ?? '',
        ($s['class_name'] ?? '') . ' - ' . ($s['section'] ?? ''),
        $s['section'] ?? '',
        $s['roll_number'] ?? '',
        $s['academic_year'] ?? '',
        ucfirst($s['status'] ?? ''),
    ];
}

// Condensed columns for PDF (fits on A4)
$pdfHeaders = ['Admission No', 'Student Name', 'Gender', 'Class', 'Roll No', 'Parent Name', 'Parent Phone', 'Year', 'Status'];
$pdfData = [];
foreach ($students as $s) {
    $pdfData[] = [
        $s['admission_number'],
        $s['first_name'] . ' ' . $s['last_name'],
        ucfirst($s['gender'] ?? ''),
        ($s['class_name'] ?? '') . ' - ' . ($s['section'] ?? ''),
        $s['roll_number'] ?? '',
        $s['parent_name'] ?? '',
        $s['parent_phone'] ?? '',
        $s['academic_year'] ?? '',
        ucfirst($s['status'] ?? ''),
    ];
}

if ($action === 'excel') {
    ExportHelper::toExcel($filename, $fullHeaders, $fullData, 'Student Directory');

} elseif ($action === 'csv') {
    ExportHelper::toCSV($filename, $fullHeaders, $fullData);

} elseif ($action === 'pdf') {
    ExportHelper::renderPrintLayout('Student Directory Report', $pdfHeaders, $pdfData, $subtitle);

} elseif ($action === 'print') {
    // Legacy alias
    ExportHelper::renderPrintLayout('Student Directory Report', $pdfHeaders, $pdfData, $subtitle);
}
