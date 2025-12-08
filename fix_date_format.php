<?php

/**
 * Script to replace DATE_FORMAT with dbDateFormat in PHP files
 */

$files = [
    'app/Http/Controllers/Admin/ReportController.php',
    'app/Http/Controllers/Admin/TransactionReportController.php',
    'app/Http/Controllers/Admin/ProductReportController.php',
    'app/Http/Controllers/Admin/OrderReportController.php',
    'app/Http/Controllers/Admin/VendorProductSaleReportController.php',
    'app/Http/Controllers/Admin/ExpenseTransactionReportController.php',
    'app/Http/Controllers/Vendor/TransactionReportController.php',
    'app/Http/Controllers/Vendor/ProductReportController.php',
    'app/Http/Controllers/Vendor/OrderReportController.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }

    $content = file_get_contents($file);
    $original = $content;

    // Pattern 1: selectRaw("*, DATE_FORMAT(column, 'format') as alias")
    // Replace with: selectRaw("*, ".dbDateFormat('column', 'format')." as alias")
    $content = preg_replace(
        '/selectRaw\("([^"]*?)DATE_FORMAT\(([^,]+),\s*\'([^\']+)\'\)([^"]*?)"\)/',
        'selectRaw("$1".dbDateFormat(\'$2\', \'$3\')."$4")',
        $content
    );

    // Pattern 2: DB::raw("DATE_FORMAT(column, 'format')")
    // Replace with: DB::raw(dbDateFormat('column', 'format'))
    $content = preg_replace(
        '/DB::raw\("DATE_FORMAT\(([^,]+),\s*\'([^\']+)\'\)"\)/',
        'DB::raw(dbDateFormat(\'$1\', \'$2\'))',
        $content
    );

    // Pattern 3: DB::raw("(DATE_FORMAT(column, 'format')) as alias")
    // Replace with: DB::raw("(".dbDateFormat('column', 'format').") as alias")
    $content = preg_replace(
        '/DB::raw\("\(DATE_FORMAT\(([^,]+),\s*\'([^\']+)\'\)\)\s*as\s*([^"]+)"\)/',
        'DB::raw("(".dbDateFormat(\'$1\', \'$2\').") as $3")',
        $content
    );

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated: $file\n";
    } else {
        echo "No changes: $file\n";
    }
}

echo "\nDone!\n";
