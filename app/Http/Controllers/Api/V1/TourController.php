<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TourListRequest;
use App\Http\Resources\TourResource;
use App\Models\Tour;
use App\Models\Travel;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function index(Travel $travel, TourListRequest $request)
    {
        $tours = $travel->tours()
            ->when($request->priceFrom, function($query) use($request) {
                $query->where('price', '>=', $request->priceFrom * 100);
            })
            ->when($request->priceTo, function($query) use($request) {
                $query->where('price', '<=', $request->priceTo * 100);
            })
            ->when($request->dateFrom, function($query) use($request) {
                $query->where('start_date', '>=', $request->dateFrom);
            })
            ->when($request->dateTo, function($query) use($request) {
                $query->where('start_date', '<=', $request->dateTo);
            })
            ->when($request->sortBy && $request->sortOrder, function($query) use($request) {
                $query->orderBy($request->sortBy, $request->sortOrder);
            })
            ->orderBy('start_date')
            ->paginate();
          
        return TourResource::collection($tours);    
    }
}
