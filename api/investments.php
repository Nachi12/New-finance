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
        $required = ['date', 'family_member_id', 'investment_type', 'investment_name', 'invested_amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $id = 'INV-' . date('Ym') . '-' . uniqid();
        
        $invested_amount = floatval($data['invested_amount']);
        $current_value = isset($data['current_value']) && $data['current_value'] !== '' ? floatval($data['current_value']) : $invested_amount;
        
        $returns = $current_value - $invested_amount;
        $return_percentage = $invested_amount > 0 ? ($returns / $invested_amount) * 100 : 0;
        
        // INVESTMENTS Schema:
        // ['investment_id', 'date', 'family_member_id', 'investment_type', 'investment_name', 'invested_amount', 'current_value', 'returns', 'return_percentage', 'notes']
        $row = [
            $id,
            $data['date'],
            $data['family_member_id'],
            $data['investment_type'],
            $data['investment_name'],
            $invested_amount,
            $current_value,
            $returns,
            round($return_percentage, 2),
            isset($data['notes']) ? $data['notes'] : ''
        ];

        $sheets->appendRow('INVESTMENTS!A:J', $row);
        
        echo json_encode(['success' => true, 'message' => 'Investment added successfully', 'id' => $id]);
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('INVESTMENTS!A:J');
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
