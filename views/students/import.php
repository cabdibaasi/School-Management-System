<?php
/**
 * Import Students via CSV / Excel
 */
$pageTitle = "Import Students";
require_once dirname(dirname(__DIR__)) . '/config/config.php';

Auth::requireRole('admin');

$db = Database::connect();
$successMsg = '';
$errorMsg = '';
$importResults = null;

// Download CSV Template Action
if (isset($_GET['template'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="student_import_template.csv"');
    
    $output = fopen('php://output', 'w');
    // Header columns
    fputcsv($output, [
        'ID',
        'Name of Students',
        'Parent\'s Name',
        'Parent Number',
        'Class',
        'Course Type',
        'Fee',
        'Date Register',
        'Date Out',
        'Status'
    ]);
    // Sample rows
    fputcsv($output, [
        '1294', 'Fartuun Hussein Mohamed', 'Fartuun Hussein', '615240687', 'N - S.X 002 ((P))', 'Somaali & Xisaab', '$6.00', '04/10/2025', '', 'Present'
    ]);
    fputcsv($output, [
        '1410', 'Abdalla Abdiqani Ise', 'Abdiqani Ise', '615550555', 'N - S.X 002 ((P))', 'Somaali & Xisaab', '$6.00', '02/12/2025', '', 'Present'
    ]);
    fclose($output);
    exit;
}

// Handle Import Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Utility::validateCSRFToken($token)) {
        $errorMsg = 'Invalid request security token.';
    } else {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'Please select a valid CSV file to upload.';
        } else {
            $fileTmpPath = $_FILES['csv_file']['tmp_name'];
            $file = fopen($fileTmpPath, 'r');
            
            // Read headers
            $headers = fgetcsv($file);
            
            // Detect layout type
            $isCustomExcel = false;
            foreach ($headers as $h) {
                if (stripos(trim($h), 'name of students') !== false) {
                    $isCustomExcel = true;
                    break;
                }
            }
            
            // Initialize dynamic indexes
            $idxId          = 0;
            $idxName        = 1;
            $idxParentName  = -1;
            $idxParentPhone = -1;
            $idxClass       = -1;
            $idxCourse      = -1;
            $idxFee         = -1;
            $idxDateReg     = -1;
            $idxStatus      = -1;
            
            if ($isCustomExcel) {
                foreach ($headers as $i => $h) {
                    $cleaned = strtolower(trim($h));
                    if ($cleaned === 'id') $idxId = $i;
                    elseif (stripos($cleaned, 'name of students') !== false) $idxName = $i;
                    elseif (stripos($cleaned, 'parent') !== false && stripos($cleaned, 'name') !== false) $idxParentName = $i;
                    elseif (stripos($cleaned, 'parent') !== false && (stripos($cleaned, 'number') !== false || stripos($cleaned, 'phone') !== false)) $idxParentPhone = $i;
                    elseif ($cleaned === 'class') $idxClass = $i;
                    elseif (stripos($cleaned, 'course') !== false) $idxCourse = $i;
                    elseif (stripos($cleaned, 'fee') !== false) $idxFee = $i;
                    elseif (stripos($cleaned, 'date') !== false && (stripos($cleaned, 'register') !== false || stripos($cleaned, 'reg') !== false)) $idxDateReg = $i;
                    elseif ($cleaned === 'status') $idxStatus = $i;
                }
            }
            
            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            $line = 1; // Line 1 is headers
            
            while (($row = fgetcsv($file)) !== false) {
                $line++;
                
                // Skip empty rows
                if (empty($row) || count(array_filter($row)) === 0) {
                    continue;
                }
                
                // Map columns dynamically
                if ($isCustomExcel) {
                    $admNum      = trim($row[$idxId] ?? '');
                    $fullName    = trim($row[$idxName] ?? '');
                    
                    // Split fullName into first/last name
                    $parts       = explode(' ', $fullName);
                    $firstName   = $parts[0] ?? '';
                    $lastName    = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '.';
                    
                    $parentName  = $idxParentName !== -1 ? trim($row[$idxParentName] ?? '') : '';
                    $parentPhone = $idxParentPhone !== -1 ? trim($row[$idxParentPhone] ?? '') : '';
                    $classCombined = $idxClass !== -1 ? trim($row[$idxClass] ?? '') : '';
                    
                    $className   = !empty($classCombined) ? $classCombined : 'Unassigned';
                    $section     = 'A';
                    $acadYear    = date('Y') . '-' . (date('Y') + 1);
                    
                    $courseType  = $idxCourse !== -1 ? trim($row[$idxCourse] ?? '') : '';
                    $feeAmount   = $idxFee !== -1 ? trim($row[$idxFee] ?? '') : '0.00';
                    $dateReg     = $idxDateReg !== -1 ? trim($row[$idxDateReg] ?? '') : '';
                    $statusVal   = $idxStatus !== -1 ? strtolower(trim($row[$idxStatus] ?? '')) : 'present';
                    
                    $gender      = 'male';
                    $dob         = !empty($dateReg) ? date('Y-m-d', strtotime($dateReg)) : null;
                    $email       = '';
                    $phone       = $parentPhone;
                    $address     = !empty($courseType) ? 'Course Type: ' . $courseType : '';
                    $rollNum     = '';
                    $status      = ($statusVal === 'present' || $statusVal === 'active') ? 'active' : 'inactive';
                } else {
                    $admNum      = trim($row[0] ?? '');
                    $firstName   = trim($row[1] ?? '');
                    $lastName    = trim($row[2] ?? '');
                    $gender      = strtolower(trim($row[3] ?? ''));
                    $dob         = trim($row[4] ?? '');
                    $email       = trim($row[5] ?? '');
                    $phone       = trim($row[6] ?? '');
                    $address     = trim($row[7] ?? '');
                    $parentName  = trim($row[8] ?? '');
                    $parentPhone = trim($row[9] ?? '');
                    $className   = trim($row[10] ?? '');
                    $section     = trim($row[11] ?? '');
                    $acadYear    = trim($row[12] ?? '');
                    $rollNum     = trim($row[13] ?? '');
                    $status      = 'active';
                    $feeAmount   = '0.00';
                }
                
                // Basic validations
                if (empty($admNum) || empty($firstName) || empty($lastName) || empty($className) || empty($section) || empty($acadYear)) {
                    $failedCount++;
                    $errors[] = "Line {$line}: Missing required fields (Admission Number, First Name, Last Name, Class Name, Section, or Academic Year).";
                    continue;
                }
                
                // Validate Gender value
                if (!in_array($gender, ['male', 'female', 'other'])) {
                    $gender = 'male'; // fallback default
                }
                
                // Check if student record (admission number) already exists
                $stmt = $db->prepare("SELECT id FROM students WHERE admission_number = :adm");
                $stmt->execute(['adm' => $admNum]);
                if ($stmt->fetch()) {
                    $failedCount++;
                    $errors[] = "Line {$line}: Student with Admission Number '{$admNum}' already exists.";
                    continue;
                }
                
                // Check if user username (admission number) already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
                $stmt->execute(['username' => $admNum]);
                if ($stmt->fetch()) {
                    $failedCount++;
                    $errors[] = "Line {$line}: A user account with username '{$admNum}' already exists.";
                    continue;
                }
                
                // If email is provided, verify it is unique
                if (!empty($email)) {
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                    $stmt->execute(['email' => $email]);
                    if ($stmt->fetch()) {
                        $failedCount++;
                        $errors[] = "Line {$line}: A user account with email '{$email}' already exists.";
                        continue;
                    }
                } else {
                    // Generate fallback email
                    $email = strtolower($admNum) . '@school.com';
                }
                
                // Find or Create Class
                $classId = null;
                $stmt = $db->prepare("SELECT id FROM classes WHERE class_name = :class_name AND section = :section AND academic_year = :year LIMIT 1");
                $stmt->execute(['class_name' => $className, 'section' => $section, 'year' => $acadYear]);
                $cls = $stmt->fetch();
                
                if ($cls) {
                    $classId = $cls['id'];
                } else {
                    // Create Class automatically
                    if (SchoolClass::create($className, $section, $acadYear)) {
                        $classId = $db->lastInsertId();
                    } else {
                        $failedCount++;
                        $errors[] = "Line {$line}: Class '{$className} - {$section}' could not be matched or automatically created.";
                        continue;
                    }
                }
                
                // Prepare transaction elements
                $userData = [
                    'username' => $admNum,
                    'email' => $email,
                    'password' => password_hash($admNum, PASSWORD_BCRYPT), // Default password is the admission number
                    'status' => 'active'
                ];
                
                $studentData = [
                    'admission_number' => $admNum,
                    'student_id_card' => $admNum,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => $gender,
                    'date_of_birth' => !empty($dob) ? $dob : null,
                    'nationality' => 'Citizen',
                    'blood_group' => null,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'parent_name' => $parentName,
                    'parent_phone' => $parentPhone,
                    'parent_email' => null,
                    'class_id' => $classId,
                    'roll_number' => $rollNum,
                    'academic_year' => $acadYear,
                    'status' => 'active',
                    'photo' => null
                ];
                
                // Create student
                if (Student::create($userData, $studentData)) {
                    $successCount++;
                    
                    // Auto-generate invoice if a fee amount is provided in the Excel sheet
                    if ($isCustomExcel) {
                        $cleanFee = (float)preg_replace('/[^0-9.]/', '', $feeAmount);
                        if ($cleanFee > 0) {
                            // Retrieve the user ID we just created
                            $stmtUser = $db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
                            $stmtUser->execute(['username' => $admNum]);
                            $u = $stmtUser->fetch();
                            if ($u) {
                                // Retrieve the student ID associated with that user ID
                                $stmtStd = $db->prepare("SELECT id FROM students WHERE user_id = :user_id LIMIT 1");
                                $stmtStd->execute(['user_id' => $u['id']]);
                                $std = $stmtStd->fetch();
                                if ($std) {
                                    $studentId = $std['id'];
                                    $feeType = !empty($courseType) ? $courseType : 'Tuition Fee';
                                    $dueDate = !empty($dateReg) ? date('Y-m-d', strtotime($dateReg)) : date('Y-m-d');
                                    Fee::create($studentId, $feeType, $cleanFee, $dueDate, $acadYear);
                                }
                            }
                        }
                    }
                } else {
                    $failedCount++;
                    $errors[] = "Line {$line}: Database error occurred while saving student record.";
                }
            }
            
            fclose($file);
            $importResults = [
                'success' => $successCount,
                'failed' => $failedCount,
                'errors' => $errors
            ];
            
            if ($successCount > 0) {
                Utility::setFlash('success', "Import completed: {$successCount} student(s) imported successfully.");
            }
        }
    }
}

$csrfToken = Utility::generateCSRFToken();
require_once ROOT_PATH . 'views/layouts/header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/students/index.php">Students</a></li>
        <li class="breadcrumb-item active" aria-current="page">Import</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8 col-md-12">
        <div class="card section-card">
            <div class="card-header bg-white border-0 py-3">
                <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-file-import me-2"></i>Import Students from Excel / CSV</h4>
                <p class="text-muted small mb-0">Upload a spreadsheet formatted as a CSV file to add multiple students and create their user accounts in bulk.</p>
            </div>
            <div class="card-body">
                
                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= e($errorMsg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="mb-4">
                        <label for="csv_file" class="form-label fw-bold">Select CSV spreadsheet file</label>
                        <input class="form-control form-control-custom" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <div class="form-text mt-2 text-muted">
                            <i class="fas fa-info-circle me-1"></i> Make sure to select a <code>.csv</code> file. If you have an Excel sheet (<code>.xlsx</code>), save it as a <strong>CSV (Comma Delimited)</strong> before uploading.
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-primary-custom px-4 text-white">
                            <i class="fas fa-upload me-2"></i> Upload & Import
                        </button>
                        <a href="?template=1" class="btn btn-outline-success px-4">
                            <i class="fas fa-file-download me-2"></i> Download Template
                        </a>
                    </div>
                </form>

                <!-- Instruction Checklist -->
                <div class="bg-light p-3 rounded-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-list-check me-2 text-primary"></i>Spreadsheet Format Rules</h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-1"><strong>Required columns:</strong> Admission Number, First Name, Last Name, Class Name, Section, and Academic Year.</li>
                        <li class="mb-1"><strong>Auto Class Matching:</strong> If the Class & Section name does not exist, the system will automatically create it.</li>
                        <li class="mb-1"><strong>Auto-Created Login Accounts:</strong> Every imported student will automatically have a login credential created. The <strong>Username</strong> and <strong>Password</strong> will both default to their <strong>Admission Number</strong>.</li>
                        <li class="mb-1">If no email is provided, a default email will be generated (e.g. <code>admission_number@school.com</code>).</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
    
    <!-- Sidebar statistics & Import results -->
    <div class="col-lg-4 col-md-12 mt-4 mt-lg-0">
        <?php if ($importResults !== null): ?>
            <div class="card section-card border border-light">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-pie me-2 text-primary"></i>Import Results</h5>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Successfully Imported:</span>
                        <span class="badge bg-success rounded-pill px-3"><?= $importResults['success'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Skipped / Failed:</span>
                        <span class="badge bg-danger rounded-pill px-3"><?= $importResults['failed'] ?></span>
                    </div>
                    
                    <?php if (!empty($importResults['errors'])): ?>
                        <hr>
                        <h6 class="fw-bold text-danger mb-2 small">Import Warnings & Errors:</h6>
                        <div class="overflow-y-auto small" style="max-height: 250px;">
                            <?php foreach ($importResults['errors'] as $err): ?>
                                <div class="p-2 mb-2 bg-danger-subtle text-danger-emphasis rounded-3 border border-danger-subtle">
                                    <i class="fas fa-exclamation-triangle me-1"></i> <?= e($err) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card section-card bg-primary-subtle border-0">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-magic me-2"></i>Bulk Enrollment</h5>
                    <p class="text-primary-emphasis small mb-0">
                        Enrolling students one by one is time consuming. Download the spreadsheet template, fill it with your students information, and import everything in a single click!
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
