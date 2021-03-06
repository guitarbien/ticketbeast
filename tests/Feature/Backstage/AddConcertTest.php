<?php

namespace Tests\Feature\Backstage;

use App\Concert;
use App\Events\ConcertAdded;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Class AddConcertTest
 * @package Tests\Feature\Backstage
 */
class AddConcertTest extends TestCase
{
    use DatabaseMigrations;

    private function validParams($overrides = [])
    {
        return array_merge([
            'title'                  => 'No Warning',
            'subtitle'               => 'with Cruel Hand and Backtrack',
            'additional_information' => "You must be 19 years of age to attend this concert.",
            'date'                   => '2017-11-18',
            'time'                   => '8:00pm',
            'venue'                  => 'The Mosh Pit',
            'venue_address'          => '123 Fake St.',
            'city'                   => 'Laraville',
            'state'                  => 'ON',
            'zip'                    => '12345',
            'ticket_price'           => '32.50',
            'ticket_quantity'        => '75',
        ], $overrides);
    }

    public function test_管理者可以看到新增音樂會的表單新增頁()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/backstage/concerts/new');

        $response->assertStatus(200);
    }

    public function test_guests不能看到新增音樂會的表單新增頁()
    {
        $response = $this->get('/backstage/concerts/new');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_管理者可以加入一個合法的音樂會()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/backstage/concerts', [
            'title'                  => 'No Warning',
            'subtitle'               => 'with Cruel Hand and Backtrack',
            'additional_information' => "You must be 19 years of age to attend this concert.",
            'date'                   => '2017-11-18',
            'time'                   => '8:00pm',
            'venue'                  => 'The Mosh Pit',
            'venue_address'          => '123 Fake St.',
            'city'                   => 'Laraville',
            'state'                  => 'ON',
            'zip'                    => '12345',
            'ticket_price'           => '32.50',
            'ticket_quantity'        => '75',
        ]);

        tap(Concert::first(), function($concert) use ($response, $user) {
            $response->assertStatus(302);
            $response->assertRedirect("/backstage/concerts");

            static::assertTrue($concert->user->is($user));

            static::assertFalse($concert->isPublished());

            static::assertEquals('No Warning', $concert->title);
            static::assertEquals('with Cruel Hand and Backtrack', $concert->subtitle);
            static::assertEquals("You must be 19 years of age to attend this concert.", $concert->additional_information);
            static::assertEquals(Carbon::parse('2017-11-18 8:00pm'), $concert->date);
            static::assertEquals('The Mosh Pit', $concert->venue);
            static::assertEquals('123 Fake St.', $concert->venue_address);
            static::assertEquals('Laraville', $concert->city);
            static::assertEquals('ON', $concert->state);
            static::assertEquals('12345', $concert->zip);
            static::assertEquals(3250, $concert->ticket_price);
            static::assertEquals(75, $concert->ticket_quantity);
            static::assertEquals(0, $concert->ticketsRemaining());
        });
    }

    public function test_guests不能新增音樂會()
    {
        $response = $this->post('/backstage/concerts', $this->validParams());

        $response->assertStatus(302);
        $response->assertRedirect("/login");
        static::assertEquals(0, Concert::count());
    }

    public function test_title欄位為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'title' => ''
        ]));

        $response->assertStatus(302);
        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('title');
        static::assertEquals(0, Concert::count());
    }

    public function test_subtitle欄位為非必填()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/backstage/concerts', $this->validParams([
            'subtitle' => ''
        ]));

        tap(Concert::first(), function($concert) use ($response, $user) {
            $response->assertStatus(302);
            $response->assertRedirect("/backstage/concerts");

            static::assertTrue($concert->user->is($user));
            static::assertFalse($concert->isPublished());

            static::assertNull($concert->subtitle);
        });
    }

    public function test_additional_information欄位為非必填()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/backstage/concerts', $this->validParams([
            'additional_information' => '',
        ]));

        tap(Concert::first(), function ($concert) use ($response, $user) {
            $response->assertStatus(302);
            $response->assertRedirect("/backstage/concerts");

            static::assertTrue($concert->user->is($user));
            static::assertFalse($concert->isPublished());

            static::assertNull($concert->additional_information);
        });
    }

    public function test_date欄位為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'date' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('date');
        static::assertEquals(0, Concert::count());
    }

    public function test_date欄位格式必須為日期()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'date' => 'not a date',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('date');
        static::assertEquals(0, Concert::count());
    }

    public function test_時間為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'time' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('time');
        static::assertEquals(0, Concert::count());
    }

    public function test_time欄位格式必須為時間()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'time' => 'not-a-time',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('time');
        static::assertEquals(0, Concert::count());
    }

    public function test_venue為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'venue' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('venue');
        static::assertEquals(0, Concert::count());
    }

    public function test_venue_address為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'venue_address' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('venue_address');
        static::assertEquals(0, Concert::count());
    }

    public function test_city為必填()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'city' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('city');
        static::assertEquals(0, Concert::count());
    }

    public function test_state為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'state' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('state');
        static::assertEquals(0, Concert::count());
    }

    public function test_zip為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'zip' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('zip');
        static::assertEquals(0, Concert::count());
    }

    public function test_ticket_price為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_price' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_price');
        static::assertEquals(0, Concert::count());
    }

    public function test_ticket_price必須為數字()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_price' => 'not a price',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_price');
        static::assertEquals(0, Concert::count());
    }

    public function test_ticket_price至少要為5()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_price' => '4.99',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_price');
        static::assertEquals(0, Concert::count());
    }

    public function test_ticket_quantity為必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_quantity' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_quantity');
        static::assertEquals(0, Concert::count());
    }

    public function test_ticket_quantity必須是數字()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_quantity' => 'not a number',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_quantity');
        static::assertEquals(0, Concert::count());
    }

    public function test_ticket_quantity至少要為1()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_quantity' => '0',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_quantity');
        static::assertEquals(0, Concert::count());
    }

    public function test_如果有選擇圖檔的話可以上傳成功()
    {
        Event::fake([ConcertAdded::class]);
        Storage::fake('public');

        $user = User::factory()->create();

        $file = File::image('concert-poster.png', 850, 1100);
        $this->actingAs($user)->post('/backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        tap(Concert::first(), function ($concert) use($file) {
            // make sure there's a file in the public folder that matches the file that we uploaded
            static::assertNotNull($concert->poster_image_path);
            Storage::disk('public')->assertExists($concert->poster_image_path);

            // make sure the content of the two files are the same
            static::assertFileEquals(
                $file->getPathname(),
                Storage::disk('public')->path($concert->poster_image_path)
            );
        });
    }

    public function test_poster_image必須要是image()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = File::create('not-a-poster.pdf');

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        static::assertEquals(0, Concert::count());
    }

    public function test_poster_image寬度必須大於600px()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = File::image('poster.png', 599, 775);

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        static::assertEquals(0, Concert::count());
    }

    public function test_poster_image要符合某個長寬比例()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = File::image('poster.png', 851, 1100);

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        static::assertEquals(0, Concert::count());
    }

    public function test_poster_image為非必填()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/backstage/concerts', $this->validParams([
            'poster_image' => null,
        ]));

        tap(Concert::first(), function ($concert) use($response, $user) {
            $response->assertRedirect('/backstage/concerts');
            static::assertTrue($concert->user->is($user));
            static::assertNull($concert->poster_image_path);
        });
    }

    public function test_新增concert時會發送一個event()
    {
        $this->withoutExceptionHandling();

        Event::fake([ConcertAdded::class]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/backstage/concerts', $this->validParams());

        // assert that a concertAdded event was dispatched
        Event::assertDispatched(ConcertAdded::class, function ($event) {
            // 使用 firstOrFail() 確保找不到會 exception
            $concert = Concert::firstOrFail();
            return $event->concert->is($concert);
        });
    }
}
