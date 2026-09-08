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
        $required = ['family_member_id', 'goal_name', 'target_amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $id = 'GOAL-' . date('Ym') . '-' . uniqid();
        
        $target_amount = floatval($data['target_amount']);
        $current_amount = isset($data['current_amount']) && $data['current_amount'] !== '' ? floatval($data['current_amount']) : 0;
        
        $monthly_contribution = 0;
        
        if (!empty($data['target_date'])) {
            $target_date = new DateTime($data['target_date']);
            $now = new DateTime();
            $interval = $now->diff($target_date);
            
            // Calculate months remaining
            $months = ($interval->y * 12) + $interval->m;
            if ($months > 0) {
                $remaining_amount = $target_amount - $current_amount;
                if ($remaining_amount > 0) {
                    $monthly_contribution = $remaining_amount / $months;
                }
            }
        }
        
        // FINANCIAL_GOALS Schema:
        // ['goal_id', 'family_member_id', 'goal_name', 'target_amount', 'current_amount', 'target_date', 'monthly_contribution', 'status', 'notes']
        $row = [
            $id,
            $data['family_member_id'],
            $data['goal_name'],
            $target_amount,
            $current_amount,
            isset($data['target_date']) ? $data['target_date'] : '',
            round($monthly_contribution, 2),
            'In Progress', // status
            isset($data['notes']) ? $data['notes'] : ''
        ];

        $sheets->appendRow('FINANCIAL_GOALS!A:I', $row);
        
        echo json_encode(['success' => true, 'message' => 'Financial goal added successfully', 'id' => $id]);
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('FINANCIAL_GOALS!A:I');
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
