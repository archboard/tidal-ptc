<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Language::updateOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English']);
        Language::updateOrCreate(['code' => 'zh-CN'], ['name' => 'Chinese (Simplified)', 'native_name' => '简体中文']);
        Language::updateOrCreate(['code' => 'ja'], ['name' => 'Japanese', 'native_name' => '日本語']);
        Language::updateOrCreate(['code' => 'ko'], ['name' => 'Korean', 'native_name' => '한국어']);
        Language::updateOrCreate(['code' => 'ar'], ['name' => 'Arabic', 'native_name' => 'العربية']);
        // Language::updateOrCreate(['code' => 'es'], ['name' => 'Spanish', 'native_name' => 'Español']);
        // Language::updateOrCreate(['code' => 'zh-TW'], ['name' => 'Chinese (Traditional)', 'native_name' => '繁體中文']);
        // Language::updateOrCreate(['code' => 'fr'], ['name' => 'French', 'native_name' => 'Français']);
        // Language::updateOrCreate(['code' => 'de'], ['name' => 'German', 'native_name' => 'Deutsch']);
    }
}
