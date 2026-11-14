<?php

// Strings added in Filament 5 that its Arabic file does not have yet (merged over the package file).
return [
    'column_manager' => [
        'position' => 'الترتيب :position من :count.',
        'position_in_group' => 'الترتيب :position من :count في :group.',
        'reordered' => 'نُقل :column إلى الترتيب :position من :count.',
        'reordered_deferred' => 'نُقل :column إلى الترتيب :position من :count. طبّق الأعمدة لحفظ التغيير.',
        'reordered_in_group' => 'نُقل :column إلى الترتيب :position من :count في :group.',
        'reordered_in_group_deferred' => 'نُقل :column إلى الترتيب :position من :count في :group. طبّق الأعمدة لحفظ التغيير.',
        'actions' => ['move_up' => ['label' => 'تحريك :column لأعلى'], 'move_down' => ['label' => 'تحريك :column لأسفل']],
    ],
    'columns' => ['editable' => [
        'saving' => 'جارٍ حفظ التغيير...',
        'saved' => 'حُفظ التغيير.',
        'failure' => 'تعذّر تأكيد الحفظ. حدّث الصفحة قبل المحاولة مرة أخرى.',
        'unavailable' => 'لم يعد بالإمكان تعديل هذا العنصر.',
    ]],
    'actions' => [
        'move_record_up' => ['label' => 'تحريك :label لأعلى'],
        'move_record_down' => ['label' => 'تحريك :label لأسفل'],
    ],
    'reordering' => [
        'position' => ':label، الترتيب :position من :count.',
        'moved' => 'نُقل :label إلى الترتيب :position من :count.',
    ],
];
