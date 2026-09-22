<?php

use Illuminate\Support\Facades\File;

/**
 * Every literal string passed to __()/trans() in the app, including the English
 * validation lines that intentionally route their messages through the catalogue.
 *
 * @return array<int, string>
 */
function translatableKeys(): array
{
    $paths = [
        app_path(),
        resource_path('js'),
        base_path('routes'),
        base_path('bootstrap'),
        lang_path('en'),
    ];

    $keys = [];

    foreach ($paths as $path) {
        foreach (File::allFiles($path) as $file) {
            $source = $file->getContents();

            if ($file->getExtension() === 'php') {
                $tokens = token_get_all($source);

                foreach ($tokens as $index => $token) {
                    if (! is_array($token) || $token[0] !== T_STRING || ! in_array($token[1], ['__', 'trans'], true)) {
                        continue;
                    }

                    $depth = 0;
                    for ($i = $index + 1; $i < count($tokens); $i++) {
                        if ($tokens[$i] === '(') {
                            $depth++;

                            continue;
                        }
                        if ($tokens[$i] === ')') {
                            $depth--;
                            if ($depth <= 0) {
                                break;
                            }

                            continue;
                        }
                        if ($depth >= 1 && is_array($tokens[$i]) && $tokens[$i][0] === T_CONSTANT_ENCAPSED_STRING) {
                            $keys[stripcslashes(substr($tokens[$i][1], 1, -1))] = true;
                            break;
                        }
                    }
                }
            } elseif (preg_match_all('/\b__(?:\(\s*)([\'"`])((?:\\\\.|(?!\1).)*)\1/s', $source, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $keys[$match[2]] = true;
                }
            }
        }
    }

    return array_keys($keys);
}

it('translates user-facing strings into Chinese', function () {
    app()->setLocale('zh-CN');

    expect(__('Save'))->toBe('保存')
        ->and(__('Time slots'))->toBe('时间段')
        ->and(__('You are managing time slots for :count people.', ['count' => 3]))->toBe('您正在为 3 人管理时间段。')
        ->and(__('auth.failed'))->toBe('这些凭据与我们的记录不符。')
        ->and(__('passwords.sent'))->toBe('我们已通过邮件发送您的密码重置链接！')
        ->and(__('validation.required'))->toBe('此字段为必填项。');
});

it('has a Chinese translation for every string used in the app', function () {
    $catalogue = json_decode(file_get_contents(lang_path('zh-CN.json')), true);

    expect($catalogue)->toBeArray();

    $missing = collect(translatableKeys())
        ->reject(fn (string $key) => str_contains($key, '.') && in_array(explode('.', $key)[0], ['auth', 'passwords', 'validation', 'pagination'], true))
        ->reject(fn (string $key) => array_key_exists($key, $catalogue))
        ->sort()
        ->values();

    expect($missing)->toBeEmpty('Missing zh-CN translations: '.$missing->implode(' | '));
});
