<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer l'utilisateur Administrateur par défaut pour le Backoffice
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@koriassetmanagement.com'],
            [
                'name' => 'Administrateur Kori',
                'password' => bcrypt('admin1234'),
            ]
        );

        $this->call([
            FundSeeder::class,
        ]);
    }
}
