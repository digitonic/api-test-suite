# General structure

The main class you will want to extend to enable the test framework functionalities is CRUDTestCase. This is the main entry point for the test suite automated assertions via the `runBaseApiTestSuite()`method. This class also provides a default `setUp()` method creating users. The CRUDTestCase class extends the base Laravel TestCase from your app, so it will take into account everything you change in there too, e.g. Migrations runs, setUp, or tearDown methods;

## Configuration file
This file is generated in `config/digitonic` when you run the installer command: `php artisan digitonic:api-test-suite:install` or `php artisan d:a:i` for short. It contains the following options:

###### `api_user_class`
This is the class that is used for your base user. _e.g.: Mdoc\Users\Models\User::class_

###### `required_response_headers`
An array of header-header value pairs to be returned by all your API routes. If any value goes, you can set the value of your header to null. This can be overwritten at the endpoint level by overwriting the `checkRequiredResponseHeaders` method of the AssertsOuput trait in your Test class. 

###### `default_headers`
The default headers that will be passed to all your api calls. Keys should be capitalised, and prefixed with HTTP, guzzle style. _e.g.: ['HTTP_ACCEPT' => 'application/json']_

###### `entities_per_page`
Indicate here the maximum number of entities your responses should have on index requests. This is used for pagination testing.

###### `identifier_field`
A closure that returns a string determining the name of the ID field. You have access to the same context as your test here. _e.g.: function () {return 'uuid';} for a use of uuids across all entities_
###### `identifier_faker`
A closure to fake an identifier. Mainly used for NOT_FOUND status code testing. _e.g.: function () {return \Mdoc\Concerns\HasUuid::generateUuid();}_

###### `templates`
An array which contain a `base_path` key, with a string value indiciating the path to the templates for pagination and error responses . _e.g.: ['base_path' => base_path('tests/templates/')]_

###### `creation_rules`
***Probably the most useful configuration option***. It allows to create ad hoc creation rules for your payloads. It should be an array with creation rules names used in your tests as keys, and closures returning an appropriate value as values. _e.g.:
```['tomorrow' => function () {return Carbon::parse('+1day')->format('Y-m-d H:i:s');}]``` allows you to generate the date of tomorrow in your payloads. We'll come back to it when we'll see the `creationRules()` method_

## `setUp()` method
This methods generates:
- an `identifierGenerator` field usable anywhere in your tests, based on your `identifier_faker` configuration (see above).
- A new `entities` field defaulting to an empty Laravel Collection.
- A `user` field which will be the rightful user of your resources in your API. This relies on you creating a `crud` factory state for your `api_user_class` in your factories folder. This should set up the user default fields, and it's relation to a team/organization. See mDoc's [UserFactory](https://github.com/digitonic/mdoc/blob/develop/database/factories/UserFactory.php) for an example of how to set it up.
- An `otherUser` field which is created using the same factory, which represent a user that shouldn't have access to the resource being accessed. Make sure that your factory `crud` state creates a different team each time it is called.

All these fields are available anywhere in your test class.

## `runBaseApiTestSuite()` method
This method is where the magic happen. Most classical CRUD endpoints will be able to use that method out of the box, but some more complexe endpoints might need to override it to get the context for the test suite right. If that was to be the case, all the CRUDTestCase methods are still available to help you build your test.

This method runs the following assertions in order:
- `Unauthorized` status code and error format when not providing an authoriztion token.
- `Not Found` status code and error format when providing a non existant identifier for your resource.
- `Unprocessable entity` status code and error format when forgetting required fields.
- `Forbidden` status code and error format when trying to access a resource you don't have permissions to access.
- `Bad Request` status code and error format if a required header is missing (we need that to ensure that the `Accept: application/json` is systematically set, which is the guarentee of integrity of the output of your API, otherwise you may have web redirects happening on Unauthorized requests, for example). This can also be used to enforce any other HTTP header to be present. It will test the value for that header, if it is provided.
- `Created` status code, and response data format, including provided links and timestamps, and the id replacement value if specified (e.g., `uuid` instead of `id`). If the resource shouldn't be duplicated, it will also make sure it is created only once (or twice, if it can be duplicated).
- `Accepted` status code, and response data format, including provided links and timestamps (`updated_at` has to be updated), and the id replacement value if specified (e.g., `uuid` instead of `id`).
- `OK` status code, and response data format, including provided links and timestamps, and the id replacement value if specified (e.g., `uuid` instead of `id`) for ListAll and Retrieve endpoints. In the case of a ListAll endpoint, pagination will also be tested for maximum numbers per page, and format, if the test is set to do so.
- `No Content` status code, and an empty response body for Delete endpoints.

Which assertion will be run is determined by the `DeterminesAssertions` Trait in the `CRUDTestCase` class. The method is based on the metadata you provide about your endpoint through the implementation of the CRUDTestCase abstract methods. I encourage you to read that file if you find unexpected assertions being run, and to override them if necessary.

# Test interface implementation
To help you, the package provides you with a a few Traits that you can use to set the defaults for Create, Retrieve, Update, ListAll, and Delete actions (`TestsCreateAction`,`TestsRetrieveAction`,`TestsUpdateAction`,`TestsListAllAction`, and `TestsDeleteAction` respectively). These provides with sensible defaults for the `httpAction()`, `statusCodes()`, `shouldAssertPaginate()`, `requiredHeaders()`, `creationHeaders()`, `requiredFields()` and `cannotBeDuplicated()` methods below. These can be overwritten in your test class if needed.

## Resource metadata
###### `resourceClass()`
Return the class name for the entity at hands.

###### `createResource()`
This method should return a string (the name of your create endpoint route, e.g. `campaigns.api.store`) if you choose to use your API Create endpoint to create the test's resources. In that case, expect other related endpoints tests to fail if you break the Create Endpoint. Otherwise, you can provide  a closure setting up the database in the required state for your test, and returning the target entity, the way your Create endpoint would do (as an object though, not an array or json).

###### `creationRules()`
This should return an associative array of form fields. Each should use a rule that is either available from the api test suite, or a custom rule that you have declared in your api-test-suite.php configuration file. The rules available by default can be seen in this [file](https://github.com/digitonic/api-test-suite/blob/master/src/DataGeneration/Factories/RuleFactory.php).

###### `viewableByOwnerOnly()`
This should return true if the resource you are creating is only available by the owner of the resource, or false otherwise.

###### `cannotBeDuplicated()`
This should return true if the resource can be duplicated on several Create endpoints calls with the same payload, or false if not.

##Request metadata
###### `httpAction()`
Should return one of these values as a string, according to the method your endpoint allows: get, post, put, or delete.

###### `requiredFields()`
This should return a non associative array of required fields for your request. Most useful for Create endpoints. _e.g.: ['code', 'sender', 'mdoc', 'send_at', 'is_live']_

###### `requiredHeaders()`
These are the headers you want to enforce the presence of in your request. It defaults to the default Headers from your configuration file if you're using the helper traits. _e.g.: ['HTTP_ACCEPT' => 'application/json']_

###### `creationHeaders()`
If you choose to use the API to create your resources (see `createResource()` method below), this should match the requiredHeaders() return value for your Create endpoint. _e.g.: ['HTTP_ACCEPT' => 'application/json']_

## Response metadata
###### `statusCodes()`
***This method determines what status codes should be returned by your endpoint, that is the different scenarios to be tested for success and errors. As such, it is very important to understans***
This method shold return an array of statusCodes from the list above in the `runBaseApiTestSuite()` method section. However the default from the helper traits are most often that not the ones you're after. _e.g.: [Response::HTTP_CREATED,Response::HTTP_UNPROCESSABLE_ENTITY,Response::HTTP_UNAUTHORIZED]_

###### `expectedResourceData(array $data)`
This method is a way to declare the expected value. Most of the time, it will be the payload, plus or minus some fields. You can add these from the $data array, if there is no way for you to know in advance what value will be returned (for timestamps for example).

###### `expectsTimestamps()`
Return true if the API returns the created_at, and updated_at timestamps, false otherwise.

###### `expectedLinks()`
Return an array of links related to your entity that your API returns. _e.g. ['self' => 'campaigns.api.show']_. This hasn't been used so far for any other type of link.

###### `fieldsReplacement()`
Return an array with the keys being the current entity's fields that should not be public, and therefore replace by the value of the pair. _e.g., ['id' => 'uuid'] to check that hte id is not present, and that the uuid is, in the returned API data for your entity._

###### `shouldAssertPaginate()`
This should return true if the endpoint should be paginated, false otherwise. Usually useful for ListAll endpoints.

###### `checkRequiredResponseHeaders()`
Return a list of headers and values which have to be included in the response from the server for the endpoint at hand. If any value goes, you can set the value of your header to null.

# Helper methods and fields
You can obviously use any of the subroutines used in the runBaseApiTestSuite in order to write a more flexible, custom test suite for your endpoint.

Useful methods provided by the CRUDTestCase class are the following:
- `getCurrentIdentifier()` returns the identifier (according to your configuration file) of the entity being tested.
- `doAuthenticatedRequest($data, array $params = [], $headers = [])` does the configured request for your endpoint, allows you to pass a $data array for your payload, and custom $params (used to build the target url, e.g. `campaignUuid`) and headers (these will override the default headers set in your configuration. If $headers is not set or is empty, they will use the default ones); It also sets the actingAs on the test to be $this->user.
- `doRequest($data, array $params = [], $headers = [])` is the same as the above, but without setting the actingAs to $this->user
- `getResponseData(TestResponse $response)` allorws you to easily extract the `data` attribute from your response.
- `generateEntities($numberOfEntities, $httpAction, $baseUser, $otherUser)` will create the number of entities of the `resourceClass()`, for the $baseUser provided. In addition, the $otherUser will be used to create another entity, not belonging to the $baseUser if the $httpAction is 'get' and $this->viewableByOwnerOnly() returns true, in order to test that it can't be seen by the $baseUser.
- `generatePayload($user)` returns a payload for the $user provided, that follows the rules returned by $this->creationRules().
- `generateEntityOverApi(array $payload, $user)` creates the entity at hand for the payload and user provided. This needs the createResource method to return an endpoint name.
- `generateUpdateData($payload, $user)` generate an update payload from the creation payload that is passed to it.
- `identifier()` is the identifier key in the current context. This can be quite handy when trying to build a custom test, if you have problems creating the `identifier_field` closure in the api-test-suite config.

- `generateSingleEntity($user, $payload = null)` returns an entity of the type at hand. I fyou don't pass a specific paylaod, it will use the above `generatePayload($user)` to create one.

# Pagination and Error Templates
Use the config file's `templates` field to indicate where your templates are for your automated test suite. By default they will be in `tests/templates`. Defaults are provided on running the installer command, to get you started.

###### pagination
The pagination template has currently no default. If you keep it as is, this won't be used.

###### errors
The error templates should be named after the statusCodes you are expecting (you then should have the following files: `400.blade.php, 401.blade.php, 403.blade.php, 404.blade.php and 422.blade.php`). For your convenience, templates allow the use of regular expressions, and the 422 template is being passed 2 variables: 'fieldName' (the required key undex scrutiny being tested from your `requiredFields()`, and 'formattedFieldName' which is the same field in snake_case.

