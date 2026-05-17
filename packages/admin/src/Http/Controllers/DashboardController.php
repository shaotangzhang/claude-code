<?php

declare(strict_types=1);

namespace Acme\Admin\Http\Controllers;

use Acme\Contracts\Module\NavigationRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class DashboardController extends Controller
{
    public function __invoke(NavigationRegistry $nav): View
    {
        return view('acme-admin::dashboard', [
            'navigation' => $nav->for('admin'),
            'brand'      => config('acme.admin.brand', 'Acme'),
        ]);
    }
}
