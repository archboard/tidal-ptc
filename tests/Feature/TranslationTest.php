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

/**
 * Keys like "auth.failed" resolve to lang/{locale}/auth.php, not the JSON catalogue.
 */
function isGroupKey(string $key): bool
{
    return str_contains($key, '.')
        && in_array(explode('.', $key)[0], ['auth', 'passwords', 'validation', 'pagination'], true);
}

$locales = array_values(array_diff(
    array_map(
        fn (string $path) => basename($path, '.json'),
        glob(dirname(__DIR__, 2).'/lang/*.json') ?: [],
    ),
    ['en'],
));

it('translates user-facing strings into Chinese', function () {
    app()->setLocale('zh-CN');

    expect(__('Save'))->toBe('保存')
        ->and(__('Time slots'))->toBe('时间段')
        ->and(__('You are managing time slots for :count people.', ['count' => 3]))->toBe('您正在为 3 人管理时间段。')
        ->and(__('auth.failed'))->toBe('这些凭据与我们的记录不符。')
        ->and(__('passwords.sent'))->toBe('我们已通过邮件发送您的密码重置链接！')
        ->and(__('validation.required'))->toBe('此字段为必填项。')
        ->and(__('Created'))->toBe('已创建')
        ->and(__('Created at'))->toBe('创建时间');
});

it('translates user-facing strings into Korean', function () {
    app()->setLocale('ko');

    expect(__('Save'))->toBe('저장')
        ->and(__('Time slots'))->toBe('시간 슬롯')
        ->and(__('You are managing time slots for :count people.', ['count' => 3]))->toBe('3 명의 시간 슬롯을 관리하고 있습니다.')
        ->and(__('auth.failed'))->toBe('이 자격 증명은 우리 기록과 일치하지 않습니다.')
        ->and(__('passwords.sent'))->toBe('비밀번호 재설정 링크를 이메일로 보냈습니다!')
        ->and(__('validation.required'))->toBe('이 필드는 필수입니다.')
        ->and(__('Created'))->toBe('생성됨')
        ->and(__('Created at'))->toBe('생성일');
});

it('translates user-facing strings into Japanese', function () {
    app()->setLocale('ja');

    expect(__('Save'))->toBe('保存')
        ->and(__('Time slots'))->toBe('時間枠')
        ->and(__('You are managing time slots for :count people.', ['count' => 3]))->toBe('3 名の時間枠を管理しています。')
        ->and(__('auth.failed'))->toBe('これらの認証情報は記録と一致しません。')
        ->and(__('passwords.sent'))->toBe('パスワード再設定リンクをメールで送信しました！')
        ->and(__('validation.required'))->toBe('この項目は必須です。')
        ->and(__('Created'))->toBe('作成済み')
        ->and(__('Created at'))->toBe('作成日時');
});

it('translates user-facing strings into Spanish', function () {
    app()->setLocale('es');

    expect(__('Save'))->toBe('Guardar')
        ->and(__('Time slots'))->toBe('Franjas horarias')
        ->and(__('You are managing time slots for :count people.', ['count' => 3]))->toBe('Estás gestionando franjas horarias para 3 personas.')
        ->and(__('auth.failed'))->toBe('Estas credenciales no coinciden con nuestros registros.')
        ->and(__('passwords.sent'))->toBe('¡Te hemos enviado por correo el enlace para restablecer tu contraseña!')
        ->and(__('validation.required'))->toBe('Este campo es obligatorio.')
        ->and(__('Created'))->toBe('Creado')
        ->and(__('Created at'))->toBe('Fecha de creación');
});

it('has a translation for every string used in the app', function (string $locale) {
    $catalogue = json_decode(file_get_contents(lang_path("$locale.json")), true);

    expect($catalogue)->toBeArray();

    $missingStrings = collect(translatableKeys())
        ->reject(fn (string $key) => isGroupKey($key))
        ->reject(fn (string $key) => array_key_exists($key, $catalogue))
        ->sort()
        ->values();

    $missingGroups = collect(['auth', 'passwords', 'pagination', 'validation'])
        ->reject(fn (string $group) => File::exists(lang_path("$locale/$group.php")))
        ->values();

    expect($missingStrings)->toBeEmpty("Missing $locale translations: ".$missingStrings->implode(' | '))
        ->and($missingGroups)->toBeEmpty("Missing $locale group files: ".$missingGroups->implode(', '));
})->with($locales);
