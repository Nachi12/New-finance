<?php
/**
 * Google Sheets API Helper Class
 * 
 * This class handles all CRUD operations with the Google Sheets database.
 */

class GoogleSheetsHelper {
    private $client;
    private $service;
    private $spreadsheetId;

    public function __construct() {
        if (!class_exists('Google_Client')) {
            $autoload = BASE_PATH . '/vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            } else {
                throw new Exception("Google API Client is not installed. Please run 'composer install'.");
            }
        }

        if (!isset($_ENV['GOOGLE_SPREADSHEET_ID'])) {
            throw new Exception("GOOGLE_SPREADSHEET_ID is not set in .env file.");
        }
        $this->spreadsheetId = $_ENV['GOOGLE_SPREADSHEET_ID'];
        $this->client = new \Google\Client();
        $this->client->setApplicationName('Finance Dashboard');
        $this->client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');
        
        // Try direct JSON string first (for Vercel/PaaS)
        if (isset($_ENV['GOOGLE_CREDENTIALS_JSON']) && !empty($_ENV['GOOGLE_CREDENTIALS_JSON'])) {
            $credentials = json_decode($_ENV['GOOGLE_CREDENTIALS_JSON'], true);
            if (!$credentials) {
                throw new Exception("GOOGLE_CREDENTIALS_JSON is invalid JSON.");
            }
            $this->client->setAuthConfig($credentials);
        } else {
            // Fallback to local file path
            if (!isset($_ENV['GOOGLE_CREDENTIALS_PATH'])) {
                throw new Exception("Either GOOGLE_CREDENTIALS_JSON or GOOGLE_CREDENTIALS_PATH must be set in environment.");
            }
            $credentialsPath = BASE_PATH . '/' . $_ENV['GOOGLE_CREDENTIALS_PATH'];
            if (!file_exists($credentialsPath)) {
                throw new Exception("Google Credentials file not found at $credentialsPath");
            }
            $this->client->setAuthConfig($credentialsPath);
        }
        
        $this->service = new \Google\Service\Sheets($this->client);
    }

    /**
     * Get all rows from a specific sheet
     * 
     * @param string $range e.g., "INCOME!A:I"
     * @return array
     */
    public function getSheetData($range) {
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        $values = $response->getValues();
        return $values ? $values : [];
    }

    /**
     * Get multiple ranges from the sheet in a single request (Batch Get)
     * 
     * @param array $ranges e.g., ["INCOME!A:J", "EXPENSES!A:J"]
     * @return array
     */
    public function getBatchSheetData($ranges) {
        $response = $this->service->spreadsheets_values->batchGet($this->spreadsheetId, ['ranges' => $ranges]);
        $valueRanges = $response->getValueRanges();
        
        $result = [];
        foreach ($valueRanges as $valueRange) {
            // The range returned by API might look like "INCOME!A1:J1000", we key it by the original requested sheet name if possible, 
            // but since they return in the same order, we'll just map them to the same order.
            $result[] = $valueRange->getValues() ? $valueRange->getValues() : [];
        }
        return $result;
    }

    /**
     * Append a row to a specific sheet
     * 
     * @param string $range e.g., "INCOME!A:I"
     * @param array $values An array of string values representing the row
     * @return bool
     */
    public function appendRow($range, $values) {
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => [$values]
        ]);
        
        $params = [
            'valueInputOption' => 'USER_ENTERED'
        ];
        
        $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
        return true;
    }

    /**
     * Update a specific row in a sheet
     * 
     * @param string $range e.g., "INCOME!A5:I5"
     * @param array $values An array of string values
     * @return bool
     */
    public function updateRow($range, $values) {
        $body = new \Google\Service\Sheets\ValueRange([
            'values' => [$values]
        ]);
        
        $params = [
            'valueInputOption' => 'USER_ENTERED'
        ];
        
        $this->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
        return true;
    }

    /**
     * Clear (Empty) a specific row without deleting it entirely, 
     * or use BatchUpdate to completely delete the row shift it up.
     * 
     * @param int $sheetId The numeric ID of the sheet (gid)
     * @param int $rowIndex The 0-based row index to delete
     * @return bool
     */
    public function deleteRow($sheetId, $rowIndex) {
        $requests = [
            new \Google\Service\Sheets\Request([
                'deleteDimension' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $rowIndex,
                        'endIndex' => $rowIndex + 1
                    ]
                ]
            ])
        ];
        
        $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);
        
        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
        return true;
    }

    /**
     * Find the row index (0-based) of a given ID in a specific sheet.
     * Returns false if not found.
     * 
     * @param string $range e.g., "INCOME!A:A" (Just the ID column)
     * @param string $id The ID to search for
     * @return int|bool
     */
    public function findRowIndexById($range, $id) {
        $values = $this->getSheetData($range);
        foreach ($values as $index => $row) {
            if (isset($row[0]) && $row[0] === $id) {
                return $index;
            }
        }
        return false;
    }
    
    /**
     * Gets all sheet metadata (names and IDs)
     */
    public function getSheetMetadata() {
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
        $sheets = [];
        foreach ($spreadsheet->getSheets() as $sheet) {
            $props = $sheet->getProperties();
            $sheets[$props->getTitle()] = $props->getSheetId();
        }
        return $sheets;
    }
}
