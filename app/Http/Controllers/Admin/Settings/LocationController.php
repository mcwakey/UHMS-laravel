<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\CountryCity;
use App\Models\CountryRegion;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function regions(Request $request)
    {
        $countryCode = strtoupper($request->query('country', config('patient_reference.setup_country_code', 'GH')));

        return response()->json(
            CountryRegion::query()
                ->active()
                ->whereHas('country', fn ($query) => $query->active()->where('iso2', $countryCode))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($region) => ['id' => $region->id, 'value' => $region->name, 'label' => $region->name])
                ->values()
        );
    }

    public function cities(Request $request)
    {
        $countryCode = strtoupper($request->query('country', config('patient_reference.setup_country_code', 'GH')));

        $region = CountryRegion::query()
            ->active()
            ->where('name', $request->query('region'))
            ->whereHas('country', fn ($query) => $query->active()->where('iso2', $countryCode))
            ->first();

        if (! $region) {
            return response()->json([]);
        }

        return response()->json(
            $region->cities()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($city) => ['id' => $city->id, 'value' => $city->name, 'label' => $city->name])
                ->values()
        );
    }

    public function towns(Request $request)
    {
        $countryCode = strtoupper($request->query('country', config('patient_reference.setup_country_code', 'GH')));

        $cityQuery = CountryCity::query()
            ->active()
            ->where('name', $request->query('city'))
            ->whereHas('region.country', fn ($query) => $query->active()->where('iso2', $countryCode));

        if ($request->filled('region')) {
            $cityQuery->whereHas('region', fn ($query) => $query
                ->active()
                ->where('name', $request->query('region')));
        }

        $city = $cityQuery->first();

        if (! $city) {
            return response()->json([]);
        }

        return response()->json(
            $city->towns()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($town) => ['id' => $town->id, 'value' => $town->name, 'label' => $town->name])
                ->values()
        );
    }
}
