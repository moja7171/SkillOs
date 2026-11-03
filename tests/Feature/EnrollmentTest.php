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

        // Enrolling again is a no-op — still exactly one row, no error.
        $this->actingAs($user)->post(route('courses.enroll', $course));
        $this->assertSame(1, Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->count());
    }

    public function test_enrolling_lands_on_the_course_the_first_time_only(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($user)->post(route('courses.enroll', $course))->assertRedirect(route('courses.learn', $course));
        $this->actingAs($user)->post(route('courses.enroll', $course))->assertRedirect(route('courses.show', $course));
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

        $this->actingAs($user)->post(route('courses.enroll', $course))->assertRedirect(route('courses.learn', $course));

        // Once enrolled, the catalog no longer offers the enroll form for that course.
        $this->actingAs($user)->get(route('courses.index'))->assertOk()
            ->assertDontSee(route('courses.enroll', $course), false);
    }
}
