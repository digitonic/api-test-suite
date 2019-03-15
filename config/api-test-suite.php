<?php

return [
    'api_user_class' => '', //the user that should be used to authenticate. Please create a `crud` state in a factory for that user
    'default_headers' => ['HTTP_ACCEPT' => 'application/json'],
    'entities_per_page' => '', // the number of entities per page on paginated api endpoints
];