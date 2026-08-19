<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_page_links_to_prefecture_pages(): void
    {
        $this->venue();

        $this->get('/')
            ->assertOk()
            ->assertSee('都道府県から探す')
            ->assertSee('/area/tokyo', false);
    }

    public function test_prefecture_page_lists_its_venues(): void
    {
        $venue = $this->venue();

        $this->get('/area/tokyo')
            ->assertOk()
            ->assertSee($venue->name)
            ->assertSee('東京都の保育園・幼稚園');
    }

    public function test_old_area_query_redirects_to_the_prefecture_page(): void
    {
        $this->venue();

        $this->get('/?area='.urlencode('東京都'))
            ->assertRedirect(route('venues.area', ['areaSlug' => 'tokyo']));
    }

    public function test_prefecture_without_venues_is_not_found(): void
    {
        $this->venue();

        $this->get('/area/okinawa')->assertNotFound();
        $this->get('/area/atlantis')->assertNotFound();
    }

    public function test_nearby_returns_venues_sorted_by_distance(): void
    {
        $near = Venue::create([
            'name' => '近い園', 'area' => '東京都', 'facility_type' => '保育園',
            'lat' => 35.6820, 'lng' => 139.7670,
        ]);
        $far = Venue::create([
            'name' => '遠い園', 'area' => '東京都', 'facility_type' => '保育園',
            'lat' => 35.9000, 'lng' => 139.9000,
        ]);

        $response = $this->getJson('/nearby?lat=35.6812&lng=139.7671')->assertOk();

        $names = array_column($response->json('venues'), 'name');
        $this->assertSame([$near->name, $far->name], $names);
    }

    private function venue(): Venue
    {
        return Venue::create([
            'name' => 'テスト保育園',
            'area' => '東京都',
            'facility_type' => '保育園',
            'lat' => 35.6812,
            'lng' => 139.7671,
        ]);
    }
}
