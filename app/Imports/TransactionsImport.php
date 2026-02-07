<?php

namespace App\Imports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class TransactionsImport implements ToModel, WithHeadingRow
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Debuging what keys are available if needed.
        // Log::info($row);

        // Expected keys based on slugification of headers:
        // 'other_transaction_details_upi_id_or_ac_no' for 'Other Transaction Details (UPI ID or A/c No)'
        // 'your_account' for 'Your Account'
        // 'upi_ref_no' for 'UPI Ref No.'

        return new Transaction([
            'user_id' => $this->userId,
            'date' => $this->transformDate($row['date'] ?? null),
            'time' => $row['time'] ?? null,
            'transaction_details' => $row['transaction_details'] ?? null,
            'other_transaction_details' => $row['other_transaction_details_upi_id_or_ac_no'] ?? null,
            'account' => $row['your_account'] ?? null, // Mapped from 'Your Account'
            'amount' => $row['amount'] ?? 0,
            'ref_no' => $row['upi_ref_no'] ?? null, // Mapped from 'UPI Ref No.'
            'order_id' => $row['order_id'] ?? null,
            'remarks' => $row['remarks'] ?? null,
            'tag' => $row['tags'] ?? null, // Mapped from 'Tags'
            'comment' => $row['comment'] ?? null,
        ]);
    }

    private function transformDate($value)
    {
        if (!$value)
            return null;
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value; // Fallback if already a string or invalid
        }
    }
}
