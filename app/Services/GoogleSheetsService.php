<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsService
{
  protected $client;
  protected $service;
  protected $spreadsheetId;

  public function __construct()
  {
    $this->client = $this->getClient();
    $this->service = new Sheets($this->client);
    $this->spreadsheetId = env('GOOGLE_SHEET_ID');

    // Debug: Check if spreadsheet ID is loaded
    if (!$this->spreadsheetId) {
      throw new \Exception('GOOGLE_SHEET_ID not found in environment variables');
    }
  }

  public function getClient()
  {
    $client = new Client();
    $client->setApplicationName('Laravel Google Sheets Integration');
    $client->setScopes(Sheets::SPREADSHEETS);

    // Use the private directory path
    $credentialsPath = storage_path('app/private/userdataspreadsheet-c93aa4793c04.json');

    if (!file_exists($credentialsPath)) {
      throw new \Exception('Credentials file not found: ' . $credentialsPath);
    }

    // Check if file is readable
    if (!is_readable($credentialsPath)) {
      throw new \Exception('Credentials file is not readable: ' . $credentialsPath);
    }

    // Validate JSON content
    $jsonContent = file_get_contents($credentialsPath);
    $jsonData = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception('Invalid JSON in credentials file: ' . json_last_error_msg());
    }

    if (!isset($jsonData['type']) || $jsonData['type'] !== 'service_account') {
      throw new \Exception('Invalid service account credentials file');
    }

    try {
      $client->setAuthConfig($credentialsPath);
    } catch (\Exception $e) {
      throw new \Exception('Invalid credentials file: ' . $e->getMessage());
    }

    return $client;
  }

  /**
   * Append data to the Google Spreadsheet
   *
   * @param array $values Array of data to append
   * @param string $sheet Name of the sheet
   * @return bool
   * @throws \Exception
   */
  public function appendToSheet($values, $sheet = 'Sheet1')
  {
    try {
      // Try different range formats
      $possibleRanges = [
        $sheet . '!A:A',
        $sheet . '!A1:Z',
        $sheet,
        'A:A'
      ];

      $body = new ValueRange([
        'values' => $values
      ]);

      $params = [
        'valueInputOption' => 'USER_ENTERED',
      ];

      // Try each range format until one works
      foreach ($possibleRanges as $range) {
        try {
          $result = $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
          );
          return true;
        } catch (\Exception $e) {
          // Continue to next range format
          continue;
        }
      }

      throw new \Exception('Unable to append data to any valid range');
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Get all sheet names in the spreadsheet
   */
  public function getSheetNames()
  {
    try {
      $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
      $sheets = $spreadsheet->getSheets();

      $sheetNames = [];
      foreach ($sheets as $sheet) {
        $sheetNames[] = $sheet->getProperties()->getTitle();
      }

      return $sheetNames;
    } catch (\Exception $e) {
      throw $e;
    }
  }
}
