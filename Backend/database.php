<?php
class OracleDatabase {
    private $conn;
    private $config;
    
    public function __construct() {
        $this->config = [
            'host' => 'your_host',
            'port' => '1521',
            'service_name' => 'your_service',
            'username' => 'your_username',
            'password' => 'your_password'
        ];
    }
    
    public function connect() {
        try {
            $dsn = "oci:dbname=//" . $this->config['host'] . ":" . 
                   $this->config['port'] . "/" . $this->config['service_name'];
            
            $this->conn = new PDO($dsn, $this->config['username'], $this->config['password']);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "Connected to Oracle Database successfully!\n";
            return true;
            
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            return false;
        }
    }
    
    // OPTIMIZED SQL QUERY FOR LARGE DATASETS
    public function getSalesData($startDate, $endDate, $limit = 10000) {
        $sql = "
        SELECT /*+ PARALLEL(8) FIRST_ROWS(1000) */
            s.sale_id,
            s.sale_date,
            p.product_name,
            c.category_name,
            s.quantity,
            s.unit_price,
            s.total_amount,
            r.region_name,
            TO_CHAR(s.sale_date, 'YYYY-MM') as sale_month
        FROM sales s
        JOIN products p ON s.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        JOIN regions r ON s.region_id = r.region_id
        WHERE s.sale_date BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                             AND TO_DATE(:end_date, 'YYYY-MM-DD')
        ORDER BY s.sale_date DESC
        FETCH FIRST :limit ROWS ONLY
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // BATCH PROCESSING FOR VERY LARGE DATASETS
    public function getSalesDataBatch($startDate, $endDate, $batchSize = 1000) {
        $offset = 0;
        $allData = [];
        
        do {
            $sql = "
            SELECT * FROM (
                SELECT a.*, ROWNUM as rnum FROM (
                    SELECT s.*, p.product_name, c.category_name
                    FROM sales s
                    JOIN products p ON s.product_id = p.product_id
                    JOIN categories c ON p.category_id = c.category_id
                    WHERE s.sale_date BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                                         AND TO_DATE(:end_date, 'YYYY-MM-DD')
                    ORDER BY s.sale_date DESC
                ) a
                WHERE ROWNUM <= :offset + :batch_size
            )
            WHERE rnum >= :offset
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':batch_size', $batchSize, PDO::PARAM_INT);
            $stmt->execute();
            
            $batchData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $allData = array_merge($allData, $batchData);
            $offset += $batchSize;
            
        } while (!empty($batchData));
        
        return $allData;
    }
}
?>
