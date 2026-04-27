<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $nombre = $this->faker->unique()->word();

        return [
            'nombre'      => $nombre,
            'slug'        => Str::slug($nombre),
            'descripcion' => $this->faker->sentence(),
            'modulos'     => [],
        ];
    }
}