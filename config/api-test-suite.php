<?php

return [
    'api_user_class' => '', // the user class for which a factory with 'crud' state has been created, and which implements authenticatable
    'default_headers' => ['HTTP_ACCEPT' => 'application/json'],//the default headers for the api calls
    'entities_per_page' => 0, // the number of entities per page one paginated requests
    'identifier_field' => function () {}, // a function that returns the field that is used in routes to identify resources
    'identifier_faker' => function () {}, // a function that returns a new valid entity id
    'templates' => [
        'base_path' => base_path('tests/templates/'),// the folder in which the blade templates for errors can be found
    ],
    'creation_rules' => [ // the custom creation rules callbacks,e.g.:
//        'campaign_uuid' => function () {
//            $campaign = factory(\Mdoc\Campaigns\Models\Campaign::class)->create();
//            return $campaign->uuid;
//        },
//        'commentable' => function () {
//            return 'campaign';
//        },
    ]
];
