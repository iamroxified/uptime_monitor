<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MonitorController extends Controller
{
    public function store(StoreMonitorRequest $request): JsonResponse
    {
        $data = $request->validated();

        $monitor = Monitor::create([
            'url' => $data['url'],
            'check_interval' => $data['check_interval'] ?? 5,
            'threshold' => $data['threshold'] ?? 3,
            'status' => 'pending',
        ]);

        return (new MonitorResource($monitor))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function index(): AnonymousResourceCollection
    {
        return MonitorResource::collection(
            Monitor::query()->latest()->get()
        );
    }
}
