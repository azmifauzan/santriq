<?php

use App\Exports\Templates\ReferenceSheet;

test('array() puts column labels first, then transposes values, padding short columns with null', function () {
    $sheet = new ReferenceSheet([
        'Kelas' => ['Kelas A', 'Kelas B', 'Kelas C'],
        'Role' => ['admin', 'pengajar'],
    ]);

    expect($sheet->array())->toBe([
        ['Kelas', 'Role'],
        ['Kelas A', 'admin'],
        ['Kelas B', 'pengajar'],
        ['Kelas C', null],
    ]);
});

test('title() returns Referensi', function () {
    $sheet = new ReferenceSheet(['Role' => ['admin', 'pengajar']]);

    expect($sheet->title())->toBe('Referensi');
});
