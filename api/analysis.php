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
    
    // Fetch all necessary sheets
    $ranges = [
        'SETTINGS!A:C',
        'INCOME!A:I',
        'EXPENSES!A:K',
        'LOANS!A:S',
        'EMI_PAYMENTS!A:K',
        'BANK_ACCOUNTS!A:H',
        'INVESTMENTS!A:J'
    ];
    $batchData = $sheets->getBatchSheetData($ranges);

    // Helper: Map rows to associative array
    function mapData($records) {
        if (empty($records)) return [];
        $headers = array_shift($records);
        foreach ($headers as &$h) $h = strtolower(trim(str_replace(' ', '_', $h)));
        $result = [];
        foreach ($records as $row) {
            $item = [];
            foreach ($headers as $i => $key) {
                $item[$key] = isset($row[$i]) ? $row[$i] : '';
            }
            $result[] = $item;
        }
        return $result;
    }

    $settingsData = mapData($batchData[0]);
    $incomeData = mapData($batchData[1]);
    $expenseData = mapData($batchData[2]);
    $loansData = mapData($batchData[3]);
    $emiData = mapData($batchData[4]);
    $bankData = mapData($batchData[5]);
    $invData = mapData($batchData[6]);

    // Parse Settings
    $settings = [
        'emergency_fund_target_months' => 6,
        'debt_strategy' => 'avalanche',
        'minimum_savings_allocation' => 0,
        'minimum_investment_allocation' => 0,
        'expense_classifications' => []
    ];
    foreach ($settingsData as $s) {
        $settings[$s['setting_name']] = $s['setting_value'];
    }
    if (is_string($settings['expense_classifications']) && !empty($settings['expense_classifications'])) {
        $decoded = json_decode($settings['expense_classifications'], true);
        if (is_array($decoded)) $settings['expense_classifications'] = $decoded;
    }

    // Determine current month string (e.g. "2026-09")
    $current_ym = date('Y-m');

    $snapshot = [
        'income' => 0,
        'essential_expenses' => 0,
        'discretionary_expenses' => 0,
        'emi' => 0,
        'investments' => 0,
        'savings' => 0,
        'free_cash_flow' => 0
    ];

    $historical = []; // Array of last 6 months "Y-m" => [income, expense]

    // Process Income
    foreach ($incomeData as $inc) {
        $fm_id = isset($inc['family_member_id']) ? $inc['family_member_id'] : '';
        if ($filter_member !== 'ALL' && $fm_id !== $filter_member) continue;

        $amt = floatval($inc['amount']);
        $ym = date('Y-m', strtotime($inc['date']));
        
        if (!isset($historical[$ym])) $historical[$ym] = ['income' => 0, 'expense' => 0];
        $historical[$ym]['income'] += $amt;

        if ($ym === $current_ym) $snapshot['income'] += $amt;
    }

    // Process Expenses
    $expensesByCategory = [];
    foreach ($expenseData as $exp) {
        $fm_id = isset($exp['family_member_id']) ? $exp['family_member_id'] : '';
        // If ALL, include shared expenses. If specific, only match.
        if ($filter_member !== 'ALL' && $fm_id !== $filter_member) continue;

        $amt = floatval($exp['amount']);
        $ym = date('Y-m', strtotime($exp['date']));
        $cat = isset($exp['category']) ? $exp['category'] : 'Other';
        
        if (!isset($historical[$ym])) $historical[$ym] = ['income' => 0, 'expense' => 0];
        $historical[$ym]['expense'] += $amt;

        if ($ym === $current_ym) {
            $class = isset($settings['expense_classifications'][$cat]) ? $settings['expense_classifications'][$cat] : 'Discretionary';
            if ($class === 'Essential') {
                $snapshot['essential_expenses'] += $amt;
            } else {
                $snapshot['discretionary_expenses'] += $amt;
            }
            if (!isset($expensesByCategory[$cat])) $expensesByCategory[$cat] = 0;
            $expensesByCategory[$cat] += $amt;
        }
    }

    // Process EMI (Only Option A: tracked via EMI_PAYMENTS)
    foreach ($emiData as $emi) {
        $fm_id = isset($emi['family_member_id']) ? $emi['family_member_id'] : '';
        if ($filter_member !== 'ALL' && $fm_id !== $filter_member) continue;

        $ym = date('Y-m', strtotime($emi['payment_date']));
        if ($ym === $current_ym) {
            $snapshot['emi'] += floatval($emi['emi_amount']);
        }
    }

    // Process Investments
    foreach ($invData as $inv) {
        $fm_id = isset($inv['family_member_id']) ? $inv['family_member_id'] : '';
        if ($filter_member !== 'ALL' && $fm_id !== $filter_member) continue;

        if (!empty($inv['date'])) {
            $ym = date('Y-m', strtotime($inv['date']));
            if ($ym === $current_ym) {
                $snapshot['investments'] += floatval($inv['invested_amount']);
            }
        }
    }

    // Bank Accounts (Liquid Assets for Emergency Fund)
    $liquidAssets = 0;
    foreach ($bankData as $acc) {
        $fm_id = isset($acc['family_member_id']) ? $acc['family_member_id'] : '';
        if ($filter_member !== 'ALL' && $fm_id !== $filter_member) continue;
        $liquidAssets += floatval($acc['current_balance']);
    }

    // Compute Derived Snapshot Metrics
    $snapshot['savings'] = $snapshot['income'] - $snapshot['essential_expenses'] - $snapshot['discretionary_expenses'] - $snapshot['emi'];
    $snapshot['free_cash_flow'] = $snapshot['savings'] - $snapshot['investments'];
    
    $savingsRate = $snapshot['income'] > 0 ? ($snapshot['savings'] / $snapshot['income']) * 100 : 0;
    $emiRatio = $snapshot['income'] > 0 ? ($snapshot['emi'] / $snapshot['income']) * 100 : 0;
    $expenseRatio = $snapshot['income'] > 0 ? (($snapshot['essential_expenses'] + $snapshot['discretionary_expenses']) / $snapshot['income']) * 100 : 0;

    $emergencyFundTarget = $snapshot['essential_expenses'] * floatval($settings['emergency_fund_target_months']);
    $emergencyFundProgress = $emergencyFundTarget > 0 ? ($liquidAssets / $emergencyFundTarget) * 100 : 100;

    // Debt Strategy & Active Loans
    $activeLoans = [];
    $totalDebt = 0;
    foreach ($loansData as $loan) {
        $fm_id = isset($loan['family_member_id']) ? $loan['family_member_id'] : '';
        if ($filter_member !== 'ALL' && $fm_id !== $filter_member) continue;

        if ($loan['status'] === 'Active') {
            $outstanding = floatval($loan['outstanding_principal']);
            $totalDebt += $outstanding;
            $activeLoans[] = [
                'loan_id' => $loan['loan_id'],
                'loan_name' => $loan['loan_name'],
                'outstanding' => $outstanding,
                'interest_rate' => floatval($loan['interest_rate']),
                'emi' => floatval($loan['emi_amount'])
            ];
        }
    }

    $strategy = $settings['debt_strategy'];
    if ($strategy === 'avalanche') {
        usort($activeLoans, function($a, $b) { return $b['interest_rate'] <=> $a['interest_rate']; });
    } else if ($strategy === 'snowball') {
        usort($activeLoans, function($a, $b) { return $a['outstanding'] <=> $b['outstanding']; });
    }

    // Extra Payment Capacity
    $extraPaymentCapacity = $snapshot['free_cash_flow'] - floatval($settings['minimum_savings_allocation']);
    if ($extraPaymentCapacity < 0) $extraPaymentCapacity = 0;

    // Recommendations Engine
    $recommendations = [];

    // Rule: Missing Data
    if (count($historical) < 3) {
        $recommendations[] = [
            'priority' => 'LOW',
            'issue' => 'Limited Data History',
            'evidence' => 'Less than 3 months of financial records available.',
            'impact' => 'Trend analysis and debt projections may be less accurate.',
            'action' => 'Continue recording income and expenses consistently.'
        ];
    }

    // Rule: Negative Cash Flow
    if ($snapshot['free_cash_flow'] < 0) {
        $recommendations[] = [
            'priority' => 'HIGH',
            'issue' => 'Negative Free Cash Flow',
            'evidence' => 'Your monthly outflows exceed your recorded income by ₹' . abs($snapshot['free_cash_flow']),
            'impact' => 'You may be accumulating hidden debt or depleting savings reserves.',
            'action' => 'Review discretionary expenses (' . count($expensesByCategory) . ' categories) and halt non-essential spending.'
        ];
    }

    // Rule: EMI Ratio > 40%
    if ($emiRatio > 40) {
        $recommendations[] = [
            'priority' => 'HIGH',
            'issue' => 'High EMI Burden',
            'evidence' => "Your current EMI represents " . round($emiRatio, 1) . "% of your monthly income.",
            'impact' => 'A ratio above 40% restricts financial flexibility and cash flow.',
            'action' => 'Focus on reducing debt using the ' . ucfirst($strategy) . ' strategy before taking on new financial commitments.'
        ];
    }

    // Rule: Emergency Fund
    if ($emergencyFundProgress < 100) {
        $recommendations[] = [
            'priority' => 'MEDIUM',
            'issue' => 'Emergency Fund Gap',
            'evidence' => "Current liquid assets cover " . round($emergencyFundProgress, 1) . "% of your " . $settings['emergency_fund_target_months'] . "-month target.",
            'impact' => 'Unexpected expenses could force you into high-interest debt.',
            'action' => 'Allocate a portion of free cash flow towards your emergency savings.'
        ];
    }

    // Rule: High Interest Loan Focus
    if (!empty($activeLoans)) {
        $target = $activeLoans[0]; // The #1 prioritized loan
        $recommendations[] = [
            'priority' => 'MEDIUM',
            'issue' => 'Debt Prioritization Target',
            'evidence' => "Based on your " . ucfirst($strategy) . " strategy, '" . $target['loan_name'] . "' is the priority.",
            'impact' => 'Directing extra payments here accelerates your debt-free timeline mathematically.',
            'action' => 'Consider directing your ₹' . number_format($extraPaymentCapacity) . ' extra capacity to this loan.'
        ];
    }

    // --- DEBT-FREE SIMULATOR ENGINE ---
    function runSimulator($loans, $strategy, $extraPaymentMultiplier, $availableExtraCapacity) {
        $simLoans = [];
        foreach ($loans as $l) {
            $simLoans[] = [
                'id' => $l['loan_id'],
                'name' => $l['loan_name'],
                'balance' => $l['outstanding'],
                'rate' => $l['interest_rate'],
                'emi' => $l['emi']
            ];
        }
        
        $months = 0;
        $totalInterest = 0;
        $roadmap = [];
        
        $extraPaymentFixed = $availableExtraCapacity * $extraPaymentMultiplier;

        while (count($simLoans) > 0 && $months < 360) {
            $months++;
            $monthExtra = $extraPaymentFixed;
            
            if ($strategy === 'avalanche') {
                usort($simLoans, function($a, $b) { return $b['rate'] <=> $a['rate']; });
            } else {
                usort($simLoans, function($a, $b) { return $a['balance'] <=> $b['balance']; });
            }
            
            $monthRoadmap = [
                'month' => $months,
                'opening_debt' => array_sum(array_column($simLoans, 'balance')),
                'regular_emi' => 0,
                'extra_payment' => $monthExtra,
                'interest' => 0,
                'principal' => 0,
                'closing_debt' => 0,
                'target_loan' => isset($simLoans[0]) ? $simLoans[0]['name'] : ''
            ];
            
            foreach ($simLoans as $k => &$l) {
                if ($l['balance'] <= 0) continue;
                
                $monthlyRate = ($l['rate'] / 12) / 100;
                $interest = $l['balance'] * $monthlyRate;
                $totalInterest += $interest;
                $monthRoadmap['interest'] += $interest;
                
                $regularPrincipal = $l['emi'] - $interest;
                if ($regularPrincipal < 0) $regularPrincipal = 0;
                
                $monthRoadmap['regular_emi'] += $l['emi'];
                $monthRoadmap['principal'] += $regularPrincipal;
                
                $l['balance'] -= $regularPrincipal;
            }
            unset($l);
            
            foreach ($simLoans as $k => &$l) {
                if ($monthExtra <= 0) break;
                if ($l['balance'] <= 0) continue;
                
                if ($monthExtra >= $l['balance']) {
                    $monthExtra -= $l['balance'];
                    $monthRoadmap['principal'] += $l['balance'];
                    $l['balance'] = 0;
                } else {
                    $l['balance'] -= $monthExtra;
                    $monthRoadmap['principal'] += $monthExtra;
                    $monthExtra = 0;
                }
            }
            unset($l);
            
            foreach ($simLoans as $k => $l) {
                if ($l['balance'] <= 0.01) {
                    $extraPaymentFixed += $l['emi'];
                    unset($simLoans[$k]);
                }
            }
            $simLoans = array_values($simLoans);
            
            $monthRoadmap['closing_debt'] = array_sum(array_column($simLoans, 'balance'));
            
            if ($months <= 24) {
                $roadmap[] = $monthRoadmap;
            }
        }
        
        return [
            'months' => $months,
            'total_interest' => $totalInterest,
            'roadmap' => $roadmap
        ];
    }
    
    $scenarios = [];
    $roadmap = [];
    
    if (!empty($activeLoans)) {
        $scenCurrent = runSimulator($activeLoans, $strategy, 0, $extraPaymentCapacity);
        $scenCons = runSimulator($activeLoans, $strategy, 0.5, $extraPaymentCapacity);
        $scenBal = runSimulator($activeLoans, $strategy, 1.0, $extraPaymentCapacity);
        $scenAgg = runSimulator($activeLoans, $strategy, 1.5, $extraPaymentCapacity);
        
        $scenarios = [
            ['name' => 'Current Plan', 'months' => $scenCurrent['months'], 'interest' => $scenCurrent['total_interest'], 'saved' => 0],
            ['name' => 'Conservative', 'months' => $scenCons['months'], 'interest' => $scenCons['total_interest'], 'saved' => $scenCurrent['total_interest'] - $scenCons['total_interest']],
            ['name' => 'Balanced', 'months' => $scenBal['months'], 'interest' => $scenBal['total_interest'], 'saved' => $scenCurrent['total_interest'] - $scenBal['total_interest']],
            ['name' => 'Aggressive', 'months' => $scenAgg['months'], 'interest' => $scenAgg['total_interest'], 'saved' => $scenCurrent['total_interest'] - $scenAgg['total_interest']]
        ];
        
        $roadmap = $scenBal['roadmap'];
    }

    // Output payload
    $response = [
        'success' => true,
        'data' => [
            'snapshot' => $snapshot,
            'ratios' => [
                'savings_rate' => $savingsRate,
                'emi_ratio' => $emiRatio,
                'expense_ratio' => $expenseRatio,
                'emergency_fund_progress' => $emergencyFundProgress,
                'emergency_fund_target' => $emergencyFundTarget,
                'liquid_assets' => $liquidAssets
            ],
            'debt' => [
                'total_debt' => $totalDebt,
                'active_loans' => $activeLoans,
                'extra_capacity' => $extraPaymentCapacity,
                'strategy' => $strategy
            ],
            'simulator' => [
                'scenarios' => $scenarios,
                'roadmap' => $roadmap
            ],
            'recommendations' => $recommendations,
            'historical_count' => count($historical)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
