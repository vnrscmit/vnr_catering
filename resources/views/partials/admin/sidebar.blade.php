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

            @if ($loggedInUser->role == "Super Admin")
            <li class="nav-item {{ request()->route()->named('organizations.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('organizations.index') }}">
                <i class="fa fa-university menu-icon"></i>
                <span class="menu-title">Organization Master</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('locations.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('locations.index') }}">
                <i class="fa fa-map-marker-alt menu-icon"></i>
                <span class="menu-title">Location Master</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('departments.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('departments.index') }}">
                <i class="fa fa-building menu-icon"></i>
                <span class="menu-title">Department Master</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('admin.roles.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.roles.index') }}">
                <i class="fa fa-user-shield menu-icon"></i>
                <span class="menu-title">Role Master</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('admin.users.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.users.index') }}">
                <i class="fa fa-users menu-icon"></i>
                <span class="menu-title">User Master</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('admin.menus.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('admin.menus.index') }}">
                <i class="fa fa-utensils menu-icon"></i>
                <span class="menu-title">Menu Master</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('today-menu.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('today-menu.index') }}">
                <i class="fa fa-utensils menu-icon"></i>
                <span class="menu-title">Daily Menu Master</span>
              </a>
            </li>



            <li class="nav-item {{ request()->route()->named('rate-masters.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('rate-masters.index') }}">
                <i class="fa fa-money-bill-wave menu-icon"></i>
                <span class="menu-title">Rate Master</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('holiday-settings.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('holiday-settings.index') }}">
                <i class="fa fa-calendar-alt menu-icon"></i>
                <span class="menu-title">Holiday Master</span>
              </a>
            </li>
            <li class="nav-item {{ request()->route()->named('company-parameters.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('company-parameters.create') }}">
                <i class="fa fa-cogs menu-icon"></i>
                <span class="menu-title">Canteen Parameter</span>
              </a>
            </li>

            <li class="nav-item {{ request()->route()->named('bill-generate.*') ? 'active-nav' : '' }}">
              <a class="nav-link {{ request()->route()->named('bill-generate.*') ? '' : 'collapsed' }}"
                data-toggle="collapse"
                href="#bill-generate"
                role="button"
                aria-expanded="{{ request()->route()->named('bill-generate.*') ? 'true' : 'false' }}"
                aria-controls="bill-generate">
                <i class="fa fa-file-invoice-dollar menu-icon"></i>
                <span class="menu-title">Bill Generate</span>
                <i class="menu-arrow"></i>
              </a>
              <div class="collapse {{ request()->route()->named('bill-generate.*') ? 'show' : '' }}" id="bill-generate">
                <ul class="nav flex-column sub-menu">
                  <!-- <li class="nav-item">
                    <a class="nav-link {{ request()->route()->named('bill-generate.individual') ? 'active' : '' }}"
                      href="{{ route('bill-generate.individual') }}">
                      <i class="fa fa-user me-2"></i> Individual Settlement
                    </a>
                  </li> -->
                  <li class="nav-item">
                    <a class="nav-link {{ request()->route()->named('bill-generate.monthly') ? 'active' : '' }}"
                      href="{{ route('bill-generate.monthly') }}">
                      <i class="fa fa-calendar me-2"></i> Monthly Bill
                    </a>
                  </li>
                  <!-- Add more sub-menu items if needed -->
                </ul>
              </div>
            </li>

            @endif

            @if ($loggedInUser->role == "Canteen Administrator")
            {{-- Master --}}
            <li class="nav-item {{ 
    request()->route()->named('admin.users.*') ||
    request()->route()->named('admin.menus.*') ||
    request()->route()->named('rate-masters.*') ||
    request()->route()->named('holiday-settings.*') ||
    request()->route()->named('company-parameters.*')
        ? 'active' : '' 
}}">
              <a class="nav-link" data-toggle="collapse" href="#masterMenu"
                aria-expanded="{{ 
            request()->route()->named('admin.users.*') ||
            request()->route()->named('admin.menus.*') ||
            request()->route()->named('rate-masters.*') ||
            request()->route()->named('holiday-settings.*') ||
            request()->route()->named('company-parameters.*')
                ? 'true' : 'false' 
        }}"
                aria-controls="masterMenu">

                <i class="fa fa-database menu-icon"></i>
                <span class="menu-title">Masters</span>
                <i class="menu-arrow"></i>
              </a>

              <div class="collapse {{ 
        request()->route()->named('admin.users.*') ||
        request()->route()->named('admin.menus.*') ||
        request()->route()->named('rate-masters.*') ||
        request()->route()->named('holiday-settings.*') ||
        request()->route()->named('company-parameters.*')
            ? 'show' : '' 
    }}" id="masterMenu">

                <ul class="nav flex-column sub-menu">

                  {{-- User Master --}}
                  <li class="nav-item {{ request()->route()->named('admin.users.*') ? 'active-nav' : '' }}">
                    <a class="nav-link" href="{{ route('admin.users.index') }}">
                      <i class="fa fa-users menu-icon"></i>
                      <span class="menu-title">User Master</span>
                    </a>
                  </li>

                  {{-- Menu Master --}}
                  <li class="nav-item {{ request()->route()->named('admin.menus.*') ? 'active-nav' : '' }}">
                    <a class="nav-link" href="{{ route('admin.menus.index') }}">
                      <i class="fa fa-utensils menu-icon"></i>
                      <span class="menu-title">Menu Master</span>
                    </a>
                  </li>

                  {{-- Daily Menu Master --}}
                  <li class="nav-item {{ request()->route()->named('today-menu.*') ? 'active-nav' : '' }}">
                    <a class="nav-link" href="{{ route('today-menu.index') }}">
                      <i class="fa fa-utensils menu-icon"></i>
                      <span class="menu-title">Daily Menu Master</span>
                    </a>
                  </li>

                  {{-- Rate Master --}}
                  <li class="nav-item {{ request()->route()->named('rate-masters.*') ? 'active-nav' : '' }}">
                    <a class="nav-link" href="{{ route('rate-masters.index') }}">
                      <i class="fa fa-money-bill-wave menu-icon"></i>
                      <span class="menu-title">Rate Master</span>
                    </a>
                  </li>

                  {{-- Holiday Master --}}
                  <li class="nav-item {{ request()->route()->named('holiday-settings.*') ? 'active-nav' : '' }}">
                    <a class="nav-link" href="{{ route('holiday-settings.index') }}">
                      <i class="fa fa-calendar-alt menu-icon"></i>
                      <span class="menu-title">Holiday Master</span>
                    </a>
                  </li>

                  {{-- Canteen Parameter --}}
                  <li class="nav-item {{ request()->route()->named('company-parameters.*') ? 'active-nav' : '' }}">
                    <a class="nav-link" href="{{ route('company-parameters.create') }}">
                      <i class="fa fa-cogs menu-icon"></i>
                      <span class="menu-title">Canteen Parameter</span>
                    </a>
                  </li>

                </ul>
              </div>
            </li>

            {{-- Bill Generate --}}
            {{-- Bill Generate --}}
            <li class="nav-item {{ 
    request()->route()->named('bill-generate.*')
        ? 'active' : '' 
}}">

              <a class="nav-link"
                data-toggle="collapse"
                href="#billGenerateMenu"
                aria-expanded="{{ 
            request()->route()->named('bill-generate.*')
                ? 'true' : 'false' 
        }}"
                aria-controls="billGenerateMenu">

                <i class="fa fa-file-invoice-dollar menu-icon"></i>
                <span class="menu-title">Bill Generate</span>
                <i class="menu-arrow"></i>
              </a>

              <div class="collapse {{ 
        request()->route()->named('bill-generate.*')
            ? 'show' : '' 
    }}" id="billGenerateMenu">

                <ul class="nav flex-column sub-menu">

                  {{-- Individual Settlement --}}
                  <!-- <li class="nav-item {{ 
                request()->route()->named('bill-generate.individual')
                    ? 'active-nav' : '' 
            }}">
                    <a class="nav-link"
                      href="{{ route('bill-generate.individual') }}">

                      <i class="fa fa-user menu-icon"></i>
                      <span class="menu-title">Individual Settlement</span>

                    </a>
                  </li> -->

                  {{-- Monthly Bill --}}
                  <li class="nav-item {{ 
                request()->route()->named('bill-generate.monthly')
                    ? 'active-nav' : '' 
            }}">
                    <a class="nav-link"
                      href="{{ route('bill-generate.monthly') }}">

                      <i class="fa fa-calendar menu-icon"></i>
                      <span class="menu-title">Monthly Bill</span>

                    </a>
                  </li>

                </ul>
              </div>
            </li>
            {{-- Transaction --}}
            <li class="nav-item {{ 
    request()->route()->named('payment.*') ||
    request()->routeIs('feedback.*') ||
    request()->routeIs('security-amount.*')
        ? 'active' : '' 
}}">

              <a class="nav-link"
                data-toggle="collapse"
                href="#transactionMenu"
                aria-expanded="{{ 
            request()->route()->named('payment.*') ||
            request()->routeIs('feedback.*') ||
            request()->routeIs('security-amount.*')
                ? 'true' : 'false' 
        }}"
                aria-controls="transactionMenu">

                <i class="fa fa-exchange-alt menu-icon"></i>
                <span class="menu-title">Transactions</span>
                <i class="menu-arrow"></i>
              </a>

              <div class="collapse {{ 
        request()->route()->named('payment.*') ||
        request()->routeIs('feedback.*') ||
         request()->routeIs('opening-amount.*') ||
        request()->routeIs('security-amount.*')
            ? 'show' : '' 
    }}" id="transactionMenu">

                <ul class="nav flex-column sub-menu">

                  {{-- Bill Payment --}}


                  {{-- Security Amount --}}
                  <li class="nav-item {{ 
                request()->routeIs('security-amount.*')
                    ? 'active-nav' : '' 
            }}">
                    <a class="nav-link" href="{{ route('security-amount.create') }}">
                      <i class="fa fa-shield-alt menu-icon"></i>
                      <span class="menu-title">Security Amount</span>
                    </a>
                  </li>

                  {{-- Opening Amount --}}
                  <li class="nav-item {{ 
    request()->routeIs('opening-amount.*')
        ? 'active-nav' : '' 
}}">
                    <a class="nav-link" href="{{ route('opening-amount.create') }}">
                      <i class="fa fa-balance-scale menu-icon"></i>
                      <span class="menu-title">Opening Amount</span>
                    </a>
                  </li>

                  {{-- Feedback --}}
                  <li class="nav-item {{ 
                request()->routeIs('feedback.*')
                    ? 'active-nav' : '' 
            }}">
                    <a class="nav-link" href="{{ route('feedback.index') }}">
                      <i class="fa fa-comment-dots menu-icon"></i>
                      <span class="menu-title">Feedback</span>
                    </a>
                  </li>

                </ul>
              </div>
            </li>

            {{-- Report --}}
            <li class="nav-item {{ 
    request()->routeIs('report.*')
        ? 'active' : '' 
}}">

              <a class="nav-link"
                data-toggle="collapse"
                href="#reportMenu"
                aria-expanded="{{ 
            request()->routeIs('report.*')
                ? 'true' : 'false' 
        }}"
                aria-controls="reportMenu">

                <i class="fa fa-file-alt menu-icon"></i>
                <span class="menu-title">Report</span>
                <i class="menu-arrow"></i>
              </a>

              <div class="collapse {{ 
        request()->routeIs('report.*')
            ? 'show' : '' 
    }}" id="reportMenu">

                <ul class="nav flex-column sub-menu">

                  {{-- Daily Attendance Report --}}
                  <li class="nav-item {{ 
                request()->routeIs('report.daily')
                    ? 'active-nav' : '' 
            }}">
                    <a class="nav-link" href="{{ route('report.daily') }}">

                      <i class="fa fa-user menu-icon"></i>
                      <span class="menu-title">Daily Attendance</span>

                    </a>
                  </li>

                  {{-- Periodical Attendance Report --}}
                  <!-- <li class="nav-item {{ 
                request()->routeIs('report.periodical')
                    ? 'active-nav' : '' 
            }}">
                    <a class="nav-link" href="{{ route('report.periodical') }}">

                      <i class="fa fa-calendar menu-icon"></i>
                      <span class="menu-title">Periodical Attendance Report</span>

                    </a>
                  </li> -->

                  {{-- Guest Report --}}
                  <li class="nav-item {{ 
                request()->routeIs('report.guest')
                    ? 'active-nav' : '' 
            }}">
                    <a class="nav-link" href="{{ route('report.guest') }}">

                      <i class="fa fa-users menu-icon"></i>
                      <span class="menu-title">Guest</span>

                    </a>
                  </li>
                  <li class="nav-item {{ 
    request()->routeIs('report.ledger')
        ? 'active-nav' : '' 
}}">
                    <a class="nav-link" href="{{ route('report.ledger') }}">
                      <i class="fa fa-book menu-icon"></i>
                      <span class="menu-title">User Ledger</span>
                    </a>
                  </li>

                  <li class="nav-item {{ 
    request()->routeIs('report.collection_expenses')
        ? 'active-nav' : '' 
}}">
                    <a class="nav-link" href="{{ route('report.collection_expenses') }}">
                      <i class="fa fa-book-open menu-icon"></i>
                      <span class="menu-title">Collection & Expense</span>
                    </a>
                  </li>

                </ul>
              </div>
            </li>
            <!-- <li class="nav-item {{ request()->route()->named('bill-generate.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('bill-generate.index') }}">
                <i class="fa fa-file-invoice-dollar menu-icon"></i>
                <span class="menu-title">Bill Generate</span>
              </a>
            </li> -->
            @endif

            @if ($loggedInUser->role == "Canteen Incharge")
            <li class="nav-item {{ request()->route()->named('today-menu.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('today-menu.index') }}">
                <i class="fa fa-utensils menu-icon"></i>
                <span class="menu-title">Daily Menu Master</span>
              </a>
            </li>
            @endif

            @if ($loggedInUser->role == "Member" || $loggedInUser->role == "Non Member")
            <li class="nav-item {{ request()->routeIs('calendar.index') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('calendar.index') }}">
                <i class="fa fa-calendar-alt menu-icon"></i>
                <span class="menu-title">Calendar</span>
              </a>
            </li>

            <li class="nav-item {{ request()->routeIs('feedback.*') ? 'active-nav' : '' }}">
              <a class="nav-link" href="{{ route('feedback.index') }}">
                <i class="fa fa-comment-dots menu-icon"></i>
                <span class="menu-title">Feedback</span>
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
            <li class="nav-item">
              <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="fa fa-power-off menu-icon"></i>
                <span class="menu-title">Logout</span>
              </a>
            </li>

          </ul>

        </nav>