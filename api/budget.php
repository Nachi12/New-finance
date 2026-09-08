<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    require_once INCLUDES_PATH . '/sheets.php';
    $sheets = new GoogleSheetsHelper();
    
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception("Invalid JSON input");
        }

        // Validate required fields
        $required = ['month', 'category', 'budget_amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $id = 'BGT-' . date('Ym') . '-' . uniqid();
        
        $month = date('F Y', strtotime($data['month'] . '-01'));
        
        // BUDGET Schema:
        // ['budget_id', 'month', 'category', 'budget_amount']
        $row = [
            $id,
            $month,
            $data['category'],
            $data['budget_amount']
        ];

        $sheets->appendRow('BUDGET!A:D', $row);
        
        echo json_encode(['success' => true, 'message' => 'Budget set successfully', 'id' => $id]);
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('BUDGET!A:D');
        $headers = array_shift($records);
        $result = [];
        
        foreach ($records as $row) {
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
