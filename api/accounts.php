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
        $required = ['family_member_id', 'bank_name', 'account_name', 'account_type', 'opening_balance'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $id = 'ACC-' . date('Ym') . '-' . uniqid();
        
        // BANK_ACCOUNTS Schema:
        // ['account_id', 'family_member_id', 'bank_name', 'account_name', 'account_type', 'opening_balance', 'current_balance', 'notes']
        $row = [
            $id,
            $data['family_member_id'],
            $data['bank_name'],
            $data['account_name'],
            $data['account_type'],
            $data['opening_balance'],
            $data['opening_balance'], // Initial current balance is the opening balance
            isset($data['notes']) ? $data['notes'] : ''
        ];

        $sheets->appendRow('BANK_ACCOUNTS!A:H', $row);
        
        echo json_encode(['success' => true, 'message' => 'Account added successfully', 'id' => $id]);
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('BANK_ACCOUNTS!A:H');
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
