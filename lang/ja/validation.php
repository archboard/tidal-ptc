<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute を承認する必要があります。',
    'active_url' => ':attribute は有効な URL ではありません。',
    'after' => ':attribute は :date より後の日付である必要があります。',
    'after_or_equal' => ':attribute は :date 以降の日付である必要があります。',
    'alpha' => ':attribute は文字のみを含めることができます。',
    'alpha_dash' => ':attribute は文字、数字、ハイフン、アンダースコアのみを含めることができます。',
    'alpha_num' => ':attribute は文字と数字のみを含めることができます。',
    'array' => ':attribute は配列である必要があります。',
    'before' => ':attribute は :date より前の日付である必要があります。',
    'before_or_equal' => ':attribute は :date 以前の日付である必要があります。',
    'between' => [
        'numeric' => ':attribute は :min から :max の間である必要があります。',
        'file' => ':attribute は :min から :max キロバイトの間である必要があります。',
        'string' => ':attribute は :min から :max 文字の間である必要があります。',
        'array' => ':attribute は :min から :max 個の項目を持つ必要があります。',
    ],
    'boolean' => ':attribute フィールドは true または false である必要があります。',
    'confirmed' => ':attribute の確認が一致しません。',
    'date' => ':attribute は有効な日付ではありません。',
    'date_equals' => ':attribute は :date と同じ日付である必要があります。',
    'date_format' => ':attribute は :format 形式と一致しません。',
    'different' => ':attribute と :other は異なる必要があります。',
    'digits' => ':attribute は :digits 桁である必要があります。',
    'digits_between' => ':attribute は :min から :max 桁である必要があります。',
    'dimensions' => ':attribute の画像サイズが無効です。',
    'distinct' => ':attribute フィールドに重複した値があります。',
    'email' => ':attribute は有効なメールアドレスである必要があります。',
    'ends_with' => ':attribute は次のいずれかで終わる必要があります: :values。',
    'exists' => '選択された :attribute は無効です。',
    'file' => ':attribute はファイルである必要があります。',
    'filled' => ':attribute フィールドには値が必要です。',
    'gt' => [
        'numeric' => ':attribute は :value より大きい必要があります。',
        'file' => ':attribute は :value キロバイトより大きい必要があります。',
        'string' => ':attribute は :value 文字より大きい必要があります。',
        'array' => ':attribute は :value 個より多い項目を持つ必要があります。',
    ],
    'gte' => [
        'numeric' => ':attribute は :value 以上である必要があります。',
        'file' => ':attribute は :value キロバイト以上である必要があります。',
        'string' => ':attribute は :value 文字以上である必要があります。',
        'array' => ':attribute は :value 個以上の項目を持つ必要があります。',
    ],
    'image' => ':attribute は画像である必要があります。',
    'in' => '選択された :attribute は無効です。',
    'in_array' => ':attribute フィールドは :other に存在しません。',
    'integer' => ':attribute は整数である必要があります。',
    'ip' => ':attribute は有効な IP アドレスである必要があります。',
    'ipv4' => ':attribute は有効な IPv4 アドレスである必要があります。',
    'ipv6' => ':attribute は有効な IPv6 アドレスである必要があります。',
    'json' => ':attribute は有効な JSON 文字列である必要があります。',
    'lt' => [
        'numeric' => ':attribute は :value より小さい必要があります。',
        'file' => ':attribute は :value キロバイトより小さい必要があります。',
        'string' => ':attribute は :value 文字より小さい必要があります。',
        'array' => ':attribute は :value 個より少ない項目を持つ必要があります。',
    ],
    'lte' => [
        'numeric' => ':attribute は :value 以下である必要があります。',
        'file' => ':attribute は :value キロバイト以下である必要があります。',
        'string' => ':attribute は :value 文字以下である必要があります。',
        'array' => ':attribute は :value 個以下である必要があります。',
    ],
    'max' => [
        'numeric' => ':attribute は :max より大きくできません。',
        'file' => ':attribute は :max キロバイトを超えることはできません。',
        'string' => ':attribute は :max 文字を超えることはできません。',
        'array' => ':attribute は :max 個を超えることはできません。',
    ],
    'mimes' => ':attribute は次の種類のファイルである必要があります: :values。',
    'mimetypes' => ':attribute は次の種類のファイルである必要があります: :values。',
    'min' => [
        'numeric' => ':attribute は少なくとも :min である必要があります。',
        'file' => ':attribute は少なくとも :min キロバイトである必要があります。',
        'string' => ':attribute は少なくとも :min 文字である必要があります。',
        'array' => ':attribute は少なくとも :min 個の項目を持つ必要があります。',
    ],
    'multiple_of' => ':attribute は :value の倍数である必要があります',
    'not_in' => '選択された :attribute は無効です。',
    'not_regex' => ':attribute の形式が無効です。',
    'numeric' => ':attribute は数値である必要があります。',
    'password' => 'パスワードが正しくありません。',
    'present' => ':attribute フィールドが存在する必要があります。',
    'regex' => ':attribute の形式が無効です。',
    'required' => __('This field is required.'),
    'required_if' => __('This field is required when :other is :value.'),
    'required_unless' => __('This field is required unless :other is in :values.'),
    'required_with' => __('This field is required when :values is present.'),
    'required_with_all' => __('This field is required when :values are present.'),
    'required_without' => __('This field is required when :values is not present.'),
    'required_without_all' => __('This field is required when none of :values are present.'),
    'same' => ':attribute と :other は一致する必要があります。',
    'size' => [
        'numeric' => ':attribute は :size である必要があります。',
        'file' => ':attribute は :size キロバイトである必要があります。',
        'string' => ':attribute は :size 文字である必要があります。',
        'array' => ':attribute は :size 個の項目を含む必要があります。',
    ],
    'starts_with' => ':attribute は次のいずれかで始まる必要があります: :values。',
    'string' => ':attribute は文字列である必要があります。',
    'timezone' => ':attribute は有効なタイムゾーンである必要があります。',
    'unique' => ':attribute はすでに使用されています。',
    'uploaded' => ':attribute のアップロードに失敗しました。',
    'url' => ':attribute の形式が無効です。',
    'uuid' => ':attribute は有効な UUID である必要があります。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
