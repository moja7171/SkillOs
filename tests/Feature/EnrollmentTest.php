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

    public function test_unenrolling_removes_only_my_enrollment_and_keeps_progress(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::factory()->create();
        Enrollment::factory()->for($me)->for($course)->create();
        Enrollment::factory()->for($other)->for($course)->create();

        $this->actingAs($me)->get(route('courses.show', $course))->assertOk()
            ->assertSee(route('courses.unenroll', $course), false);

        $this->actingAs($me)->delete(route('courses.unenroll', $course))
            ->assertRedirect(route('courses.show', $course))->assertSessionHas('status');

        $this->assertSame(0, Enrollment::where('user_id', $me->id)->count());
        $this->assertSame(1, Enrollment::where('user_id', $other->id)->count());

        // Cancelling again is a harmless no-op, and the course can be taken again.
        $this->actingAs($me)->delete(route('courses.unenroll', $course))->assertRedirect(route('courses.show', $course));
        $this->actingAs($me)->post(route('courses.enroll', $course))->assertRedirect(route('courses.learn', $course));
    }
}
