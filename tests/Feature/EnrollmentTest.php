<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolling_creates_one_active_enrollment_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($user)->post(route('courses.enroll', $course))->assertSessionHas('status');

        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->sole();
        $this->assertSame('active', $enrollment->status);
        $this->assertSame(3, $enrollment->priority);
        $this->assertNull($enrollment->daily_time_minutes);

        // Enrolling again is a no-op — still exactly one row, no error.
        $this->actingAs($user)->post(route('courses.enroll', $course));
        $this->assertSame(1, Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->count());
    }

    public function test_enrolling_redirects_to_the_schedule_form_only_the_first_time(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        // A brand-new enrollment lands straight on the schedule form, not the course page —
        // it isn't in the daily plan yet without a priority/daily time set.
        $enrollResponse = $this->actingAs($user)->post(route('courses.enroll', $course));
        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->sole();
        $enrollResponse->assertRedirect(route('enrollments.edit', $enrollment));

        $this->actingAs($user)->post(route('courses.enroll', $course))->assertRedirect(route('courses.show', $course));
    }

    public function test_owner_can_update_schedule_and_status(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($enrollment->user)->put(route('enrollments.update', $enrollment), [
            'priority' => 1, 'daily_time_minutes' => 45, 'preferred_time' => '21:00', 'status' => 'paused',
        ])->assertRedirect(route('courses.show', $enrollment->course));

        $enrollment->refresh();
        $this->assertSame(1, $enrollment->priority);
        $this->assertSame(45, $enrollment->daily_time_minutes);
        $this->assertSame('21:00', substr($enrollment->preferred_time, 0, 5));
        $this->assertSame('paused', $enrollment->status);
    }

    public function test_owner_can_set_and_clear_video_days(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($enrollment->user)->put(route('enrollments.update', $enrollment), [
            'priority' => 1, 'status' => 'active', 'video_days' => ['1', '4'],
        ]);
        $this->assertSame([1, 4], $enrollment->fresh()->video_days);

        // An empty checkbox group sends no "video_days" key at all: clears back to "every day".
        $this->actingAs($enrollment->user)->put(route('enrollments.update', $enrollment), [
            'priority' => 1, 'status' => 'active',
        ]);
        $this->assertNull($enrollment->fresh()->video_days);
    }

    public function test_another_user_cannot_edit_or_update_an_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('enrollments.edit', $enrollment))->assertForbidden();
        $this->actingAs($stranger)->put(route('enrollments.update', $enrollment), ['priority' => 1, 'status' => 'active'])->assertForbidden();
        $this->assertSame(3, $enrollment->fresh()->priority);
    }

    public function test_catalog_marks_only_my_enrolled_courses(): void
    {
        $me = User::factory()->create();
        $mine = Course::factory()->create(['title' => 'دوره‌ی من']);
        $theirs = Course::factory()->create(['title' => 'دوره‌ی دیگری']);
        Enrollment::factory()->for($me)->for($mine)->create();
        Enrollment::factory()->for($theirs)->create(); // someone else's

        $html = $this->actingAs($me)->get(route('courses.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'برداشته‌شده'));
    }

    public function test_catalog_page_can_enroll_directly(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($user)->get(route('courses.index'))->assertOk()
            ->assertSee(route('courses.enroll', $course), false);

        $enrollResponse = $this->actingAs($user)->post(route('courses.enroll', $course));
        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->sole();
        $enrollResponse->assertRedirect(route('enrollments.edit', $enrollment));

        // Once enrolled, the catalog no longer offers the enroll form for that course.
        $this->actingAs($user)->get(route('courses.index'))->assertOk()
            ->assertDontSee(route('courses.enroll', $course), false);
    }
}
