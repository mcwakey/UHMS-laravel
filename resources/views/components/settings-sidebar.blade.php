<!-- Start Settings Sidebar -->
<div class="sidebars settings-sidebar" id="sidebar2">
    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu5" class="sidebar-menu mt-0 p-0">
            <ul>
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ Request::is('profile-settings', 'security-settings', 'notifications-settings', 'integrations-settings') ? 'active' : '' }}">
                        <i class="ti ti-user-cog me-2"></i><span>Account Settings</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        <li><a href="{{url('profile-settings')}}" class="{{ Request::is('profile-settings') ? 'active' : '' }}">Profile</a></li>
                        <li><a href="{{url('security-settings')}}" class="{{ Request::is('security-settings') ? 'active' : '' }}">Security</a></li>
                        <li><a href="{{url('notifications-settings')}}" class="{{ Request::is('notifications-settings') ? 'active' : '' }}">Notifications</a></li>
                        <li><a href="{{url('integrations-settings')}}" class="{{ Request::is('integrations-settings') ? 'active' : '' }}">Integrations</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ Request::is('organization-settings', 'localization-settings', 'prefixes-settings', 'seo-setup-settings', 'language-settings', 'language-settings2','language-settings3', 'maintenance-mode-settings', 'login-and-register-settings', 'preferences-settings') ? 'active' : '' }}">
                        <i class="ti ti-world-cog me-2"></i><span>Website Settings</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        <li><a href="{{url('organization-settings')}}" class="{{ Request::is('organization-settings') ? 'active' : '' }}">Organization</a></li>
                        <li><a href="{{url('localization-settings')}}" class="{{ Request::is('localization-settings') ? 'active' : '' }}">Localization</a></li>
                        <li><a href="{{url('prefixes-settings')}}" class="{{ Request::is('prefixes-settings') ? 'active' : '' }}">Prefixes</a></li>
                        <li><a href="{{url('seo-setup-settings')}}" class="{{ Request::is('seo-setup-settings') ? 'active' : '' }}">SEO Setup</a></li>
                        <li><a href="{{url('language-settings')}}" class="{{ Request::is('language-settings','language-settings2','language-settings3') ? 'active' : '' }}">Language</a></li>
                        <li><a href="{{url('maintenance-mode-settings')}}" class="{{ Request::is('maintenance-mode-settings') ? 'active' : '' }}">Maintenance Mode</a></li>
                        <li><a href="{{url('login-and-register-settings')}}" class="{{ Request::is('login-and-register-settings') ? 'active' : '' }}">Login & Register</a></li>
                        <li><a href="{{url('preferences-settings')}}" class="{{ Request::is('preferences-settings') ? 'active' : '' }}">Preferences</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ Request::is('appointment-settings', 'working-hours-settings', 'cancellation-reason-settings') ? 'active' : '' }}">
                        <i class="ti ti-building-hospital me-2"></i><span>Clinic Settings</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        <li><a href="{{url('appointment-settings')}}" class="{{ Request::is('appointment-settings') ? 'active' : '' }}">Appointment</a></li>
                        <li><a href="{{url('working-hours-settings')}}" class="{{ Request::is('working-hours-settings') ? 'active' : '' }}">Working Hours</a></li>
                        <li><a href="{{url('cancellation-reason-settings')}}" class="{{ Request::is('cancellation-reason-settings') ? 'active' : '' }}">Cancellation Reason</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ Request::is('invoice-settings', 'invoice-templates-settings', 'signatures-settings', 'custom-fields-settings') ? 'active' : '' }}">
                        <i class="ti ti-device-mobile-cog me-2"></i><span>App Settings</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        <li><a href="{{url('invoice-settings')}}" class="{{ Request::is('invoice-settings') ? 'active' : '' }}">Invoice Settings</a></li>
                        <li><a href="{{url('invoice-templates-settings')}}" class="{{ Request::is('invoice-templates-settings') ? 'active' : '' }}">Invoice Templates</a></li>
                        <li><a href="{{url('signatures-settings')}}" class="{{ Request::is('signatures-settings') ? 'active' : '' }}">Signatures</a></li>
                        <li><a href="{{url('custom-fields-settings')}}" class="{{ Request::is('custom-fields-settings') ? 'active' : '' }}">Custom Fields</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ Request::is('email-settings', 'email-templates-settings', 'sms-gateways-settings', 'sms-templates-settings', 'gdpr-cookies-settings') ? 'active' : '' }}">
                        <i class="ti ti-device-desktop-cog me-2"></i><span>System Settings</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        <li><a href="{{url('email-settings')}}" class="{{ Request::is('email-settings') ? 'active' : '' }}">Email Settings</a></li>
                        <li><a href="{{url('email-templates-settings')}}" class="{{ Request::is('email-templates-settings') ? 'active' : '' }}">Email Templates</a></li>
                        <li><a href="{{url('sms-gateways-settings')}}" class="{{ Request::is('sms-gateways-settings') ? 'active' : '' }}">SMS Gateways</a></li>
                        <li><a href="{{url('sms-templates-settings')}}" class="{{ Request::is('sms-templates-settings') ? 'active' : '' }}">SMS Templates</a></li>
                        <li><a href="{{url('gdpr-cookies-settings')}}" class="{{ Request::is('gdpr-cookies-settings') ? 'active' : '' }}">GDPR Cookies</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ Request::is('payment-methods-settings', 'bank-accounts-settings', 'tax-rates-settings', 'currencies-settings') ? 'active' : '' }}">
                        <i class="ti ti-settings-dollar me-2"></i><span>Finance & Accounts</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        <li><a href="{{url('payment-methods-settings')}}" class="{{ Request::is('payment-methods-settings') ? 'active' : '' }}">Payment Methods</a></li>
                        <li><a href="{{url('bank-accounts-settings')}}" class="{{ Request::is('bank-accounts-settings') ? 'active' : '' }}">Bank Accounts</a></li>
                        <li><a href="{{url('tax-rates-settings')}}" class="{{ Request::is('tax-rates-settings') ? 'active' : '' }}">Tax Rates</a></li>
                        <li><a href="{{url('currencies-settings')}}" class="{{ Request::is('currencies-settings') ? 'active' : '' }}">Currencies</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ Request::is('sitemap-settings', 'clear-cache-settings', 'storage-settings', 'cronjob-settings', 'ban-ip-address-settings', 'system-backup-settings', 'database-backup-settings', 'system-update') ? 'active' : '' }}">
                        <i class="ti ti-settings-2 me-2"></i><span>Other Settings</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        <li><a href="{{url('sitemap-settings')}}" class="{{ Request::is('sitemap-settings') ? 'active' : '' }}">Sitemap</a></li>
                        <li><a href="{{url('clear-cache-settings')}}" class="{{ Request::is('clear-cache-settings') ? 'active' : '' }}">Clear Cache</a></li>
                        <li><a href="{{url('storage-settings')}}" class="{{ Request::is('storage-settings') ? 'active' : '' }}">Storage</a></li>
                        <li><a href="{{url('cronjob-settings')}}" class="{{ Request::is('cronjob-settings') ? 'active' : '' }}">Cronjob</a></li>
                        <li><a href="{{url('ban-ip-address-settings')}}" class="{{ Request::is('ban-ip-address-settings') ? 'active' : '' }}">Ban IP Address</a></li>
                        <li><a href="{{url('system-backup-settings')}}" class="{{ Request::is('system-backup-settings') ? 'active' : '' }}">System Backup</a></li>
                        <li><a href="{{url('database-backup-settings')}}" class="{{ Request::is('database-backup-settings') ? 'active' : '' }}">Database Backup</a></li>
                        <li><a href="{{url('system-update')}}" class="{{ Request::is('system-update') ? 'active' : '' }}">System Update</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- End Settings Sidebar -->