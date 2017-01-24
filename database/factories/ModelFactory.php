<?php

use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Concert::class, function(Faker\Generator $faker) {
    return [
        'title'                  => 'sample title',
        'subtitle'               => 'sample subtitle',
        'date'                   => Carbon::parse('2016-12-01 8:00pm'),
        'ticket_price'           => 5566,
        'venue'                  => 'sample venue',
        'venue_address'          => 'sample venue_address',
        'city'                   => 'sample city',
        'state'                  => 'sample state',
        'zip'                    => 'sample zip',
        'additional_information' => 'sample additional_information',
    ];
});

$factory->state(App\Concert::class, 'published', function(Faker\Generator $faker) {
    return [
        'published_at' => Carbon::parse('-1 week'),
    ];
});

$factory->state(App\Concert::class, 'unpublished', function(Faker\Generator $faker) {
    return [
        'published_at' => null,
    ];
});

$factory->define(App\Ticket::class, function(Faker\Generator $faker) {
    return [
        'concert_id' => function () {
            return factory(App\Concert::class)->create()->id;
        },
    ];
});

$factory->state(App\Ticket::class, 'reserved', function ($faker) {
    return [
        'reserved_at' => Carbon::now(),
    ];
});
