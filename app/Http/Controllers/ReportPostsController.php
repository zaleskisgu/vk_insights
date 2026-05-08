<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportPostsRequest;
use App\Services\Posts\ReportPostsService;
use Illuminate\Http\JsonResponse;

class ReportPostsController extends Controller
{
    public function index(ReportPostsRequest $request, ReportPostsService $posts): JsonResponse
    {
        $validated = $request->validated();

        $payload = $posts->listPage(
            $validated['group'],
            $request->date('from'),
            $request->date('to'),
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 25),
            $validated['sort'] ?? 'date',
            $validated['order'] ?? 'desc',
            $validated['q'] ?? null,
            $validated['type'] ?? 'all',
        );

        return response()->json($payload);
    }
}
