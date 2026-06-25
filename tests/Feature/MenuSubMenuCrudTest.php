<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuSubMenuCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_and_submenu_can_be_created_updated_and_deleted(): void
    {
        $this->withoutMiddleware();

        $createMenuResponse = $this->post(route('admin.menus.store'), [
            'name' => 'Main Menu',
            'status' => 'active',
        ]);

        $createMenuResponse->assertRedirect();
        $this->assertDatabaseHas('menus', ['name' => 'Main Menu', 'status' => 'active']);

        $menu = \App\Models\Menu::where('name', 'Main Menu')->firstOrFail();

        $createSubMenuResponse = $this->post(route('admin.submenus.store'), [
            'menu_id' => $menu->id,
            'name' => 'Starter',
            'status' => 'active',
        ]);

        $createSubMenuResponse->assertRedirect();
        $this->assertDatabaseHas('sub_menus', ['menu_id' => $menu->id, 'name' => 'Starter', 'status' => 'active']);

        $submenu = $menu->subMenus()->firstOrFail();

        $updateResponse = $this->patch(route('admin.submenus.update', $submenu->id), [
            'menu_id' => $menu->id,
            'name' => 'Updated Starter',
            'status' => 'inactive',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('sub_menus', ['id' => $submenu->id, 'name' => 'Updated Starter', 'status' => 'inactive']);

        $deleteResponse = $this->delete(route('admin.submenus.destroy', $submenu->id));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('sub_menus', ['id' => $submenu->id]);

        $deleteMenuResponse = $this->delete(route('admin.menus.destroy', $menu->id));
        $deleteMenuResponse->assertRedirect();
        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }
}
