<?php

return [
    'api_user_class' => '', //the user that should be used to authenticate. Please create a `crud` state in a factory for that user
    'default_headers' => ['HTTP_ACCEPT' => 'application/json'],
    'entities_per_page' => '', // the number of entities per page on paginated api endpoints,
    'identifier_field' => function () {
        return 'id';
    }, //if you use uuids in your URLs, for example, this should be changed accordingly
    'identifier_faker' => function () {
        return 999999;
    },
    'owned_class_field' => function () {
        return 'team_id';
    },
    'templates' => [
        'base_path' => base_path('tests/templates/')
    ]
];