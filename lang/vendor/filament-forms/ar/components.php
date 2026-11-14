<?php

// Strings added in Filament 5 that its Arabic file does not have yet (merged over the package file).
$order = [
    'position' => ':label، الترتيب :position من :count',
    'moved' => 'نُقل :label إلى الترتيب :position من :count',
];

return [
    'builder' => ['reordering' => ['item' => 'قسم'] + $order],
    'file_upload' => ['placeholder' => 'لم تُرفع ملفات'],
    'js_field' => ['failed_to_load' => 'تعذّر تحميل هذا الحقل. حدّث الصفحة وحاول مرة أخرى.'],
    'key_value' => [
        'actions' => ['move_down' => ['label' => 'تحريك لأسفل'], 'move_up' => ['label' => 'تحريك لأعلى']],
        'reordering' => ['row' => 'صف'] + $order,
    ],
    'repeater' => ['reordering' => ['item' => 'عنصر'] + $order],
    'select' => [
        'error_message' => 'تعذّر تحميل الخيارات. حاول مرة أخرى.',
        'required_description' => 'اختر خياراً.',
    ],
];
