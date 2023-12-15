<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Configuration
    |--------------------------------------------------------------------------
    |
    | When authenticating users from PowerSchool, there are essentially
    | three different types of users: staff, guardian, and student.
    | Configure their model class, guard, and attributes to sync.
    |
    | If `allowed` is true, that user type may authenticate.
    | See the readme for more examples on user type authorization.
    |
    | Set the `model` value to be the class of the appropriate model
    | for the given user type.
    |
    | Attributes are configured by setting the key's value to be the attribute
    | that is received from PowerSchool and the value's value the attribute
    | that is used in your actual model. To forgo syncing, set `attributes`
    | to be an empty array.
    |
    | The `guard` is the name of the guard to authenticate.
    |
    | The `identifying_attributes` map the OpenID attributes and column
    | on the given model by which to look up users in the database.
    | There are some caveats discussed in the readme. This assumes that
    | the values are actually present in the appropriate database.
    | Generally, these are the keys you would want to use
    | openid_claimed_id, email, and/or ref.
    |
    | Sometimes attributes need to be transformed before being used.
    | The `attribute_transformers` array contains a map of fields to
    | an invokable class. The key is the attribute from PowerSchool,
    | the value being the class name to be invoked.
    |
    | Lastly, there is a `redirectTo` key that the user will be redirected
    | to after successfully authenticating.
    |
    */

    'staff' => [
        'allowed' => true,
        'model' => \App\Models\User::class,
        'attributes' => [
            'firstName' => 'first_name',
            'given_name' => 'first_name',
            'lastName' => 'last_name',
            'family_name' => 'last_name',
            'email' => 'email',
            'ps_dcid' => 'sis_id',
        ],
        'guard' => 'web',
        'identifying_attributes' => [],
        'attribute_transformers' => [
            'email' => \GrantHolle\PowerSchool\Auth\Transformers\Lowercase::class,
        ],
        'redirectTo' => '',
    ],

    'guardian' => [
        'allowed' => true,
        'model' => \App\Models\User::class,
        'attributes' => [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'given_name' => 'first_name',
            'family_name' => 'last_name',
            'email' => 'email',
            'ps_dcid' => 'sis_id',
            'dcid' => 'sis_id',
        ],
        'guard' => 'web',
        'identifying_attributes' => [],
        'attribute_transformers' => [
            'email' => \GrantHolle\PowerSchool\Auth\Transformers\Lowercase::class,
        ],
        'redirectTo' => '',
    ],

    'student' => [
        'allowed' => false,
        'model' => \App\User::class,
        'attributes' => [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'email' => 'email',
        ],
        'guard' => 'web',
        'identifying_attributes' => [
            'openid_claimed_id' => 'openid_identity',
        ],
        'attribute_transformers' => [
            'email' => \GrantHolle\PowerSchool\Auth\Transformers\Lowercase::class,
        ],
        'redirectTo' => '',
    ],

];
