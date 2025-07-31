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
  }

  public function getClient()
  {
    $client = new Client();
    $client->setApplicationName('Laravel Google Sheets Integration');
    $client->setScopes(Sheets::SPREADSHEETS);
    $client->setAuthConfig(storage_path('/userdataspreadsheet-36498ef55ca4.json'));
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
