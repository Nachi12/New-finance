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

        // Action routing
        $action = isset($data['action']) ? $data['action'] : 'add';

        if ($action === 'add') {
            $required = ['name', 'relationship'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $id = 'FM-' . strtoupper(uniqid());
            $createdAt = date('Y-m-d H:i:s');
            
            // Schema: family_member_id, name, relationship, status, notes, created_at
            $row = [
                $id,
                $data['name'],
                $data['relationship'],
                isset($data['status']) ? $data['status'] : 'Active',
                isset($data['notes']) ? $data['notes'] : '',
                $createdAt
            ];

            $sheets->appendRow('FAMILY!A:F', $row);
            
            echo json_encode(['success' => true, 'message' => 'Family member added successfully', 'id' => $id]);
        } 
        else if ($action === 'update' || $action === 'deactivate') {
            if (empty($data['family_member_id'])) {
                throw new Exception("Missing family_member_id");
            }
            
            $id = $data['family_member_id'];
            $rowIndex = $sheets->findRowIndexById('FAMILY!A:A', $id);
            
            if ($rowIndex === false) {
                throw new Exception("Member not found");
            }
            
            // Fetch current row data
            $allData = $sheets->getSheetData('FAMILY!A:F');
            $currentRow = $allData[$rowIndex];
            
            if ($action === 'deactivate') {
                $currentRow[3] = 'Inactive';
            } else if ($action === 'update') {
                if (isset($data['name'])) $currentRow[1] = $data['name'];
                if (isset($data['relationship'])) $currentRow[2] = $data['relationship'];
                if (isset($data['status'])) $currentRow[3] = $data['status'];
                if (isset($data['notes'])) $currentRow[4] = $data['notes'];
            }
            
            // Google Sheets row index is 1-based for API, so $rowIndex + 1
            $range = 'FAMILY!A' . ($rowIndex + 1) . ':F' . ($rowIndex + 1);
            $sheets->updateRow($range, $currentRow);
            
            echo json_encode(['success' => true, 'message' => 'Family member updated successfully']);
        }
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('FAMILY!A:F');
        
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
