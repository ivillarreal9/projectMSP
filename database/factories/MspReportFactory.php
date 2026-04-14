<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MspReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_number'   => $this->faker->unique()->numberBetween(10000, 99999),
            'customer_name'   => $this->faker->company(),
            'location_name'   => $this->faker->city(),
            'ticket_title'    => $this->faker->sentence(),
            'tipo_ticket'     => $this->faker->randomElement(['Incidente', 'Solicitud']),
            'fecha_creacion'  => $this->faker->dateTimeThisYear(),
            'fecha_cierre'    => $this->faker->dateTimeThisYear(),
            'tiempo_vida_ticket' => $this->faker->randomFloat(2, 0.1, 10),
            'periodo'         => $this->faker->randomElement(['Enero 2026', 'Febrero 2026', 'Marzo 2026']),
        ];
    }
}