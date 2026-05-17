<?php

declare(strict_types=1);

namespace Acme\Search\Http\Controllers;

use Acme\Search\Drivers\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class SearchController extends Controller
{
    public function show(Request $request, Driver $driver): Response
    {
        $filters = array_filter([
            'q'        => (string) $request->query('q', ''),
            'locale'   => app()->getLocale(),
            'category' => $request->query('category'),
            'brand'    => $request->query('brand'),
            'min_price_cents' => $request->query('min_price_cents'),
            'max_price_cents' => $request->query('max_price_cents'),
        ], fn ($v) => $v !== '' && $v !== null);

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = (int) config('acme.search.per_page', 20);

        $result = $driver->search($filters, $page, $perPage);

        return new Response((string) view('acme-search::results', [
            'filters' => $filters,
            'result'  => $result,
            'page'    => $page,
            'perPage' => $perPage,
        ])->render());
    }
}
