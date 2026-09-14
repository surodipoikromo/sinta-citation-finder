<?php

namespace Tests\Unit;

use App\Services\CitationSearchService;
use PHPUnit\Framework\TestCase;

class CitationSearchServiceTest extends TestCase
{
    public function test_query_is_split_into_meaningful_concepts(): void
    {
        $service = new CitationSearchService();
        $profile = $service->queryProfile('Kualitas sistem berpengaruh terhadap kepuasan pengguna.');

        $this->assertContains('kualitas', $profile['terms']);
        $this->assertContains('sistem', $profile['terms']);
        $this->assertContains('kepuasan', $profile['terms']);
        $this->assertContains('pengguna', $profile['terms']);
        $this->assertNotContains('berpengaruh', $profile['terms']);
        $this->assertContains('kualitas sistem', $profile['concepts']);
        $this->assertContains('kepuasan pengguna', $profile['concepts']);
    }

    public function test_english_relationship_words_are_removed_but_concepts_remain(): void
    {
        $service = new CitationSearchService();
        $profile = $service->queryProfile('System quality affects user satisfaction.');

        $this->assertContains('system quality', $profile['concepts']);
        $this->assertContains('user satisfaction', $profile['concepts']);
        $this->assertNotContains('affects', $profile['terms']);
    }
}
