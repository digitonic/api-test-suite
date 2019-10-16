<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API User Class
    |--------------------------------------------------------------------------
    |
    | The user class for which a factory with 'crud' state has been
    | created, and which implements authenticatable
    */
    'api_user_class' => '',

    /*
    |--------------------------------------------------------------------------
    | Required Response Headers
    |--------------------------------------------------------------------------
    |
    | The headers your application should return
    */
    'required_response_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Default Headers
    |--------------------------------------------------------------------------
    |
    | The default headers for the api calls
    */
    'default_headers' => ['HTTP_ACCEPT' => 'application/json'],

    /*
    |--------------------------------------------------------------------------
    | Entities per Page
    |--------------------------------------------------------------------------
    |
    | The number of entities per page one paginated requests
    */
    'entities_per_page' => 0,

    /*
    |--------------------------------------------------------------------------
    | Identifier Field
    |--------------------------------------------------------------------------
    |
    | A function that returns the field that is used in routes to identify resources
    */
    'identifier_field' => function () {
    },

    /*
    |--------------------------------------------------------------------------
    | Identifier Faker
    |--------------------------------------------------------------------------
    |
    | A function that returns a new valid entity id
    */
    'identifier_faker' => function () {
    },

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    | Configure templates for your tests such as a folder in which the blade
    | templates for errors can be found
    */
    'templates' => [
        'base_path' => base_path('tests/templates/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Creation Rules
    |--------------------------------------------------------------------------
    |
    | the custom creation rules callbacks. e.g
    */
    'creation_rules' => [ // the custom creation rules callbacks,e.g.:
//        'user_uuid' => function () {
//            $user = factory(\App\Models\User::class)->create();
//            return $user->uuid;
//        },
//        'commentable' => function () {
//            return 'user';
//        },
    ]
];
