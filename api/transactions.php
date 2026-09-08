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
        $required = ['date', 'account_id', 'type', 'amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $id = 'TXN-' . date('Ym') . '-' . uniqid();
        $month = date('F Y', strtotime($data['date']));
        
        // TRANSACTIONS Schema:
        // ['transaction_id', 'date', 'month', 'family_member_id', 'account_id', 'type', 'category', 'description', 'amount', 'payment_method', 'reference', 'notes']
        $row = [
            $id,
            $data['date'],
            $month,
            isset($data['family_member_id']) ? $data['family_member_id'] : '',
            $data['account_id'],
            $data['type'], // Transfer, Withdrawal, Investment, etc.
            isset($data['category']) ? $data['category'] : '',
            isset($data['description']) ? $data['description'] : '',
            $data['amount'],
            isset($data['payment_method']) ? $data['payment_method'] : '',
            isset($data['reference']) ? $data['reference'] : '',
            isset($data['notes']) ? $data['notes'] : ''
        ];

        $sheets->appendRow('TRANSACTIONS!A:L', $row);
        
        // Note: In Phase 8, the account current_balance will be recalculated 
        // by factoring in Income + TransfersIn - Expenses - TransfersOut
        
        echo json_encode(['success' => true, 'message' => 'Transaction recorded successfully', 'id' => $id]);
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('TRANSACTIONS!A:L');
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
