<?php

use App\Policies\CategoryPolicy;
use App\Policies\ForumPolicy;
use App\Policies\PostPolicy;
use App\Policies\ThreadPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Here we specify the policy classes to use. Change these if you want to
    | extend the provided classes and use your own instead.
    |
    */

    'policies' => [
        'forum' => ForumPolicy::class,
        'model' => [
            TeamTeaTime\Forum\Models\Category::class => CategoryPolicy::class,
            TeamTeaTime\Forum\Models\Thread::class => ThreadPolicy::class,
            TeamTeaTime\Forum\Models\Post::class => PostPolicy::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application user model
    |--------------------------------------------------------------------------
    |
    | Your application's user model.
    |
    */

    'user_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Application user name
    |--------------------------------------------------------------------------
    |
    | The user model attribute to use for displaying usernames.
    |
    */

    'user_name' => 'name',

];
