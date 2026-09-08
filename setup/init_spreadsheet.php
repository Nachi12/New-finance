<?php
// NOTE: This script is intended to be run from the CLI (e.g., `php setup/init_spreadsheet.php`)
// It requires Composer dependencies (google/apiclient) to be installed.

require_once __DIR__ . '/../config/config.php';

try {
    require_once __DIR__ . '/../includes/sheets.php';
    $sheetsHelper = new GoogleSheetsHelper();
    
    echo "Successfully authenticated with Google Sheets API.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure you have run 'composer install' and set up your .env file.\n";
    exit(1);
}

// Instantiate the raw service to perform batch operations
$client = new \Google\Client();
$client->setApplicationName('Finance Dashboard Setup');
$client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);
$client->setAuthConfig(BASE_PATH . '/' . $_ENV['GOOGLE_CREDENTIALS_PATH']);
$service = new \Google\Service\Sheets($client);
$spreadsheetId = $_ENV['GOOGLE_SPREADSHEET_ID'];

// Schema definition
$schema = [
    'SETTINGS' => ['id', 'setting_name', 'setting_value'],
    'FAMILY' => ['family_member_id', 'name', 'relationship', 'status', 'notes', 'created_at'],
    'INCOME' => ['income_id', 'date', 'month', 'family_member_id', 'income_source', 'income_type', 'amount', 'recurring', 'notes'],
    'EXPENSES' => ['expense_id', 'date', 'month', 'family_member_id', 'category', 'subcategory', 'description', 'amount', 'payment_method', 'recurring', 'notes'],
    'LOANS' => ['loan_id', 'family_member_id', 'loan_name', 'lender', 'loan_type', 'principal_amount', 'interest_rate', 'interest_type', 'tenure_months', 'start_date', 'emi_amount', 'total_paid', 'principal_paid', 'interest_paid', 'outstanding_principal', 'next_emi_date', 'status', 'balance_calculation_method', 'notes'],
    'EMI_PAYMENTS' => ['payment_id', 'loan_id', 'family_member_id', 'payment_date', 'month', 'emi_amount', 'principal_component', 'interest_component', 'status', 'payment_method', 'notes'],
    'BANK_ACCOUNTS' => ['account_id', 'family_member_id', 'bank_name', 'account_name', 'account_type', 'opening_balance', 'current_balance', 'notes'],
    'TRANSACTIONS' => ['transaction_id', 'date', 'month', 'family_member_id', 'account_id', 'type', 'category', 'description', 'amount', 'payment_method', 'reference', 'notes'],
    'INVESTMENTS' => ['investment_id', 'date', 'family_member_id', 'investment_type', 'investment_name', 'invested_amount', 'current_value', 'returns', 'return_percentage', 'notes'],
    'ASSETS' => ['asset_id', 'family_member_id', 'asset_name', 'asset_type', 'purchase_value', 'current_value', 'purchase_date', 'notes'],
    'FINANCIAL_GOALS' => ['goal_id', 'family_member_id', 'goal_name', 'target_amount', 'current_amount', 'target_date', 'monthly_contribution', 'status', 'notes'],
    'BUDGET' => ['budget_id', 'month', 'category', 'budget_amount'],
    'MONTHLY_SUMMARY' => ['month', 'total_income', 'total_expenses', 'total_emi', 'total_investment', 'total_savings', 'net_cash_flow', 'outstanding_debt', 'net_worth']
];

echo "Fetching existing sheets...\n";
$existingMetadata = $sheetsHelper->getSheetMetadata();

$requests = [];
$dataRequests = [];

// 1. Create missing sheets
foreach ($schema as $sheetTitle => $columns) {
    if (!isset($existingMetadata[$sheetTitle])) {
        echo "Queueing creation of sheet: $sheetTitle\n";
        $requests[] = new \Google\Service\Sheets\Request([
            'addSheet' => [
                'properties' => [
                    'title' => $sheetTitle
                ]
            ]
        ]);
    }
}

// Execute sheet creation
if (!empty($requests)) {
    echo "Creating sheets...\n";
    $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
        'requests' => $requests
    ]);
    $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
    echo "Sheets created successfully.\n";
} else {
    echo "All sheets already exist.\n";
}

// 2. Add headers
echo "Setting up headers...\n";
foreach ($schema as $sheetTitle => $columns) {
    $range = $sheetTitle . '!A1';
    $body = new \Google\Service\Sheets\ValueRange([
        'values' => [$columns]
    ]);
    $params = [
        'valueInputOption' => 'RAW'
    ];
    $service->spreadsheets_values->update(
        $spreadsheetId,
        $range,
        $body,
        $params
    );
}
echo "Headers verified/updated.\n";
echo "Spreadsheet initialization complete!\n";
