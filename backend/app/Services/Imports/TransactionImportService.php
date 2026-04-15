<?php

declare(strict_types=1);

namespace App\Services\Imports;

use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use App\Services\Parsers\TransactionParserResolver;
use App\Services\Validation\TransactionRecordValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final readonly class TransactionImportService
{
    public function __construct(
        private TransactionParserResolver $transactionParserResolver,
        private TransactionRecordValidator $transactionRecordValidator,
    ) {
    }

    public function import(UploadedFile $file): Import
    {
        $import = Import::query()->create([
            'file_name' => $file->getClientOriginalName(),
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'status' => 'failed',
        ]);

        try {
            $records = $this->parseFile($file);
            $summary = $this->processRecords($import, $records);

            $import->update([
                'total_records' => $summary['total_records'],
                'successful_records' => $summary['successful_records'],
                'failed_records' => $summary['failed_records'],
                'status' => $this->resolveStatus(
                    $summary['total_records'],
                    $summary['successful_records'],
                    $summary['failed_records'],
                ),
            ]);
        } catch (Throwable $throwable) {
            ImportLog::query()->create([
                'import_id' => $import->id,
                'transaction_id' => null,
                'error_message' => $throwable->getMessage(),
            ]);

            $import->update([
                'total_records' => 0,
                'successful_records' => 0,
                'failed_records' => 0,
                'status' => 'failed',
            ]);
        }

        return $import->fresh('logs');
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseFile(UploadedFile $file): array
    {
        $extension = $this->resolveExtension($file);
        $parser = $this->transactionParserResolver->resolve($extension);
        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException('Uploaded file path could not be resolved.');
        }

        return $parser->parse($path);
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        if ($extension === '') {
            throw new RuntimeException('Uploaded file has no extension.');
        }

        return $extension;
    }

    /**
     * @param array<int, array<string, string|null>> $records
     * @return array{
     *     total_records: int,
     *     successful_records: int,
     *     failed_records: int
     * }
     */
    private function processRecords(Import $import, array $records): array
    {
        $totalRecords = count($records);
        $successfulRecords = 0;
        $failedRecords = 0;

        foreach ($records as $record) {
            $wasSaved = $this->processSingleRecord($import, $record);

            if ($wasSaved) {
                $successfulRecords++;

                continue;
            }

            $failedRecords++;
        }

        return [
            'total_records' => $totalRecords,
            'successful_records' => $successfulRecords,
            'failed_records' => $failedRecords,
        ];
    }

    /**
     * @param array<string, string|null> $record
     */
    private function processSingleRecord(Import $import, array $record): bool
    {
        $normalizedRecord = $this->transactionRecordValidator->normalizeRecord($record);
        $validationResult = $this->transactionRecordValidator->validate($record);

        if ($validationResult['is_valid'] === false) {
            $this->createImportLog(
                $import,
                $normalizedRecord['transaction_id'],
                $validationResult['errors'],
            );

            return false;
        }

        DB::transaction(static function () use ($normalizedRecord): void {
            Transaction::query()->create([
                'transaction_id' => $normalizedRecord['transaction_id'],
                'account_number' => $normalizedRecord['account_number'],
                'transaction_date' => $normalizedRecord['transaction_date'],
                'amount' => $normalizedRecord['amount'],
                'currency' => $normalizedRecord['currency'],
            ]);
        });

        return true;
    }

    /**
     * @param array<int, string> $errors
     */
    private function createImportLog(Import $import, ?string $transactionId, array $errors): void
    {
        ImportLog::query()->create([
            'import_id' => $import->id,
            'transaction_id' => $transactionId,
            'error_message' => implode('; ', $errors),
        ]);
    }

    private function resolveStatus(int $totalRecords, int $successfulRecords, int $failedRecords): string
    {
        if ($totalRecords === 0) {
            return 'failed';
        }

        if ($successfulRecords === $totalRecords) {
            return 'success';
        }

        if ($successfulRecords > 0 && $failedRecords > 0) {
            return 'partial';
        }

        return 'failed';
    }
}
