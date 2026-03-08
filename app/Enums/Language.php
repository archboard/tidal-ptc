<?php

namespace App\Enums;

use App\Enums\Traits\Collectable;
use App\Enums\Traits\HasName;

enum Language: string
{
    use Collectable;
    use HasName;

    case ENGLISH = 'en';
    case CHINESE_SIMPLIFIED = 'zh-CN';
    case JAPANESE = 'ja';
    case KOREAN = 'ko';
    case ARABIC = 'ar';

    public function nativeName(): string
    {
        return match ($this) {
            self::ENGLISH => 'English',
            self::CHINESE_SIMPLIFIED => '简体中文',
            self::JAPANESE => '日本語',
            self::KOREAN => '한국어',
            self::ARABIC => 'العربية',
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::ENGLISH => __('English'),
            self::CHINESE_SIMPLIFIED => __('Chinese (Simplified)'),
            self::JAPANESE => __('Japanese'),
            self::KOREAN => __('Korean'),
            self::ARABIC => __('Arabic'),
        };
    }
}
