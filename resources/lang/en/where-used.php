<?php

declare(strict_types=1);

return [
    'count' => ':count :label',
    'and' => 'and',
    'used_by' => 'Used by :list.',
    'none' => 'Not used anywhere.',
    'blocked' => ':summary Deleting is blocked until those references are removed or reassigned.',
    'confirm' => ':summary Deleting will leave those records without this reference.',
    'blocked_title' => 'Cannot delete',
    'widget' => [
        'heading' => 'References',
        'empty' => 'Nothing references this record.',
        'more' => 'and :count more',
    ],
];
