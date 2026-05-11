<?php

use Omega\Database\Schema\Table\Create;
use Omega\Database\Facades\Schema;

return [
    'up' => [
        Schema::table('users', function (Create $column) {
            $column('user')->varChar(32);
            $column('password')->varChar(500);

            $column->primaryKey('user');
        }),
    ],
    'down' => [
        Schema::drop()->table('users'),
    ],
];
