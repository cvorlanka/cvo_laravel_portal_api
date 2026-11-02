<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrameSearchControllerTest extends TestCase
{
    public function test_territories_requires_valid_email(): void
    {
        $this->getJson('/api/frames/territories')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->getJson('/api/frames/territories?email=not-an-email')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_frame_search_requires_all_parameters(): void
    {
        $this->getJson('/api/frames/search')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['territory_id', 'search', 'period']);
    }

    public function test_frame_search_period_must_be_positive_integer(): void
    {
        $this->getJson('/api/frames/search?territory_id=T1&search=ray&period=abc')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }
}
