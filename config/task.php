<?php

return [

    'allowed_file_extensions' => [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'jpg',
        'jpeg',
        'png',
        'zip',
    ],

    'max_file_size' => 10240, // 10 MB

    /*
    |------------------------------------------------------------------
    | Permission matrix (PRD §4.2) — backend remains the source of
    | truth; this list only drives the admin Roles & Permissions UI.
    |------------------------------------------------------------------
    */
    'permissions' => [
        'Task' => [
            'Create task' => ['admin' => true, 'manager' => true, 'staff' => false, 'checker' => false],
            'View all tasks' => ['admin' => true, 'manager' => true, 'staff' => false, 'checker' => false],
            'View assigned task' => ['admin' => true, 'manager' => true, 'staff' => true, 'checker' => true],
            'Edit task' => ['admin' => true, 'manager' => true, 'staff' => 'own', 'checker' => false],
            'Delete task' => ['admin' => true, 'manager' => true, 'staff' => false, 'checker' => false],
            'Assign task' => ['admin' => true, 'manager' => true, 'staff' => false, 'checker' => false],
            'Comment' => ['admin' => true, 'manager' => true, 'staff' => true, 'checker' => true],
            'Upload document' => ['admin' => true, 'manager' => true, 'staff' => true, 'checker' => true],
        ],
        'Workflow' => [
            'Start task' => ['admin' => true, 'manager' => true, 'staff' => true, 'checker' => true],
            'Submit for check' => ['admin' => true, 'manager' => true, 'staff' => true, 'checker' => false],
            'Request revision' => ['admin' => true, 'manager' => true, 'staff' => false, 'checker' => true],
            'Approve task' => ['admin' => true, 'manager' => true, 'staff' => false, 'checker' => true],
            'Reopen task' => ['admin' => true, 'manager' => true, 'staff' => false, 'checker' => false],
        ],
        'System' => [
            'Manage users' => ['admin' => true, 'manager' => false, 'staff' => false, 'checker' => false],
            'Manage roles' => ['admin' => true, 'manager' => false, 'staff' => false, 'checker' => false],
            'View dashboard' => ['admin' => true, 'manager' => true, 'staff' => true, 'checker' => true],
        ],
    ],

];