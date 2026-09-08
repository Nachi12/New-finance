<?php
require_once __DIR__ . '/config/config.php';
require_once INCLUDES_PATH . '/sheets.php';

$sheets = new GoogleSheetsHelper();

$expectedHeaders = [
    'BANK_ACCOUNTS' => ['account_id', 'account_name', 'bank_name', 'account_type', 'current_balance', 'last_updated', 'notes'],
    'INVESTMENTS' => ['investment_id', 'investment_name', 'type', 'invested_amount', 'current_value', 'date_invested', 'maturity_date', 'notes'],
    'ASSETS' => ['asset_id', 'asset_name', 'asset_type', 'purchase_price', 'current_value', 'purchase_date', 'notes'],
    'LOANS' => ['loan_id', 'loan_name', 'lender', 'principal_amount', 'outstanding_principal', 'interest_rate', 'emi_amount', 'start_date', 'end_date', 'next_emi_date', 'notes'],
    'INCOME' => ['income_id', 'date', 'month', 'family_member_id', 'income_source', 'category', 'amount', 'recurring', 'notes'],
    'EXPENSES' => ['expense_id', 'date', 'month', 'family_member_id', 'category', 'subcategory', 'description', 'amount', 'payment_method', 'recurring', 'notes'],
    'TRANSACTIONS' => ['transaction_id', 'date', 'month', 'family_member_id', 'account_id', 'type', 'category', 'description', 'amount', 'payment_method', 'reference', 'notes']
];

foreach ($expectedHeaders as $sheetName => $headers) {
    try {
        $range = $sheetName . '!A:Z';
        $data = $sheets->getSheetData($range);
        
        $needsHeaders = false;
        
        if (empty($data)) {
            $data = [$headers];
        } else {
            // Check if the first row is headers. 
            $firstCell = isset($data[0][0]) ? strtolower(str_replace(' ', '_', trim($data[0][0]))) : '';
            if ($firstCell === $headers[0]) {
                // Already has headers, just forcefully overwrite the first row to ensure they are the CORRECT ones!
                $data[0] = $headers;
            } else {
                // Prepend the headers
                array_unshift($data, $headers);
            }
        }
        
        // Pad all rows to have the same number of columns as the headers
        $numCols = count($headers);
            foreach ($data as &$row) {
                while (count($row) < $numCols) {
                    $row[] = '';
                }
            }
            
            // Update the sheet
            // We use the helper's internal service to update
            // Since we need to update the whole sheet, we can use clear and then update, or just overwrite.
            // Google API allows ValueRange update. It will overwrite the range.
            $client = new \Google\Client();
            $client->setApplicationName('Finance Dashboard');
            $client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);
            $client->setAuthConfig(BASE_PATH . '/' . $_ENV['GOOGLE_CREDENTIALS_PATH']);
            $service = new \Google\Service\Sheets($client);
            
            // Clear the existing data first to ensure no leftover cells
            $clearRequest = new \Google\Service\Sheets\ClearValuesRequest();
            $service->spreadsheets_values->clear($_ENV['GOOGLE_SPREADSHEET_ID'], $range, $clearRequest);
            
            // Write the new data
            $body = new \Google\Service\Sheets\ValueRange([
                'values' => $data
            ]);
            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];
            $service->spreadsheets_values->update(
                $_ENV['GOOGLE_SPREADSHEET_ID'],
                $range,
                $body,
                $params
            );
            
            echo "Successfully updated headers for $sheetName\n";
        
    } catch (Exception $e) {
        echo "Error processing $sheetName: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
