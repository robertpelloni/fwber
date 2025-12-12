<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaCheckTest extends TestCase
{
    public function test_gifts_table_has_columns()
    {
        $this->assertTrue(Schema::hasTable('gifts'));
        $this->assertTrue(Schema::hasColumn('gifts', 'cost'));
        $this->assertTrue(Schema::hasColumn('gifts', 'icon_url'));
    }

    public function test_user_gifts_table_has_columns()
    {
        $this->assertTrue(Schema::hasTable('user_gifts'));
        $this->assertTrue(Schema::hasColumn('user_gifts', 'message'));
    }
}
