<?php

namespace Database\Seeders\Admin;

use App\Admin\Models\Menu;
use App\Admin\Models\Permission;
use App\Admin\Models\Role;
use App\Admin\Authorization\PermissionCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $dashboard = $this->menu('Dashboard', 'dashboard', 'speedometer', 1);
        $executiveReports = $this->menu('Executive Reports', 'executive.reports.index', 'chart-simple', 5);
        $employee = $this->menu('Employee', 'users.index', 'users', 10);
        $website = $this->menu('Website CMS', null, 'globe', 15);
        $pages = $this->menu('Pages', 'cms.pages.index', null, 1, $website->id);
        $media = $this->menu('Media Library', 'cms.media.index', null, 2, $website->id);
        $navigation = $this->menu('Navigation', 'cms.navigation.index', null, 3, $website->id);
        $settings = $this->menu('Website Settings', 'cms.settings.edit', null, 4, $website->id);
        $catalog = $this->menu('Catalog & Pricing', 'services.index', 'briefcase', 20);
        $caregivers = $this->menu('Caregiver Recruitment', 'caregivers.index', 'user-check', 25);
        $readinessAlerts = $this->menu('Readiness Alerts', 'caregiver-readiness-alerts.index', null, 1, $caregivers->id);
        $trainingRecords = $this->menu('Training Records', 'training-records.index', 'graduation-cap', 26);
        $quality = $this->menu('Quality & Support', null, 'clipboard-check', 35);
        $followUps = $this->menu('Placement Follow-ups', 'placement-follow-ups.index', null, 1, $quality->id);
        $complaints = $this->menu('Routine Complaints', 'routine-complaints.index', null, 2, $quality->id);
        $reportIncident = $this->menu('Report Serious Incident', 'safeguarding.incidents.create', 'shield-exclamation', 40);
        $restrictedIncidents = $this->menu('Restricted Incidents', 'safeguarding.incidents.index', null, 2, $quality->id);
        $finance = $this->menu('Finance & Operations', null, 'receipt', 45);
        $invoices = $this->menu('Invoices', 'finance.invoices.index', null, 1, $finance->id);
        $payments = $this->menu('Payments', 'finance.payments.index', null, 2, $finance->id);

        $crm = $this->menu('CRM', null, 'people', 30);
        $leads = $this->menu('Leads', 'leads.index', null, 1, $crm->id);
        $households = $this->menu('Households', 'households.index', null, 2, $crm->id);
        $conflictBlocks = $this->menu('Conflict-of-Interest Blocks', 'record-conflict-blocks.index', null, 3, $crm->id);

        $adminConsole = $this->menu('Admin Console', null, 'lock', 100);
        $menus = $this->menu('Menus', 'menus.index', null, 1, $adminConsole->id);
        $userRoles = $this->menu('User Roles', 'user-roles.index', null, 2, $adminConsole->id);
        $permissions = $this->menu('Permissions', 'permissions.index', null, 3, $adminConsole->id);
        $notificationOperations = $this->menu('Notification Operations', 'notifications.operations', null, 4, $adminConsole->id);
        $auditLogs = $this->menu('System Audit Logs', 'audit-logs.index', 'clipboard-list', 95);

        $this->seedCurrentPermissions();

        $this->syncCoreRoleMenus([
            'platform-superuser' => [$dashboard, $employee, $website, $pages, $media, $navigation, $settings, $catalog, $caregivers, $readinessAlerts, $trainingRecords, $crm, $leads, $households, $conflictBlocks, $quality, $followUps, $complaints, $reportIncident, $restrictedIncidents, $finance, $invoices, $payments, $executiveReports, $adminConsole, $menus, $userRoles, $permissions, $notificationOperations, $auditLogs],
            'sa' => [$dashboard, $employee, $website, $pages, $media, $catalog, $adminConsole, $menus, $quality, $reportIncident, $auditLogs],
            'om' => [$dashboard, $employee, $website, $pages, $media, $navigation, $settings, $catalog, $caregivers, $readinessAlerts, $trainingRecords, $crm, $leads, $households, $conflictBlocks, $quality, $followUps, $reportIncident, $restrictedIncidents, $finance, $invoices, $payments, $auditLogs],
            'co' => [$dashboard, $catalog, $crm, $leads, $households, $finance, $invoices, $quality, $followUps, $reportIncident],
            'cm' => [$dashboard, $website, $pages, $media, $quality, $reportIncident],
            'rs' => [$dashboard, $catalog, $caregivers, $readinessAlerts, $quality, $reportIncident],
            'tr' => [$dashboard, $catalog, $trainingRecords, $reportIncident],
            'fn' => [$dashboard, $catalog, $finance, $invoices, $payments, $quality, $reportIncident],
            'qs' => [$dashboard, $catalog, $quality, $followUps, $complaints, $reportIncident],
            'sl' => [$dashboard, $caregivers, $quality, $followUps, $reportIncident, $restrictedIncidents, $auditLogs],
            'ex' => [$dashboard, $executiveReports, $quality, $reportIncident],
        ]);

        $this->grantControllerActions(['om'], [
            'TrainingRecordController' => ['index'],
            'FinanceInvoiceController' => ['index', 'show'],
            'FinancePaymentController' => ['index', 'receipt'],
            'UserController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus', 'passwordReset', 'bulkUpload', 'role_wise_users'],
            'ServiceController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus'],
            'ServiceRequestController' => ['store'],
            'QuoteController' => ['store', 'approve'],
            'MatchingController' => ['candidates'],
            'PlacementController' => ['transition', 'familySummary', 'revealAgreement'],
            'CaregiverReadinessAlertController' => ['index', 'acknowledge'],
            'PriceVersionController' => ['store', 'approve', 'retire', 'destroy'],
            'CaregiverController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'approveScreeningCheck', 'approveReadiness'],
            'LeadController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus', 'convert'],
            'HouseholdController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus'],
            'Household\\HouseholdContactController' => ['store', 'update', 'destroy'],
            'Household\\CareRecipientController' => ['store', 'update', 'destroy'],
            'Lead\\CommunicationLogController' => ['store', 'destroy'],
            'Lead\\ConsentController' => ['store', 'update'],
        ]);

        $this->grantControllerActions(['sa'], [
            'UserController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus', 'passwordReset', 'bulkUpload', 'role_wise_users'],
            'ServiceController' => ['index', 'show'],
        ]);
        $this->grantControllerActions(['co'], [
            'FinanceInvoiceController' => ['index', 'show'],
            'ServiceController' => ['index', 'show'],
            'ServiceRequestController' => ['store'],
            'QuoteController' => ['store'],
            'MatchingController' => ['candidates'],
            'ProposalController' => ['store', 'recordFamilyDecision', 'recordCaregiverResponse'],
            'PlacementController' => ['activate', 'transition', 'familySummary', 'revealAgreement'],
            'LeadController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'updateStatus', 'convert'],
            'HouseholdController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'updateStatus'],
            'Household\\HouseholdContactController' => ['store', 'update'],
            'Household\\CareRecipientController' => ['store', 'update'],
            'Lead\\CommunicationLogController' => ['store', 'destroy'],
            'Lead\\ConsentController' => ['store', 'update'],
        ]);
        $this->grantControllerActions(['rs', 'tr', 'fn', 'qs'], ['ServiceController' => ['index', 'show']]);
        $this->grantControllerActions(['rs'], ['CaregiverController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'importForm', 'import', 'importTemplate', 'recordCheck', 'storeDocument']]);
        $this->grantControllerActions(['tr'], ['TrainingRecordController' => ['index', 'store']]);
        $this->grantControllerActions(['rs', 'om', 'sl'], ['CaregiverController' => ['revealDocument']]);
        $this->grantControllerActions(['sl'], ['CaregiverController' => ['index', 'show', 'approveScreeningCheck']]);
        $this->grantControllerActions(['rs', 'om'], ['CaregiverReadinessAlertController' => ['index', 'acknowledge']]);
        $this->grantControllerActions(['qs'], ['PlacementFollowUpController' => ['index', 'show', 'recordAttempt']]);
        $this->grantControllerActions(['om', 'co', 'sl'], ['PlacementFollowUpController' => ['index', 'show']]);
        $this->grantControllerActions(['qs'], ['RoutineComplaintController' => ['index', 'create', 'store', 'show', 'update']]);
        $this->grantControllerActions(['om'], ['RecordConflictBlockController' => ['index', 'create', 'store', 'revoke']]);
        $financeActions = [
            'FinanceInvoiceController' => ['index', 'show', 'issue'],
            'FinancePaymentController' => ['index', 'create', 'store', 'reconcile', 'receipt'],
        ];
        $this->grantControllerActions(['fn'], $financeActions);
        $this->grantNamedPermissions(['om'], ['sensitive_data_view']);

        $this->grantCmsActions(['cm'], [
            'CmsPageController' => ['index', 'create', 'store', 'edit', 'update', 'preview'],
            'CmsSectionController' => ['store', 'update', 'destroy', 'reorder'],
            'CmsMediaController' => ['index', 'store', 'update', 'destroy'],
            'CmsSettingController' => ['edit', 'update'],
        ], ['cms.edit']);
        $this->grantCmsActions(['om'], [
            'CmsPageController' => ['index', 'create', 'store', 'edit', 'update', 'preview', 'publish', 'unpublish', 'destroy'],
            'CmsSectionController' => ['store', 'update', 'destroy', 'reorder'],
            'CmsMediaController' => ['index', 'store', 'update', 'destroy'],
            'CmsMenuController' => ['index', 'store', 'update', 'destroy', 'reorder'],
            'CmsSettingController' => ['edit', 'update', 'publish'],
        ], ['cms.edit', 'cms.publish']);
        $this->grantCmsActions(['sa'], [
            'CmsPageController' => ['index', 'preview'],
            'CmsMediaController' => ['index'],
        ]);
        $this->syncCoreRolePermissionMatrix();
    }

    private function seedCurrentPermissions(): void
    {
        $actions = [
            'UserController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus', 'passwordReset', 'bulkUpload', 'role_wise_users'],
            'ServiceController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus'],
            'ServiceRequestController' => ['store'],
            'QuoteController' => ['store', 'approve'],
            'MatchingController' => ['candidates'],
            'ProposalController' => ['store', 'recordFamilyDecision', 'recordCaregiverResponse'],
            'PlacementController' => ['activate', 'transition', 'familySummary', 'revealAgreement'],
            'CaregiverReadinessAlertController' => ['index', 'acknowledge'],
            'PlacementFollowUpController' => ['index', 'show', 'recordAttempt'],
            'RoutineComplaintController' => ['index', 'create', 'store', 'show', 'update'],
            'RecordConflictBlockController' => ['index', 'create', 'store', 'revoke'],
            'ExecutiveReportController' => ['index', 'export'],
            'FinanceInvoiceController' => ['index', 'show', 'issue'],
            'FinancePaymentController' => ['index', 'create', 'store', 'reconcile', 'receipt'],
            'PriceVersionController' => ['store', 'approve', 'retire', 'destroy'],
            'CaregiverController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'importForm', 'import', 'importTemplate', 'recordCheck', 'approveScreeningCheck', 'approveReadiness', 'storeDocument', 'revealDocument'],
            'TrainingRecordController' => ['index', 'store'],
            'AuditLogController' => ['index'],
            'LeadController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus', 'convert'],
            'HouseholdController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus'],
            'Household\\HouseholdContactController' => ['store', 'update', 'destroy'],
            'Household\\CareRecipientController' => ['store', 'update', 'destroy'],
            'Lead\\CommunicationLogController' => ['store', 'destroy'],
            'Lead\\ConsentController' => ['store', 'update'],
            'CmsPageController' => ['index', 'create', 'store', 'edit', 'update', 'preview', 'publish', 'unpublish', 'destroy'],
            'CmsSectionController' => ['store', 'update', 'destroy', 'reorder'],
            'CmsMediaController' => ['index', 'store', 'update', 'destroy'],
            'CmsMenuController' => ['index', 'store', 'update', 'destroy', 'reorder'],
            'CmsSettingController' => ['edit', 'update', 'publish'],
        ];

        foreach ($actions as $controller => $methods) {
            foreach ($methods as $method) {
                $name = $controller.'@'.$method;
                $permission = Permission::withTrashed()->firstOrNew(['name' => $name]);
                $permission->fill([
                    'slug' => Str::snake($name),
                    'controller' => $controller,
                    'description' => 'Access '.$controller.' '.$method.' action',
                    'is_active' => true,
                ]);
                if ($permission->trashed()) {
                    $permission->restore();
                }
                $permission->save();
            }
        }

        foreach ([
            'cms.edit' => ['CMS', 'Edit website CMS content'],
            'cms.publish' => ['CMS', 'Publish website content'],
            'sensitive_data_view' => ['Household', 'View sensitive care recipient data'],
        ] as $name => [$controller, $description]) {
            $permission = Permission::withTrashed()->firstOrNew(['name' => $name]);
            $permission->fill(['slug' => $name, 'controller' => $controller, 'description' => $description, 'is_active' => true]);
            if ($permission->trashed()) {
                $permission->restore();
            }
            $permission->save();
        }
    }

    private function grantControllerActions(array $roleSlugs, array $controllers): void
    {
        foreach ($roleSlugs as $slug) {
            $role = Role::query()->where('slug', $slug)->first();
            if (! $role) {
                continue;
            }
            $names = collect($controllers)->flatMap(fn (array $methods, string $controller) => array_map(fn (string $method) => $controller.'@'.$method, $methods));
            $ids = Permission::query()->whereIn('name', $names)->pluck('id');
            $role->permissions()->syncWithoutDetaching($ids);
        }

        $this->invalidatePermissionCache();
    }

    private function grantCmsActions(array $roleSlugs, array $controllers, array $extraPermissions = []): void
    {
        $this->grantControllerActions($roleSlugs, $controllers);
        if ($extraPermissions === []) {
            return;
        }
        foreach ($roleSlugs as $slug) {
            $role = Role::query()->where('slug', $slug)->first();
            if (! $role) {
                continue;
            }
            $ids = Permission::query()->whereIn('name', $extraPermissions)->pluck('id');
            $role->permissions()->syncWithoutDetaching($ids);
        }
        $this->invalidatePermissionCache();
    }

    private function grantNamedPermissions(array $roleSlugs, array $permissionNames): void
    {
        $ids = Permission::query()->whereIn('name', $permissionNames)->pluck('id');
        foreach ($roleSlugs as $slug) {
            Role::query()->where('slug', $slug)->first()?->permissions()->syncWithoutDetaching($ids);
        }
        $this->invalidatePermissionCache();
    }

    private function syncCoreRolePermissionMatrix(): void
    {
        $matrix = [
            'sa' => [
                'AuditLogController' => ['index'],
                'UserController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus', 'passwordReset', 'bulkUpload', 'role_wise_users'],
                'ServiceController' => ['index', 'show'],
                'CmsPageController' => ['index', 'preview'],
                'CmsMediaController' => ['index'],
            ],
            'om' => [
                'AuditLogController' => ['index'],
                'UserController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus', 'passwordReset', 'bulkUpload', 'role_wise_users'],
                'ServiceController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus'],
                'PriceVersionController' => ['store', 'approve', 'retire', 'destroy'],
                'ServiceRequestController' => ['store'],
                'QuoteController' => ['store', 'approve'],
                'MatchingController' => ['candidates'],
                'PlacementFollowUpController' => ['index', 'show'],
                'CaregiverReadinessAlertController' => ['index', 'acknowledge'],
                'CaregiverController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'approveScreeningCheck', 'approveReadiness', 'revealDocument'],
                'TrainingRecordController' => ['index'],
                'FinanceInvoiceController' => ['index', 'show'],
                'FinancePaymentController' => ['index', 'receipt'],
                'LeadController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus', 'convert'],
                'HouseholdController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'updateStatus'],
                'Household\\HouseholdContactController' => ['store', 'update', 'destroy'],
                'Household\\CareRecipientController' => ['store', 'update', 'destroy'],
                'Lead\\CommunicationLogController' => ['store', 'destroy'],
                'Lead\\ConsentController' => ['store', 'update'],
                'RecordConflictBlockController' => ['index', 'create', 'store', 'revoke'],
                'ExecutiveReportController' => ['index', 'export'],
                'CmsPageController' => ['index', 'create', 'store', 'edit', 'update', 'preview', 'publish', 'unpublish', 'destroy'],
                'CmsSectionController' => ['store', 'update', 'destroy', 'reorder'],
                'CmsMediaController' => ['index', 'store', 'update', 'destroy'],
                'CmsMenuController' => ['index', 'store', 'update', 'destroy', 'reorder'],
                'CmsSettingController' => ['edit', 'update', 'publish'],
            ],
            'co' => [
                'FinanceInvoiceController' => ['index', 'show'],
                'PlacementFollowUpController' => ['index', 'show'],
                'ServiceController' => ['index', 'show'],
                'ServiceRequestController' => ['store'],
                'QuoteController' => ['store'],
                'MatchingController' => ['candidates'],
                'ProposalController' => ['store', 'recordFamilyDecision', 'recordCaregiverResponse'],
                'PlacementController' => ['activate', 'transition', 'familySummary', 'revealAgreement'],
                'LeadController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'updateStatus', 'convert'],
                'HouseholdController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'updateStatus'],
                'Household\\HouseholdContactController' => ['store', 'update'],
                'Household\\CareRecipientController' => ['store', 'update'],
                'Lead\\CommunicationLogController' => ['store'],
                'Lead\\ConsentController' => ['store', 'update'],
            ],
            'cm' => [
                'CmsPageController' => ['index', 'create', 'store', 'edit', 'update', 'preview'],
                'CmsSectionController' => ['store', 'update', 'destroy', 'reorder'],
                'CmsMediaController' => ['index', 'store', 'update', 'destroy'],
            ],
            'rs' => [
                'ServiceController' => ['index', 'show'],
                'CaregiverReadinessAlertController' => ['index', 'acknowledge'],
                'CaregiverController' => ['index', 'create', 'store', 'show', 'edit', 'update', 'importForm', 'import', 'importTemplate', 'recordCheck', 'storeDocument', 'revealDocument'],
            ],
            'tr' => [
                'ServiceController' => ['index', 'show'],
                'TrainingRecordController' => ['index', 'store'],
            ],
            'fn' => [
                'ServiceController' => ['index', 'show'],
                'FinanceInvoiceController' => ['index', 'show', 'issue'],
                'FinancePaymentController' => ['index', 'create', 'store', 'reconcile', 'receipt'],
            ],
            'qs' => ['ServiceController' => ['index', 'show'], 'PlacementFollowUpController' => ['index', 'show', 'recordAttempt'], 'RoutineComplaintController' => ['index', 'create', 'store', 'show', 'update']],
            'sl' => ['AuditLogController' => ['index'], 'PlacementFollowUpController' => ['index', 'show'], 'CaregiverController' => ['index', 'show', 'approveScreeningCheck', 'revealDocument']],
            'ex' => ['ExecutiveReportController' => ['index', 'export']],
        ];

        foreach ($matrix as $roleSlug => $controllers) {
            $role = Role::query()->where('slug', $roleSlug)->first();
            if (! $role) {
                continue;
            }

            $names = collect($controllers)->flatMap(fn (array $methods, string $controller) => array_map(fn (string $method) => $controller.'@'.$method, $methods));
            if ($roleSlug === 'om') {
                $names = $names->merge(['cms.edit', 'cms.publish', 'sensitive_data_view']);
            } elseif ($roleSlug === 'cm') {
                $names = $names->push('cms.edit');
            }

            $role->permissions()->sync(Permission::query()->whereIn('name', $names)->pluck('id')->all());
        }

        $this->invalidatePermissionCache();
    }

    private function syncCoreRoleMenus(array $matrix): void
    {
        foreach ($matrix as $slug => $menus) {
            $role = Role::query()->where('slug', $slug)->first();
            $role?->menus()->sync(collect($menus)->pluck('id')->all());
        }
    }

    private function invalidatePermissionCache(): void
    {
        PermissionCache::invalidate();
    }

    private function menu(string $title, ?string $routeName, ?string $icon, int $order, ?int $parentId = null): Menu
    {
        $menu = $routeName
            ? Menu::withTrashed()->where('route_name', $routeName)->where('parent_menu_id', $parentId)->first()
            : null;

        $menu ??= Menu::withTrashed()->where('title', $title)->where('parent_menu_id', $parentId)->first();
        $menu ??= new Menu;
        $menu->fill([
            'title' => $title,
            'route_name' => $routeName,
            'menu_icon' => $icon,
            'menu_order' => $order,
            'parent_menu_id' => $parentId,
            'is_active' => true,
        ]);

        if ($menu->trashed()) {
            $menu->restore();
        }

        $menu->save();

        return $menu;
    }
}
