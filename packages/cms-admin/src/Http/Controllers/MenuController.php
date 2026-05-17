<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Http\Controllers;

use Acme\CmsAdmin\Models\Menu;
use Acme\CmsAdmin\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class MenuController extends Controller
{
    public function index(): Response
    {
        $this->authorize('cms.menu.manage');

        $menus = Menu::with(['items.children'])->orderBy('key')->get();

        return new Response((string) view('acme-cms-admin::menus.index', compact('menus'))->render());
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $this->authorize('cms.menu.manage');

        $items = (array) $request->input('items', []);

        DB::transaction(function () use ($menu, $items): void {
            MenuItem::where('menu_id', $menu->id)->delete();
            $this->ingestTree($menu->id, null, $items);
        });

        return back()->with('status', 'Menu saved.');
    }

    /** @param list<array<string,mixed>> $items */
    private function ingestTree(string $menuId, ?string $parentId, array $items): void
    {
        foreach ($items as $i => $row) {
            $item = MenuItem::create([
                'menu_id'    => $menuId,
                'parent_id'  => $parentId,
                'label'      => (string) ($row['label'] ?? ''),
                'route'      => $row['route'] ?? null,
                'url'        => $row['url']   ?? null,
                'position'   => $i,
                'attrs_json' => $row['attrs'] ?? null,
            ]);
            if (! empty($row['children']) && is_array($row['children'])) {
                $this->ingestTree($menuId, $item->id, $row['children']);
            }
        }
    }
}
