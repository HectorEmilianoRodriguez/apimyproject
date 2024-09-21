<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run(): void
    {
        
        $this->call(UserSeeder::class);
        $this->call(WorkEnvSeeder::class);
        $this->call(JoinWorkEnvUserSeeder::class);
        $this->call(BoardSeeder::class);
        $this->call(ListsSeeder::class);
        $this->call(CardSeeder::class);
        $this->call(CardUsersSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(LabelSeeder::class);
        $this->call(GroupTaskCoordinatorLeaderSeeder::class);
        $this->call(activitycoordinatorleaderSeeder::class);
        $this->call(relcardlabelSeeder::class);

        
    }
}
