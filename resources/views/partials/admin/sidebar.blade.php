        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item">
              <div class="d-flex sidebar-profile">
                <div class="sidebar-profile-image">
                  <img src=" {{ $loggedInUser && $loggedInUser->profile_picture ? asset('storage/profile-picture/' . $loggedInUser->profile_picture) : asset('assets/images/user-icon.png') }}" alt="image">
                </div>
                <div class="sidebar-profile-name">
                  <p class="sidebar-name">
                    {{ $loggedInUser->first_name }}
                  </p>
                  <p class="sidebar-designation">
                    {{ $loggedInUser->role }}
                  </p>
                </div>
              </div>
            </li>

            <li class="nav-item {{ request()->route()->named('admin.dashboard') ? 'active-nav' : '' }} ">
              <a class="nav-link" href="{{ route('admin.dashboard') }}">
                <i class="fa fa-desktop menu-icon"></i>
                <span class="menu-title">Dashboard</span>
              </a>
            </li>



            <!-- <li class="nav-item {{ request()->route()->named('admin.pos.index') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.pos.index') }}">
                <i class="fa fa-shopping-cart menu-icon" ></i>
                  <span class="menu-title">Point of Sale</span>
              </a>
          </li>
          
          
      
          <li class="nav-item {{ Request::is('admin/order*') ? 'active-nav' : '' }}">
            <a class="nav-link" href="{{ route('admin.orders.index') }}">
                <i class="fa fa-file menu-icon"></i>
                <span class="menu-title">Manage Orders</span>
            </a>
        </li>
        <li class="nav-item {{ request()->route()->named('admin.table-bookings') ? 'active-nav' : '' }}">
          <a class="nav-link" href="{{ route('admin.table-bookings') }}">
              <i class="fa fa-folder-open menu-icon"></i>
              <span class="menu-title">Manage Bookings</span>
          </a>
        </li>        
        <li class="nav-item {{ Request::is('admin/blog*') ? 'active-nav' : '' }}">
            <a class="nav-link" href="{{ route('admin.blog.index') }}">
                <i class="far fa-newspaper menu-icon"></i>
                <span class="menu-title">Manage Blog</span>
            </a>
        </li>
         -->

            @if ($loggedInUser->role == "Super Admin")

            <li class="nav-item {{ request()->route()->named('locations.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('locations.index') }}">
                <i class="fa fa-map-marker-alt menu-icon"></i>
                <span class="menu-title">Locations</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('departments.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('departments.index') }}">
                <i class="fa fa-building menu-icon"></i>
                <span class="menu-title">Departments</span>
              </a>
            </li>



            <li class="nav-item {{ request()->route()->named('admin.roles.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.roles.index') }}">
                <i class="fa fa-user-shield menu-icon"></i>
                <span class="menu-title">Master Roles</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('admin.users.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.users.index') }}">
                <i class="fa fa-users menu-icon"></i>
                <span class="menu-title">Master Users</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('admin.menus.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.menus.index') }}">
                <i class="fa fa-utensils menu-icon"></i>
                <span class="menu-title">Mess Menu</span>
              </a>
            </li>

                <li class="nav-item {{ request()->route()->named('admin.menus.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.menus.index') }}">
                <i class="fa fa-utensils menu-icon"></i>
                <span class="menu-title">Daily Menu</span>
              </a>
            </li>
            @endif

            @if ($loggedInUser->role == "Canteen Incharge")
            <li class="nav-item {{ request()->route()->named('admin.menus.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.menus.index') }}">
                <i class="fa fa-utensils menu-icon"></i>
                <span class="menu-title">Mess Menu</span>
              </a>
            </li>
            <li class="nav-item {{ request()->route()->named('admin.menus.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.menus.index') }}">
                <i class="fa fa-utensils menu-icon"></i>
                <span class="menu-title">Daily Menu</span>
              </a>
            </li>

            @endif

            <li class="nav-item {{ request()->route()->named('admin.view.myprofile') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.view.myprofile') }}">
                <i class="fa fa-user menu-icon"></i>
                <span class="menu-title">My Profile</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('change.password.form') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('change.password.form') }}">
                <i class="fa fa-lock menu-icon"></i>
                <span class="menu-title">Change Password</span>
              </a>
            </li>


            <!-- <li class="nav-item">
                <a target="_blank" class="nav-link" href="{{ route('home') }}">
                  <i class="fa fa-globe menu-icon"></i>
                  <span class="menu-title">Main Website</span>
                </a>
              </li> -->

            <li class="nav-item">
              <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="fa fa-power-off menu-icon"></i>
                <span class="menu-title">Logout</span>
              </a>
            </li>

          </ul>

        </nav>