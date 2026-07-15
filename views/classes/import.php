<?php
/**
 * Import Classes via CSV / Excel
 */
$pageTitle = "Import Classes";
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
    header('Content-Disposition: attachment; filename="class_import_template.csv"');
    
    $output = fopen('php://output', 'w');
    // Header columns
    fputcsv($output, [
        'Class Name',
        'Section',
        'Academic Year (e.g. 2026-2027)'
    ]);
    // Sample rows
    fputcsv($output, [
        'Grade 7', 'A', '2026-2027'
    ]);
    fputcsv($output, [
        'Grade 8', 'B', '2026-2027'
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
                
                // Map columns
                $className = trim($row[0] ?? '');
                $section   = trim($row[1] ?? '');
                $acadYear  = trim($row[2] ?? '');
                
                // Basic validations
                if (empty($className) || empty($section) || empty($acadYear)) {
                    $failedCount++;
                    $errors[] = "Line {$line}: Missing required fields (Class Name, Section, or Academic Year).";
                    continue;
                }
                
                // Check if class already exists
                $stmt = $db->prepare("SELECT id FROM classes WHERE class_name = :name AND section = :sec AND academic_year = :year LIMIT 1");
                $stmt->execute(['name' => $className, 'sec' => $section, 'year' => $acadYear]);
                if ($stmt->fetch()) {
                    $failedCount++;
                    $errors[] = "Line {$line}: Class '{$className} - {$section}' for year {$acadYear} already exists.";
                    continue;
                }
                
                // Create Class
                if (SchoolClass::create($className, $section, $acadYear)) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $errors[] = "Line {$line}: Database error occurred while saving class record.";
                }
            }
            
            fclose($file);
            $importResults = [
                'success' => $successCount,
                'failed' => $failedCount,
                'errors' => $errors
            ];
            
            if ($successCount > 0) {
                Utility::setFlash('success', "Import completed: {$successCount} class(es) imported successfully.");
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
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>views/classes/index.php">Classes</a></li>
        <li class="breadcrumb-item active" aria-current="page">Import</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8 col-md-12">
        <div class="card section-card">
            <div class="card-header bg-white border-0 py-3">
                <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-file-import me-2"></i>Import Classes from Excel / CSV</h4>
                <p class="text-muted small mb-0">Upload a spreadsheet formatted as a CSV file to create multiple classes and academic sections in bulk.</p>
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
                        <li class="mb-1"><strong>Required columns:</strong> Class Name, Section, and Academic Year.</li>
                        <li class="mb-1">Academic Year format should match your system configuration (e.g. <code>2026-2027</code>).</li>
                        <li class="mb-1">Duplicate checks: If a class with the same name, section, and academic year already exists in the system, it will be skipped.</li>
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
                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-magic me-2"></i>Bulk Class Setup</h5>
                    <p class="text-primary-emphasis small mb-0">
                        Create multiple class rooms and school sections at once. Simply upload your CSV schedule to populate classes in bulk.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . 'views/layouts/footer.php'; ?>
