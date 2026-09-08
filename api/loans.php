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
        $required = [
            'family_member_id', 'loan_name', 'lender', 'loan_type', 
            'principal_amount', 'interest_rate', 'interest_type', 
            'tenure_months', 'start_date', 'emi_amount', 'balance_calculation_method'
        ];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Generate unique ID
        $id = 'LOAN-' . date('Ym') . '-' . uniqid();
        
        // Prepare row data matching the LOANS schema
        // ['loan_id', 'family_member_id', 'loan_name', 'lender', 'loan_type', 'principal_amount', 'interest_rate', 'interest_type', 'tenure_months', 'start_date', 'emi_amount', 'total_paid', 'principal_paid', 'interest_paid', 'outstanding_principal', 'next_emi_date', 'status', 'balance_calculation_method', 'notes']
        $row = [
            $id,
            $data['family_member_id'],
            $data['loan_name'],
            $data['lender'],
            $data['loan_type'],
            $data['principal_amount'],
            $data['interest_rate'],
            $data['interest_type'],
            $data['tenure_months'],
            $data['start_date'],
            $data['emi_amount'],
            0, // total_paid initially 0
            0, // principal_paid initially 0
            0, // interest_paid initially 0
            $data['principal_amount'], // outstanding_principal initially equal to principal
            isset($data['next_emi_date']) && !empty($data['next_emi_date']) ? $data['next_emi_date'] : $data['start_date'],
            'Active', // status
            $data['balance_calculation_method'],
            isset($data['notes']) ? $data['notes'] : ''
        ];

        $sheets->appendRow('LOANS!A:S', $row);
        
        echo json_encode(['success' => true, 'message' => 'Loan added successfully', 'id' => $id]);
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('LOANS!A:S');
        
        if (empty($records)) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        $headers = array_shift($records);
        $result = [];
        
        foreach ($records as $row) {
            $item = [];
            foreach ($headers as $index => $key) {
                $item[strtolower(trim($key))] = isset($row[$index]) ? $row[$index] : '';
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
