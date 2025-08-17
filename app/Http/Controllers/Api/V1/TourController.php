<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToursListRequest;
use App\Http\Resources\TourResource;
use App\Models\Travel;

class TourController extends Controller
{
    public function index(Travel $travel, ToursListRequest $request)
    {
        $request->headers->set('Accept', 'application/json');

        $tours = $travel->tours()
            // request queries
            // price filter
            ->when($request->priceFrom, function ($query) use ($request) {
                $query->where($request->resolveColumnAlias('price'), '>=', $request->priceFrom * 100);
            })
            ->when($request->priceTo, function ($query) use ($request) {
                $query->where($request->resolveColumnAlias('price'), '<=', $request->priceTo * 100);
            })

            // date filter
            ->when($request->dateFrom, function ($query) use ($request) {
                $query->where('starting_date', '>=', $request->dateFrom);
            })
            ->when($request->dateTo, function ($query) use ($request) {
                $query->where('starting_date', '<=', $request->dateTo);
            })

            // sort
            ->when($request->sortBy && $request->sortOrder, function ($query) use ($request) {
                $query->orderBy($request->resolveColumnAlias($request->sortBy), $request->sortOrder);
            })

            ->orderBy('starting_date')
            ->paginate();

        return TourResource::collection($tours);
    }
}
