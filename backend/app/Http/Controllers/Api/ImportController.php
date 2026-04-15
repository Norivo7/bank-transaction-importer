<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Services\Imports\TransactionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImportController extends Controller
{
    public function __construct(
        private readonly TransactionImportService $transactionImportService,
    ) {
    }

    public function index(): JsonResponse
    {
        $imports = Import::query()
            ->latest()
            ->get();

        return response()->json($imports);
    }

    public function store(Request $request): JsonResponse
    {
        $uploadedFile = $request->file('file');

        if (!$uploadedFile instanceof UploadedFile) {
            return response()->json([
                'message' => 'File is required.',
            ], 422);
        }

        $extension = mb_strtolower($uploadedFile->getClientOriginalExtension());

        if (!in_array($extension, ['csv', 'json', 'xml'], true)) {
            return response()->json([
                'message' => 'Unsupported file format.',
            ], 422);
        }

        $import = $this->transactionImportService->import($uploadedFile);

        return response()->json($import, 201);
    }

    public function show(int $id): JsonResponse
    {
        $import = Import::query()
            ->with('logs')
            ->findOrFail($id);

        return response()->json($import);
    }
}
