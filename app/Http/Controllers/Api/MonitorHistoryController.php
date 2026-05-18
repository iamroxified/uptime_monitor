<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MonitorCheckHistoryResource;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MonitorHistoryController extends Controller
{
    public function index(Monitor $monitor, Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $paginator = $monitor
            ->histories()
            ->orderByDesc('checked_at')
            ->paginate($perPage);

        return response()->json([
            'data' => MonitorCheckHistoryResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
