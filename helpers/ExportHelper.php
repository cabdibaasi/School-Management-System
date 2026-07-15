<?php
/**
 * Export Helper — CSV, Excel (SpreadsheetML .xls), PDF/Print
 */
class ExportHelper {

    /**
     * Export as plain CSV
     */
    public static function toCSV($filename, $headers, $data) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($data as $row) fputcsv($output, $row);
        fclose($output);
        exit;
    }

    /**
     * Export as Excel-compatible SpreadsheetML (.xls)
     * Opens natively in Microsoft Excel and LibreOffice Calc with full styling
     */
    public static function toExcel($filename, $headers, $data, $sheetTitle = 'Sheet1') {
        if (ob_get_length()) ob_clean();

        $schoolName = Setting::get('school_name', 'School Management System');
        $now        = date('Y-m-d H:i:s');

        $x = function($v) { return htmlspecialchars((string)$v, ENT_XML1, 'UTF-8'); };

        // Calculate column widths
        $widths = array_fill(0, count($headers), 12);
        foreach ($headers as $i => $h) $widths[$i] = max($widths[$i], mb_strlen($h) + 2);
        foreach ($data as $row) {
            foreach (array_values($row) as $i => $cell) {
                if (isset($widths[$i])) $widths[$i] = max($widths[$i], mb_strlen((string)$cell) + 2);
            }
        }

        $colCount = count($headers);

        ob_start();
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
<Author>School Management System</Author><Created>' . date('c') . '</Created>
</DocumentProperties>' . "\n";

        echo '<Styles>
<Style ss:ID="Default"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>
<Style ss:ID="title"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Bold="1" ss:Size="14" ss:Color="#1D4ED8"/><Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/></Style>
<Style ss:ID="sub"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#64748B"/></Style>
<Style ss:ID="hdr"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Calibri" ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF"/><Interior ss:Color="#1D4ED8" ss:Pattern="Solid"/></Style>
<Style ss:ID="even"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="10"/><Interior ss:Color="#F8FAFF" ss:Pattern="Solid"/></Style>
<Style ss:ID="odd"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="10"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/></Style>
<Style ss:ID="badge_active"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Bold="1" ss:Size="10" ss:Color="#065F46"/><Interior ss:Color="#D1FAE5" ss:Pattern="Solid"/></Style>
<Style ss:ID="badge_inactive"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Bold="1" ss:Size="10" ss:Color="#991B1B"/><Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/></Style>
</Styles>' . "\n";

        echo '<Worksheet ss:Name="' . $x($sheetTitle) . '"><Table>';

        foreach ($widths as $w) {
            echo '<Column ss:AutoFitWidth="0" ss:Width="' . min($w * 7, 200) . '"/>';
        }

        // Title row
        echo '<Row ss:Height="28"><Cell ss:MergeAcross="' . ($colCount - 1) . '" ss:StyleID="title"><Data ss:Type="String">' . $x($schoolName) . ' — ' . $x($sheetTitle) . '</Data></Cell></Row>';
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="' . ($colCount - 1) . '" ss:StyleID="sub"><Data ss:Type="String">Generated: ' . $x($now) . ' | Total Records: ' . count($data) . '</Data></Cell></Row>';
        echo '<Row ss:Height="8"><Cell ss:MergeAcross="' . ($colCount - 1) . '"><Data ss:Type="String"> </Data></Cell></Row>';

        // Header row
        echo '<Row ss:Height="22">';
        foreach ($headers as $h) echo '<Cell ss:StyleID="hdr"><Data ss:Type="String">' . $x($h) . '</Data></Cell>';
        echo '</Row>';

        // Data rows
        foreach ($data as $idx => $row) {
            $styleId = ($idx % 2 === 0) ? 'even' : 'odd';
            echo '<Row ss:Height="18">';
            foreach (array_values($row) as $cell) {
                $cellStyle = $styleId;
                $lower = strtolower(trim((string)$cell));
                if ($lower === 'active')   $cellStyle = 'badge_active';
                if ($lower === 'inactive') $cellStyle = 'badge_inactive';
                $type = is_numeric($cell) ? 'Number' : 'String';
                echo '<Cell ss:StyleID="' . $cellStyle . '"><Data ss:Type="' . $type . '">' . $x($cell) . '</Data></Cell>';
            }
            echo '</Row>';
        }

        // Footer
        echo '<Row ss:Height="8"><Cell ss:MergeAcross="' . ($colCount - 1) . '"><Data ss:Type="String"> </Data></Cell></Row>';
        echo '<Row><Cell ss:MergeAcross="' . ($colCount - 1) . '" ss:StyleID="sub"><Data ss:Type="String">End of Report | ' . count($data) . ' record(s) exported on ' . $x($now) . '</Data></Cell></Row>';

        echo '</Table></Worksheet></Workbook>';
        $xml = ob_get_clean();

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename) . '.xls"');
        header('Content-Length: ' . strlen($xml));
        echo $xml;
        exit;
    }

    /**
     * Render a beautiful print/PDF layout with school letterhead
     */
    public static function renderPrintLayout($title, $headers, $data, $subtitle = '') {
        $schoolName    = Setting::get('school_name', 'St. Andrew Academy');
        $schoolAddress = Setting::get('school_address', '');
        $schoolPhone   = Setting::get('school_phone', '');
        $schoolEmail   = Setting::get('school_email', '');
        $now           = date('d M Y, H:i');
        $totalRows     = count($data);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($title) ?> — <?= e($schoolName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #1e293b; font-size: 11px; }

        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: #fff; padding: 12px 28px; gap: 12px;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 12px rgba(29,78,216,0.4);
        }
        .toolbar-title { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .toolbar-title span { font-size: 10px; font-weight: 400; opacity: 0.75; }
        .toolbar-btns { display: flex; gap: 10px; }
        .btn-print {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 22px; border-radius: 8px; font-size: 12px; font-weight: 700;
            background: #fff; color: #1d4ed8; border: none; cursor: pointer; transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-print:hover { background: #dbeafe; transform: translateY(-1px); }
        .btn-close-w {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 22px; border-radius: 8px; font-size: 12px; font-weight: 600;
            background: rgba(255,255,255,0.15); color: #fff;
            border: 1px solid rgba(255,255,255,0.3); cursor: pointer; transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-close-w:hover { background: rgba(255,255,255,0.25); }

        .page { max-width: 1100px; margin: 24px auto; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.12); }

        .letterhead {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 55%, #0284c7 100%);
            color: #fff; padding: 28px 36px; display: flex; align-items: center; gap: 22px;
        }
        .school-logo-box {
            width: 58px; height: 58px; flex-shrink: 0;
            background: rgba(255,255,255,0.18); border: 2px solid rgba(255,255,255,0.35);
            border-radius: 14px; display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 900; letter-spacing: -1px;
        }
        .school-info h2 { font-size: 17px; font-weight: 800; margin-bottom: 5px; }
        .school-info p  { font-size: 10px; opacity: 0.82; line-height: 1.8; }

        .report-meta {
            background: #f8fafc; border-bottom: 2px solid #e2e8f0;
            padding: 14px 36px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        }
        .meta-left .meta-title { font-size: 14px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.6px; }
        .meta-left .meta-sub   { font-size: 10px; color: #64748b; margin-top: 3px; }
        .meta-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .meta-chip {
            padding: 4px 13px; border-radius: 20px; font-size: 10px; font-weight: 700;
            background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe;
        }

        .table-wrap { padding: 20px 24px 28px; overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        thead tr { background: linear-gradient(135deg, #1d4ed8, #2563eb); }
        thead th {
            color: #fff; font-size: 9.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.6px;
            padding: 10px 10px; text-align: left; white-space: nowrap;
        }
        thead th:first-child { padding-left: 14px; border-radius: 8px 0 0 0; }
        thead th:last-child  { border-radius: 0 8px 0 0; }
        tbody tr:nth-child(even) { background: #f8faff; }
        tbody tr:nth-child(odd)  { background: #ffffff; }
        tbody td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; color: #334155; }
        tbody td:first-child { padding-left: 14px; color: #94a3b8; font-size: 10px; text-align: center; }
        tbody tr:last-child td { border-bottom: none; }

        .badge-status {
            display: inline-block; padding: 2px 9px; border-radius: 20px;
            font-size: 9.5px; font-weight: 700; letter-spacing: 0.3px;
        }
        .badge-active   { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-other    { background: #fef9c3; color: #92400e; }
        .no-records { text-align: center; padding: 40px; color: #94a3b8; font-style: italic; }

        .page-footer {
            background: #f8fafc; border-top: 1px solid #e2e8f0;
            padding: 12px 36px; display: flex; justify-content: space-between; align-items: center;
            font-size: 9.5px; color: #64748b;
        }

        @media print {
            body { background: #fff; font-size: 10px; }
            .toolbar { display: none !important; }
            .page { box-shadow: none; margin: 0; border-radius: 0; }
            @page { margin: 12mm 10mm; size: A4 landscape; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <div class="toolbar-title">
        📄 <?= e($title) ?>
        <span><?= $totalRows ?> Record<?= $totalRows != 1 ? 's' : '' ?></span>
    </div>
    <div class="toolbar-btns">
        <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
        <button class="btn-close-w" onclick="window.close()">✕ Close</button>
    </div>
</div>

<div class="page">

    <div class="letterhead">
        <div class="school-logo-box"><?= mb_strtoupper(mb_substr($schoolName, 0, 2)) ?></div>
        <div class="school-info">
            <h2><?= e($schoolName) ?></h2>
            <p>
                <?php if ($schoolAddress): ?><?= e($schoolAddress) ?><br><?php endif; ?>
                <?php if ($schoolPhone): ?>📞 <?= e($schoolPhone) ?><?php endif; ?>
                <?php if ($schoolEmail): ?> &nbsp;&bull;&nbsp; ✉ <?= e($schoolEmail) ?><?php endif; ?>
            </p>
        </div>
    </div>

    <div class="report-meta">
        <div class="meta-left">
            <div class="meta-title"><?= e($title) ?></div>
            <div class="meta-sub">
                <?php if ($subtitle): ?><?= e($subtitle) ?> &mdash; <?php endif; ?>
                Generated on <?= $now ?>
            </div>
        </div>
        <div class="meta-right">
            <span class="meta-chip">📋 <?= $totalRows ?> Record<?= $totalRows != 1 ? 's' : '' ?></span>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:36px; text-align:center;">#</th>
                    <?php foreach ($headers as $h): ?>
                        <th><?= e($h) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="<?= count($headers) + 1 ?>" class="no-records">No records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <?php foreach ($row as $cell):
                                $lower = strtolower(trim((string)$cell));
                                if ($lower === 'active'):   ?><td><span class="badge-status badge-active">Active</span></td>
                                <?php elseif ($lower === 'inactive'):  ?><td><span class="badge-status badge-inactive">Inactive</span></td>
                                <?php elseif (in_array($lower, ['graduated','suspended','expelled'])): ?><td><span class="badge-status badge-other"><?= e(ucfirst($lower)) ?></span></td>
                                <?php else: ?><td><?= e($cell) ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="page-footer">
        <span><?= e($schoolName) ?> &mdash; Confidential Document</span>
        <span><?= e($title) ?> | <?= $totalRows ?> record(s)</span>
        <span>Printed: <?= $now ?></span>
    </div>

</div>

<script>
    window.addEventListener('DOMContentLoaded', () => setTimeout(() => window.print(), 700));
</script>
</body>
</html>
        <?php
        exit;
    }
}
