<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    require_once INCLUDES_PATH . '/sheets.php';
    $sheets = new GoogleSheetsHelper();
    
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // Read JSON input
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception("Invalid JSON input");
        }

        // Validate required fields
        $required = ['date', 'family_member_id', 'income_source', 'income_type', 'amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Generate unique ID and formatted month
        $id = 'INC-' . date('Ym') . '-' . uniqid();
        $month = date('F Y', strtotime($data['date'])); // e.g., "September 2026"
        
        // Prepare row data matching the INCOME schema
        // ['income_id', 'date', 'month', 'family_member_id', 'income_source', 'income_type', 'amount', 'recurring', 'notes']
        $row = [
            $id,
            $data['date'],
            $month,
            $data['family_member_id'],
            $data['income_source'],
            $data['income_type'],
            $data['amount'],
            isset($data['recurring']) ? $data['recurring'] : 'No',
            isset($data['notes']) ? $data['notes'] : ''
        ];

        $sheets->appendRow('INCOME!A:I', $row);
        
        echo json_encode(['success' => true, 'message' => 'Income added successfully', 'id' => $id]);
    } else if ($method === 'GET') {
        // Fetch all income records
        $records = $sheets->getSheetData('INCOME!A:I');
        
        // Skip header row
        $headers = array_shift($records);
        $result = [];
        
        foreach ($records as $row) {
            // Map array to associative array using headers
            $item = [];
            foreach ($headers as $index => $key) {
                $item[$key] = isset($row[$index]) ? $row[$index] : '';
            }
            $result[] = $item;
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
