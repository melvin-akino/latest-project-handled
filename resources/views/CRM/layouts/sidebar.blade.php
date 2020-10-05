<aside class="main-sidebar">
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left image">
                <img src="{{ asset('CRM/Capital7-1.0.0/img/user2-160x160.jpg') }}" class="img-circle" alt="User Image">
            </div>

            <div class="pull-left info">
                <p>{{ Auth::guard('crm')->user()->first_name }} {{ Auth::guard('crm')->user()->last_name }}</p>
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
        </div>

        <ul class="sidebar-menu" data-widget="tree">
            <li class="header">MENU</li>
           
                <li class="@isset($dashboard_menu) active @endisset">
                    <a href="/admin/dashboard">
                        <i class="fa fa-dashboard" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="@isset($providers_menu) active @endisset">
                    <a href="/admin/providers">
                        <i class="fa fa-dashboard" aria-hidden="true"></i>
                        <span>Providers</span>
                    </a>
                </li>
                <li class="@isset($system_configuration_menu) active @endisset">
                    <a href="/admin/system_configurations">
                        <i class="fa fa-dashboard" aria-hidden="true"></i>
                        <span>System Configurations</span>
                    </a>
                </li>
                <li class="@isset($accounts_menu) active @endisset">
                    <a href="/admin/accounts">
                        <i class="fa fa-user" aria-hidden="true"></i>
                        <span>Accounts</span>
                    </a>
                </li>
            
                <li class="treeview @isset($wallet_menu) active menu-open @endisset" id="wallet-treeview">
                    <a href="javascript:void(0);">
                        <i class="fa fa-id-card-o" aria-hidden="true"></i>
                        <span>Wallet</span>
                        <i class="fa fa-angle-left pull-right"></i>
                    </a>

                    <ul class="treeview-menu">
                       
                        <li class=" @isset($currency_menu) active @endisset ">
                            <a href="/admin/wallet/currencies">
                                <i class="fa fa-usd" aria-hidden="true"></i>
                                <span>Currency</span>
                            </a>
                        </li>
                     

                       
                        <li class="@isset($exchange_rate_menu) active @endisset">
                            <a href="/admin/wallet/exchange_rates">
                                <i class="fa fa-exchange" aria-hidden="true"></i>
                                <span>Exchange Rate</span>
                            </a>
                        </li>
                        

                       
                        <li class=" @isset($deposit_menu) active @endisset ">
                            <a href="/admin/wallet/transfer?type=Deposit">
                                <i class="fa fa-share-square-o" aria-hidden="true"></i>
                                <span>Deposit</span>
                            </a>
                        </li>
                    

                       
                        <li class=" @isset($withdraw_menu) active @endisset ">
                            <a href="/admin/wallet/transfer?type=Withdraw">
                                <i class="fa fa-share-square-o" aria-hidden="true"></i>
                                <span>Withdraw</span>
                            </a>
                        </li>
                     
                    </ul>
                </li>

                <li class="treeview @isset($wallet_menu) active menu-open @endisset" id="wallet-treeview">
                    <a href="javascript:void(0);">
                        <i class="fa fa-id-card-o" aria-hidden="true"></i>
                        <span>Error Messaging</span>
                        <i class="fa fa-angle-left pull-right"></i>
                    </a>

                    <ul class="treeview-menu">
                                               
                        <li class="@isset($exchange_rate_menu) active @endisset">
                            <a href="/admin/error_messages">
                                <i class="fa fa-exchange" aria-hidden="true"></i>
                                <span>General Error Messages</span>
                            </a>
                        </li>
                        <li class=" @isset($currency_menu) active @endisset ">
                            <a href="/admin/message/index">
                                <i class="fa fa-usd" aria-hidden="true"></i>
                                <span>Provider Error</span>
                            </a>
                        </li>
                     


                        

                       
                        
                     
                    </ul>
                </li>
          

            {{--
            @if(Auth::guard('crm')->user()->canAccess('view', 'roles.index') || Auth::guard('crm')->user()->canAccess('view', 'permissions.index') || Auth::guard('crm')->user()->canAccess('view', 'assignuser.index'))
                <li class="treeview @isset($security_menu) active menu-open @endisset" id="security-treeview">
                    <a href="javascript:void(0);">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                        <span>Security</span>
                        <i class="fa fa-angle-left pull-right"></i>
                    </a>

                    <ul class="treeview-menu">
                        @usercan('view', 'roles.index')
                        <li class="@isset($roles_menu) active @endisset">
                            <a href="/admin/security/roles">
                                <i class="fa fa-cogs" aria-hidden="true"></i>
                                <span>Modules</span>
                            </a>
                        </li>
                        @endusercan

                        @usercan('view', 'permissions.index')
                        <li class="@isset($permission_menu) active @endisset">
                            <a href="/admin/security/permissions">
                                <i class="fa fa-user-secret" aria-hidden="true"></i>
                                <span>Roles</span>
                            </a>
                        </li>
                        @endusercan

                        @usercan('view', 'assignuser.index')
                        <li class="@isset($assignuser_menu) active @endisset">
                            <a href="/admin/security/assignuser">
                                <i class="fa fa-user-o" aria-hidden="true"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        @endusercan
                    </ul>
                </li>
                <li class="treeview @isset($masterlist_menu) active menu-open @endisset" id="security-treeview">
                    <a href="javascript:void(0);">
                        <i class="fa fa-list-alt" aria-hidden="true"></i>
                        <span>Masterlist</span>
                        <i class="fa fa-angle-left pull-right"></i>
                    </a>

                    <ul class="treeview-menu">
                        <li class="@isset($match_menu) active @endisset">
                            <a href="{{ route('crm.masterlist.match') }}">
                                <i class="fa fa-random" aria-hidden="true"></i>
                                <span>Match</span>
                            </a>
                        </li>

                        <li class="@isset($match_early_menu) active @endisset">
                            <a href="{{ route('crm.masterlist.match.early') }}">
                                <i class="fa fa-arrows-h" aria-hidden="true"></i>
                                <span>Batch Matching</span>
                            </a>
                        </li>

                        <li class="@isset($providers_menu) active @endisset" style="display: none;">
                            <a href="{{ route('crm.masterlist.providers') }}">
                                <i class="fa fa-address-book-o" aria-hidden="true"></i>
                                <span>Providers <span class="badge pull-right">v 1.0</span></span>
                            </a>
                        </li>

                        <li class="@isset($matched_menu) active @endisset">
                            <a href="{{ route('crm.masterlist.matchedgames') }}">
                                <i class="fa fa-th-list" aria-hidden="true"></i>
                                <span>Matched Games <span class="badge pull-right">BETA</span></span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif
            --}}
        </ul>
    </section>
</aside>