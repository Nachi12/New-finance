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
        $required = ['family_member_id', 'asset_name', 'asset_type', 'purchase_value'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $id = 'AST-' . date('Ym') . '-' . uniqid();
        
        $purchase_value = floatval($data['purchase_value']);
        $current_value = isset($data['current_value']) && $data['current_value'] !== '' ? floatval($data['current_value']) : $purchase_value;
        
        // ASSETS Schema:
        // ['asset_id', 'family_member_id', 'asset_name', 'asset_type', 'purchase_value', 'current_value', 'purchase_date', 'notes']
        $row = [
            $id,
            $data['family_member_id'],
            $data['asset_name'],
            $data['asset_type'],
            $purchase_value,
            $current_value,
            isset($data['purchase_date']) ? $data['purchase_date'] : '',
            isset($data['notes']) ? $data['notes'] : ''
        ];

        $sheets->appendRow('ASSETS!A:H', $row);
        
        echo json_encode(['success' => true, 'message' => 'Asset added successfully', 'id' => $id]);
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('ASSETS!A:H');
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
