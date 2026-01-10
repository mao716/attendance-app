<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DateTimeDisplayTest extends TestCase
{
	use RefreshDatabase;

	#[Test]
	public function current_datetime_is_displayed_in_ui_format(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 9, 8, 15, 0, 'Asia/Tokyo'));

		$user = User::factory()->create([
			'email_verified_at' => now(),
		]);

		$response = $this->actingAs($user)->get(route('attendance.index'));

		$response->assertStatus(200);

		$response->assertSee('2026年1月9日');
		$response->assertSee('08:15');
	}
}
