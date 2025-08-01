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
    $client = new \Google\Client();
    $client->setApplicationName('Laravel Google Sheets Integration');
    $client->setScopes(\Google\Service\Sheets::SPREADSHEETS);

    // Get JSON from env and decode
    $json = env('GOOGLE_SERVICE_ACCOUNT_JSON');
    if (!$json) {
      throw new \Exception('GOOGLE_SERVICE_ACCOUNT_JSON not set in .env');
    }

    $jsonData = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception('Invalid JSON in GOOGLE_SERVICE_ACCOUNT_JSON: ' . json_last_error_msg());
    }

    try {
      $client->setAuthConfig($jsonData);
    } catch (\Exception $e) {
      throw new \Exception('Invalid credentials: ' . $e->getMessage());
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
