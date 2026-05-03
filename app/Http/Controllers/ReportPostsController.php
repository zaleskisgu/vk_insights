<?php

namespace App\Http\Controllers;

use App\Services\Report\ReportPostsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportPostsController extends Controller
{
    public function index(Request $request, ReportPostsService $posts): JsonResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'max:512'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:10', 'max:100'],
            'sort' => ['sometimes', Rule::in(['date', 'likes', 'comments', 'reposts', 'engagement', 'type', 'text'])],
            'order' => ['sometimes', Rule::in(['asc', 'desc'])],
            'q' => ['nullable', 'string', 'max:200'],
            'type' => ['sometimes', Rule::in(['all', 'photo', 'multi', 'video', 'text', 'link'])],
        ]);

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
