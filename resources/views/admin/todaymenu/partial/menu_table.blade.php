<table class="table table-bordered">
    <thead class="table-light">
        <tr>
            <th width="30%">Menu Section</th>
            <th width="70%">Menu Items</th>
        </tr>
    </thead>
    <tbody>
        @foreach($menus as $menu)
        <tr>
            <td>
                <strong>{{ $menu->name }}</strong>
                <input type="hidden" name="menu_id[]" value="{{ $menu->id }}">
            </td>
            <td>
                <div class="row">
                    @foreach($menu->submenus as $submenu)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                name="submenu_id[{{ $menu->id }}][]"
                                value="{{ $submenu->id }}"
                                id="submenu_{{ $menu->id }}_{{ $submenu->id }}"
                                class="form-check-input submenu-checkbox"
                                data-menu-id="{{ $menu->id }}"
                                data-submenu-id="{{ $submenu->id }}">
                            <label
                                class="form-check-label"
                                for="submenu_{{ $menu->id }}_{{ $submenu->id }}">
                                {{ $submenu->name }}
                                @if($submenu->special_flag == 1)
                                <i class="fa fa-star text-warning" title="Special"></i>
                                @endif
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>