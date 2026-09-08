<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    require_once INCLUDES_PATH . '/sheets.php';
    $sheets = new GoogleSheetsHelper();
    
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $filter_member = isset($_GET['member_id']) ? $_GET['member_id'] : 'ALL';
    $filter_month = isset($_GET['month']) ? $_GET['month'] : date('m');
    $filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

    // Helper function to map sheet rows to associative arrays
    function mapSheetData($records) {
        if (empty($records)) return [];
        $headers = array_shift($records);
        
        foreach ($headers as &$header) {
            $header = strtolower(trim($header));
            $header = str_replace(' ', '_', $header);
        }
        
        $result = [];
        foreach ($records as $row) {
            $item = [];
            foreach ($headers as $index => $key) {
                $item[$key] = isset($row[$index]) ? $row[$index] : '';
            }
            $result[] = $item;
        }
        return $result;
    }

    $ranges = [
        'BANK_ACCOUNTS!A:H',
        'INVESTMENTS!A:J',
        'ASSETS!A:H',
        'LOANS!A:O',
        'INCOME!A:J',
        'EXPENSES!A:L',
        'TRANSACTIONS!A:L',
        'FAMILY!A:F',
        'EMI_PAYMENTS!A:J'
    ];
    $batchData = $sheets->getBatchSheetData($ranges);

    $accountsData = mapSheetData($batchData[0]);
    $investmentsData = mapSheetData($batchData[1]);
    $assetsData = mapSheetData($batchData[2]);
    $loansData = mapSheetData($batchData[3]);
    $incomeData = mapSheetData($batchData[4]);
    $expenseData = mapSheetData($batchData[5]);
    $transactionsData = mapSheetData($batchData[6]);
    $familyData = mapSheetData($batchData[7]);
    $emiData = mapSheetData($batchData[8]);

    // Track member stats for comparison
    $memberStats = [];
    foreach ($familyData as $fm) {
        if (!empty($fm['family_member_id'])) {
            $memberStats[$fm['family_member_id']] = [
                'name' => $fm['name'],
                'income' => 0,
                'expenses' => 0,
                'emi' => 0,
                'savings' => 0,
                'debt' => 0,
                'net_worth' => 0,
                'assets' => 0
            ];
        }
    }

    // Determine current filtering month string (e.g. "2026-09" or just checking date values)
    $filter_ym = $filter_year . '-' . str_pad($filter_month, 2, '0', STR_PAD_LEFT); // e.g. "2026-09"

    // 1. Calculate Monthly Income
    $monthly_income = 0;
    $income_by_category = [];
    
    foreach ($incomeData as $inc) {
        $inc_ym = '';
        if (!empty($inc['date'])) {
            $inc_ym = date('Y-m', strtotime($inc['date']));
        }
        
        $amt = floatval($inc['amount']);
        $fm_id = isset($inc['family_member_id']) ? $inc['family_member_id'] : '';

        // Add to Member Comparison regardless of global filter
        if ($inc_ym === $filter_ym && isset($memberStats[$fm_id])) {
            $memberStats[$fm_id]['income'] += $amt;
        }

        // Apply Global Filters
        if ($inc_ym === $filter_ym && ($filter_member === 'ALL' || $filter_member === $fm_id)) {
            $monthly_income += $amt;
            $cat = isset($inc['income_source']) ? $inc['income_source'] : 'Uncategorized';
            if (!isset($income_by_category[$cat])) $income_by_category[$cat] = 0;
            $income_by_category[$cat] += $amt;
        }
    }

    // 2. Calculate Monthly Expenses
    $monthly_expenses = 0;
    $expenses_by_category = [];
    
    foreach ($expenseData as $exp) {
        $exp_ym = '';
        if (!empty($exp['date'])) {
            $exp_ym = date('Y-m', strtotime($exp['date']));
        }
        
        $amt = floatval($exp['amount']);
        $fm_id = isset($exp['family_member_id']) ? $exp['family_member_id'] : '';

        // Member Comparison
        if ($exp_ym === $filter_ym && isset($memberStats[$fm_id])) {
            $memberStats[$fm_id]['expenses'] += $amt;
        }

        // Apply Global Filters
        if ($exp_ym === $filter_ym) {
            $include = false;
            if ($filter_member === 'ALL') {
                $include = true;
            } else if ($filter_member === $fm_id) {
                $include = true;
            }
            
            if ($include) {
                $monthly_expenses += $amt;
                $cat = isset($exp['category']) ? $exp['category'] : 'Uncategorized';
                if (!isset($expenses_by_category[$cat])) $expenses_by_category[$cat] = 0;
                $expenses_by_category[$cat] += $amt;
            }
        }
    }

    // 3. Calculate Monthly EMI
    $monthly_emi = 0;
    foreach ($emiData as $emi) {
        $emi_ym = '';
        if (!empty($emi['payment_date'])) {
            $emi_ym = date('Y-m', strtotime($emi['payment_date']));
        }
        
        $amt = floatval($emi['emi_amount']);
        
        // Find borrower from loan
        $fm_id = '';
        $loan_id = isset($emi['loan_id']) ? $emi['loan_id'] : '';
        foreach ($loansData as $loan) {
            if (isset($loan['loan_id']) && $loan['loan_id'] === $loan_id) {
                $fm_id = isset($loan['borrower']) ? $loan['borrower'] : '';
                break;
            }
        }
        
        if ($emi_ym === $filter_ym && isset($memberStats[$fm_id])) {
            $memberStats[$fm_id]['emi'] += $amt;
        }

        if ($emi_ym === $filter_ym && ($filter_member === 'ALL' || $filter_member === $fm_id)) {
            $monthly_emi += $amt;
        }
    }

    // 4. Calculate Net Worth / Assets / Debt (Global/All-time for the selected member(s))
    $total_assets = 0;
    foreach ($accountsData as $acc) {
        $fm_id = isset($acc['family_member_id']) ? $acc['family_member_id'] : '';
        $amt = floatval($acc['current_balance']);
        if (isset($memberStats[$fm_id])) $memberStats[$fm_id]['assets'] += $amt;
        if ($filter_member === 'ALL' || $filter_member === $fm_id) $total_assets += $amt;
    }
    
    foreach ($investmentsData as $inv) {
        $fm_id = isset($inv['family_member_id']) ? $inv['family_member_id'] : '';
        $amt = floatval($inv['current_value']);
        if (isset($memberStats[$fm_id])) $memberStats[$fm_id]['assets'] += $amt;
        if ($filter_member === 'ALL' || $filter_member === $fm_id) $total_assets += $amt;
    }
    
    foreach ($assetsData as $ast) {
        $fm_id = isset($ast['family_member_id']) ? $ast['family_member_id'] : '';
        $amt = floatval($ast['current_value']);
        if (isset($memberStats[$fm_id])) $memberStats[$fm_id]['assets'] += $amt;
        if ($filter_member === 'ALL' || $filter_member === $fm_id) $total_assets += $amt;
    }

    $total_debt = 0;
    foreach ($loansData as $loan) {
        $fm_id = isset($loan['borrower']) ? $loan['borrower'] : '';
        $amt = floatval($loan['outstanding_principal']);
        if (isset($memberStats[$fm_id])) $memberStats[$fm_id]['debt'] += $amt;
        if ($filter_member === 'ALL' || $filter_member === $fm_id) $total_debt += $amt;
    }
    
    $net_worth = $total_assets - $total_debt;

    // Calculate Savings for member stats
    foreach ($memberStats as $fm_id => &$stats) {
        $stats['savings'] = $stats['income'] - $stats['expenses'] - $stats['emi'];
        $stats['net_worth'] = $stats['assets'] - $stats['debt'];
    }

    $monthly_savings = $monthly_income - $monthly_expenses - $monthly_emi;
    $savings_rate = $monthly_income > 0 ? round(($monthly_savings / $monthly_income) * 100, 2) : 'N/A';

    // 7. Get Recent Activity
    $recent_activity = [];
    foreach ($incomeData as $inc) {
        if (!empty($inc['date'])) {
            $fm_id = isset($inc['family_member_id']) ? $inc['family_member_id'] : '';
            if ($filter_member === 'ALL' || $filter_member === $fm_id) {
                $recent_activity[] = [
                    'date' => $inc['date'],
                    'type' => 'Income',
                    'description' => isset($inc['income_source']) ? $inc['income_source'] : 'Income',
                    'amount' => floatval($inc['amount'])
                ];
            }
        }
    }
    foreach ($expenseData as $exp) {
        if (!empty($exp['date'])) {
            $fm_id = isset($exp['family_member_id']) ? $exp['family_member_id'] : '';
            if ($filter_member === 'ALL' || $filter_member === $fm_id) {
                $recent_activity[] = [
                    'date' => $exp['date'],
                    'type' => 'Expense',
                    'description' => isset($exp['category']) ? $exp['category'] : 'Expense',
                    'amount' => floatval($exp['amount']) * -1
                ];
            }
        }
    }
    
    usort($recent_activity, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    $recent_activity = array_slice($recent_activity, 0, 10);

    // Prepare response data
    $response = [
        'success' => true,
        'data' => [
            'summary' => [
                'monthly_income' => $monthly_income,
                'monthly_expenses' => $monthly_expenses,
                'monthly_emi' => $monthly_emi,
                'monthly_savings' => $monthly_savings,
                'savings_rate' => $savings_rate,
                'total_debt' => $total_debt,
                'net_worth' => $net_worth
            ],
            'breakdowns' => [
                'income_by_category' => $income_by_category,
                'expenses_by_category' => $expenses_by_category
            ],
            'member_stats' => array_values($memberStats),
            'recent_activity' => $recent_activity
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
