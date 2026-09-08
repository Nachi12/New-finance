<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    require_once INCLUDES_PATH . '/sheets.php';
    $sheets = new GoogleSheetsHelper();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $range = 'SETTINGS!A:C'; // id, setting_name, setting_value

    if ($method === 'GET') {
        $records = $sheets->getSheetData($range);
        
        // Defaults
        $settings = [
            'emergency_fund_target_months' => 6,
            'debt_strategy' => 'avalanche',
            'minimum_savings_allocation' => 0,
            'minimum_investment_allocation' => 0,
            'expense_classifications' => json_encode([
                'Rent' => 'Essential',
                'Utilities' => 'Essential',
                'Groceries' => 'Essential',
                'Entertainment' => 'Discretionary',
                'Dining Out' => 'Discretionary'
            ])
        ];

        if (!empty($records)) {
            $headers = array_shift($records);
            foreach ($records as $row) {
                if (isset($row[1]) && isset($row[2])) {
                    $settings[$row[1]] = $row[2];
                }
            }
        }
        
        // Decode JSON settings for the frontend
        if (isset($settings['expense_classifications']) && is_string($settings['expense_classifications'])) {
            $decoded = json_decode($settings['expense_classifications'], true);
            if (is_array($decoded)) {
                $settings['expense_classifications'] = $decoded;
            }
        }

        echo json_encode(['success' => true, 'data' => $settings]);
        
    } else if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            throw new Exception("Invalid JSON input");
        }

        // We encode expense classifications back to string if present
        if (isset($data['expense_classifications']) && is_array($data['expense_classifications'])) {
            $data['expense_classifications'] = json_encode($data['expense_classifications']);
        }

        $records = $sheets->getSheetData($range);
        $headers = !empty($records) ? array_shift($records) : ['id', 'setting_name', 'setting_value'];
        
        // Map existing settings to row numbers (1-indexed for the sheet, where row 1 is headers)
        $existing = [];
        foreach ($records as $index => $row) {
            if (isset($row[1])) {
                $existing[$row[1]] = $index + 2; // +2 because 1 for 0-index offset, 1 for header row
            }
        }

        foreach ($data as $key => $value) {
            if (isset($existing[$key])) {
                // Update existing row
                $rowNum = $existing[$key];
                // Need the original ID, or just generate a new one if missing
                $id = '';
                foreach ($records as $r) {
                    if (isset($r[1]) && $r[1] === $key) {
                        $id = isset($r[0]) ? $r[0] : uniqid();
                        break;
                    }
                }
                
                $updateRange = "SETTINGS!A{$rowNum}:C{$rowNum}";
                $sheets->updateRow($updateRange, [$id, $key, (string)$value]);
            } else {
                // Append new row
                $id = uniqid();
                $sheets->appendRow($range, [$id, $key, (string)$value]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
