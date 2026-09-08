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
        $required = ['loan_id', 'payment_date', 'emi_amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // 1. Fetch the Loan
        $loanId = $data['loan_id'];
        $loanRowIndex = $sheets->findRowIndexById('LOANS!A:A', $loanId);
        if ($loanRowIndex === false) {
            throw new Exception("Loan not found");
        }
        
        $loanRange = "LOANS!A" . ($loanRowIndex + 1) . ":S" . ($loanRowIndex + 1);
        $loanData = $sheets->getSheetData($loanRange)[0];
        
        if (empty($loanData)) {
            throw new Exception("Error retrieving loan data");
        }
        
        // Extract Loan fields (padding array if short)
        $loanData = array_pad($loanData, 19, '');
        $familyMemberId = $loanData[1];
        $interestRate = floatval($loanData[6]);
        $emiAmount = floatval($data['emi_amount']);
        $totalPaid = floatval($loanData[11]);
        $principalPaid = floatval($loanData[12]);
        $interestPaid = floatval($loanData[13]);
        $outstandingPrincipal = floatval($loanData[14]);
        $calcMethod = isset($loanData[17]) ? $loanData[17] : 'AMORTIZATION';
        
        $principalComponent = 0;
        $interestComponent = 0;
        
        // 2. Calculate Math
        if ($calcMethod === 'MANUAL') {
            $principalComponent = isset($data['principal_component']) ? floatval($data['principal_component']) : 0;
            $interestComponent = isset($data['interest_component']) ? floatval($data['interest_component']) : 0;
            
            // If they just entered EMI amount, we assume it's all principal if they didn't split it, but usually manual mode requires splits.
            if ($principalComponent == 0 && $interestComponent == 0) {
                // Fallback for lazy entry
                $principalComponent = $emiAmount;
            }
        } else {
            // AMORTIZATION
            // Monthly Rate = Annual Rate / 12 / 100
            $monthlyRate = ($interestRate / 12) / 100;
            $interestComponent = $outstandingPrincipal * $monthlyRate;
            $principalComponent = $emiAmount - $interestComponent;
            
            if ($principalComponent < 0) {
                throw new Exception("EMI amount is insufficient to cover the monthly interest. This would cause negative amortization.");
            }
        }
        
        // Cap principal payment to outstanding principal for final payment
        if ($principalComponent > $outstandingPrincipal) {
            $principalComponent = $outstandingPrincipal;
        }

        $newOutstanding = $outstandingPrincipal - $principalComponent;
        $newTotalPaid = $totalPaid + $emiAmount;
        $newPrincipalPaid = $principalPaid + $principalComponent;
        $newInterestPaid = $interestPaid + $interestComponent;
        
        // Advance EMI Date (1 month)
        $currentNextEmi = isset($loanData[15]) && !empty($loanData[15]) ? $loanData[15] : $data['payment_date'];
        $nextEmiDate = date('Y-m-d', strtotime('+1 month', strtotime($currentNextEmi)));
        
        // Check if Loan is paid off
        $loanStatus = ($newOutstanding <= 0.01) ? 'Closed' : 'Active';

        // Update Loan Row
        $loanData[11] = $newTotalPaid;
        $loanData[12] = $newPrincipalPaid;
        $loanData[13] = $newInterestPaid;
        $loanData[14] = round($newOutstanding, 2);
        $loanData[15] = $nextEmiDate;
        $loanData[16] = $loanStatus;

        $sheets->updateRow($loanRange, $loanData);

        // 3. Append to EMI_PAYMENTS
        $id = 'EMI-' . date('Ym') . '-' . uniqid();
        $month = date('F Y', strtotime($data['payment_date']));
        
        // EMI_PAYMENTS Schema:
        // ['payment_id', 'loan_id', 'family_member_id', 'payment_date', 'month', 'emi_amount', 'principal_component', 'interest_component', 'status', 'payment_method', 'notes']
        $row = [
            $id,
            $loanId,
            $familyMemberId,
            $data['payment_date'],
            $month,
            $emiAmount,
            round($principalComponent, 2),
            round($interestComponent, 2),
            isset($data['status']) ? $data['status'] : 'Paid',
            isset($data['payment_method']) ? $data['payment_method'] : 'Bank Transfer',
            isset($data['notes']) ? $data['notes'] : ''
        ];

        $sheets->appendRow('EMI_PAYMENTS!A:K', $row);

        echo json_encode(['success' => true, 'message' => 'EMI recorded successfully', 'id' => $id]);
    
    } else if ($method === 'GET') {
        $records = $sheets->getSheetData('EMI_PAYMENTS!A:K');
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
