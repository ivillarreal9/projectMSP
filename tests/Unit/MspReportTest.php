<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\MspReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class MspReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function retorna_periodos_unicos_sin_nulos()
    {
        MspReport::factory()->create(['periodo' => 'Enero 2026']);
        MspReport::factory()->create(['periodo' => 'Enero 2026']); // duplicado
        MspReport::factory()->create(['periodo' => 'Febrero 2026']);
        MspReport::factory()->create(['periodo' => null]);

        $periodos = MspReport::uniquePeriodos();

        $this->assertContains('Enero 2026', $periodos);
        $this->assertContains('Febrero 2026', $periodos);
        $this->assertNotContains(null, $periodos);
        $this->assertCount(2, $periodos); // solo 2 únicos, sin el null
    }

    #[Test]
    public function no_retorna_periodos_nulos()
    {
        MspReport::factory()->create(['periodo' => null]);
        MspReport::factory()->create(['periodo' => 'Enero 2026']);

        $periodos = MspReport::uniquePeriodos();

        $this->assertNotContains(null, $periodos);
    }   
}