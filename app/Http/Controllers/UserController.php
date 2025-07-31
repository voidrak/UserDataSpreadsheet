<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleSheetsService;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    public function store(Request $request)
    {
        try {
            // Validate the incoming request with new field names
            $validatedData = $request->validate([
                'vehicle_owner' => 'required|string|max:255',           // ፈቃድ ጠያቂው ተሽከርካሪ ባለንብረት
                'vehicle_institution' => 'required|string|max:255',     // ፈቃድ ጠያቂው ተሽከርካሪ ተቋም()
                'vehicle_type' => 'required|string|max:255',            // የተሽከርካሪው አይነት
                'vehicle_plate_number' => 'required|string|max:255',    // የተሽከርካሪው ሠሌዳ ቁጥር
                'permit_reason' => 'required|string|max:255',           // ፈቃዱ የተሰጠበት ምክንያት
                'permit_duration' => 'required|string|max:255',         // ፈቃዱ የሚያገለግልበት ጊዜ
                'receipt_number' => 'required|string|max:255',          // የአገልግሎት ክፍያ የተፈፀመበት ደረሰኝ ቁጥር
            ]);

            $googleSheetsService = new GoogleSheetsService();

            // Get available sheet names first
            $sheetNames = $googleSheetsService->getSheetNames();
            $firstSheet = $sheetNames[0] ?? 'Sheet1';

            // Prepare data from user input with Ethiopian column headers
            $result = $googleSheetsService->appendToSheet([
                [
                    now()->format('Y-m-d H:i:s'),                      // Timestamp
                    $validatedData['vehicle_owner'],                   // ፈቃድ ጠያቂው ተሽከርካሪ ባለንብረት
                    $validatedData['vehicle_institution'],             // ፈቃድ ጠያቂው ተሽከርካሪ ተቋም()
                    $validatedData['vehicle_type'],                    // የተሽከርካሪው አይነት
                    $validatedData['vehicle_plate_number'],            // የተሽከርካሪው ሠሌዳ ቁጥር
                    $validatedData['permit_reason'],                   // ፈቃዱ የተሰጠበት ምክንያት
                    $validatedData['permit_duration'],                 // ፈቃዱ የሚያገለግልበት ጊዜ
                    $validatedData['receipt_number']                   // የአገልግሎት ክፍያ የተፈፀመበት ደረሰኝ ቁጥር
                ]
            ], $firstSheet);

            return response()->json([
                'success' => true,
                'message' => 'Data appended successfully to ' . $firstSheet,
                'data' => $validatedData,
                'available_sheets' => $sheetNames
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
