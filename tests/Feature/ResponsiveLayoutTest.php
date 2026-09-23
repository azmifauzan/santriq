<?php

test('wide data tables remain horizontally scrollable on mobile', function (string $page) {
    $source = file_get_contents(resource_path("js/pages/{$page}"));

    expect($source)->toContain('overflow-x-auto rounded-md border bg-card');
})->with([
    'teachers' => 'Teachers/Index.vue',
    'classrooms' => 'Classrooms/Index.vue',
    'guardians' => 'Guardians/Index.vue',
    'attendance' => 'Attendance/Index.vue',
]);

test('panel page actions stack on mobile', function (string $page) {
    $source = file_get_contents(resource_path("js/pages/{$page}"));

    expect($source)->toContain('flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between');
})->with([
    'teachers' => 'Teachers/Index.vue',
    'classrooms' => 'Classrooms/Index.vue',
    'students' => 'Students/Index.vue',
    'guardians' => 'Guardians/Index.vue',
    'attendance' => 'Attendance/Index.vue',
    'achievements' => 'Achievements/Index.vue',
    'reports' => 'Reports/Index.vue',
    'invoices' => 'Invoices/Index.vue',
    'leave requests' => 'LeaveRequests/Index.vue',
]);

test('panel modals scroll within the mobile viewport', function (string $page) {
    $source = file_get_contents(resource_path("js/pages/{$page}"));

    expect($source)
        ->toContain('fixed inset-0 z-50 flex items-start justify-center overflow-y-auto')
        ->toMatch('/max-h-\[calc\(100dvh-2rem\)\][^"\n]*overflow-y-auto/');
})->with([
    'teachers' => 'Teachers/Index.vue',
    'classrooms' => 'Classrooms/Index.vue',
    'students' => 'Students/Index.vue',
    'guardians' => 'Guardians/Index.vue',
    'attendance' => 'Attendance/Index.vue',
    'achievements' => 'Achievements/Index.vue',
    'invoices' => 'Invoices/Index.vue',
    'leave requests' => 'LeaveRequests/Index.vue',
]);

test('paired form fields stack on mobile', function (string $page) {
    $source = file_get_contents(resource_path("js/pages/{$page}"));

    expect($source)->not->toContain('grid grid-cols-2 gap-4');
})->with([
    'students' => 'Students/Index.vue',
    'achievements' => 'Achievements/Index.vue',
    'leave requests' => 'LeaveRequests/Index.vue',
    'guardian leave requests' => 'guardian/LeaveRequests.vue',
]);

test('public headers provide a mobile navigation drawer', function (string $component) {
    $source = file_get_contents(resource_path("js/{$component}"));

    expect($source)
        ->toContain('isMobileMenuOpen')
        ->toContain('md:hidden')
        ->toContain('SheetContent');
})->with([
    'site header' => 'components/SiteHeader.vue',
    'tenant landing' => 'pages/Tenant/Landing.vue',
]);

test('public headers keep login accessible across viewports', function (string $component) {
    $source = file_get_contents(resource_path("js/{$component}"));

    expect($source)->toContain(':href="login()"');
})->with([
    'site header' => 'components/SiteHeader.vue',
    'tenant landing' => 'pages/Tenant/Landing.vue',
]);

test('public footer navigation wraps on mobile viewports', function () {
    $source = file_get_contents(resource_path('js/components/SiteFooter.vue'));

    expect($source)->toContain('flex-wrap');
});
