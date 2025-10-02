<?php
require_once 'database.php';

class ExcelExporter {
    private $db;
    
    public function __construct() {
        $this->db = new OracleDatabase();
        $this->db->connect();
    }
    
    public function generateExcelReport($startDate, $endDate) {
        // Get optimized data
        $salesData = $this->db->getSalesData($startDate, $endDate);
        
        // Create PHPExcel object (you can use PhpSpreadsheet library)
        $excelData = $this->formatExcelData($salesData);
        
        // Generate Excel file
        $filename = "sales_report_" . date('Y-m-d_H-i-s') . ".xlsx";
        $this->createExcelFile($excelData, $filename);
        
        return $filename;
    }
    
    private function formatExcelData($data) {
        $excelData = [];
        
        // Add headers
        $excelData[] = [
            'Sale ID', 'Date', 'Product', 'Category', 
            'Quantity', 'Unit Price', 'Total Amount', 'Region', 'Month'
        ];
        
        // Add data rows
        foreach ($data as $row) {
            $excelData[] = [
                $row['SALE_ID'],
                $row['SALE_DATE'],
                $row['PRODUCT_NAME'],
                $row['CATEGORY_NAME'],
                $row['QUANTITY'],
                $row['UNIT_PRICE'],
                $row['TOTAL_AMOUNT'],
                $row['REGION_NAME'],
                $row['SALE_MONTH']
            ];
        }
        
        return $excelData;
    }
    
    private function createExcelFile($data, $filename) {
        // Simple CSV export (you can enhance with PhpSpreadsheet for real Excel)
        $filepath = "../exports/" . $filename;
        
        $fp = fopen($filepath, 'w');
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        
        return $filepath;
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'] ?? date('Y-m-01');
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    
    $exporter = new ExcelExporter();
    $filename = $exporter->generateExcelReport($startDate, $endDate);
    
    echo json_encode([
        'status' => 'success',
        'filename' => $filename,
        'download_url' => 'exports/' . $filename
    ]);
}
?>
