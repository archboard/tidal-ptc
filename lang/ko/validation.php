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

    'accepted' => ':attribute 을(를) 수락해야 합니다.',
    'active_url' => ':attribute 은(는) 유효한 URL이 아닙니다.',
    'after' => ':attribute 은(는) :date 이후 날짜여야 합니다.',
    'after_or_equal' => ':attribute 은(는) :date 이후이거나 같은 날짜여야 합니다.',
    'alpha' => ':attribute 은(는) 문자만 포함할 수 있습니다.',
    'alpha_dash' => ':attribute 은(는) 문자, 숫자, 하이픈, 밑줄만 포함할 수 있습니다.',
    'alpha_num' => ':attribute 은(는) 문자와 숫자만 포함할 수 있습니다.',
    'array' => ':attribute 은(는) 배열이어야 합니다.',
    'before' => ':attribute 은(는) :date 이전 날짜여야 합니다.',
    'before_or_equal' => ':attribute 은(는) :date 이전이거나 같은 날짜여야 합니다.',
    'between' => [
        'numeric' => ':attribute 은(는) :min 에서 :max 사이여야 합니다.',
        'file' => ':attribute 은(는) :min 에서 :max 킬로바이트 사이여야 합니다.',
        'string' => ':attribute 은(는) :min 에서 :max 자 사이여야 합니다.',
        'array' => ':attribute 은(는) :min 에서 :max 개 항목 사이여야 합니다.',
    ],
    'boolean' => ':attribute 필드는 true 또는 false여야 합니다.',
    'confirmed' => ':attribute 확인 값이 일치하지 않습니다.',
    'date' => ':attribute 은(는) 유효한 날짜가 아닙니다.',
    'date_equals' => ':attribute 은(는) :date 와 같은 날짜여야 합니다.',
    'date_format' => ':attribute 은(는) :format 형식과 일치하지 않습니다.',
    'different' => ':attribute 과(와) :other 은(는) 달라야 합니다.',
    'digits' => ':attribute 은(는) :digits 자리 숫자여야 합니다.',
    'digits_between' => ':attribute 은(는) :min 에서 :max 자리 숫자여야 합니다.',
    'dimensions' => ':attribute 의 이미지 크기가 유효하지 않습니다.',
    'distinct' => ':attribute 필드에 중복된 값이 있습니다.',
    'email' => ':attribute 은(는) 유효한 이메일 주소여야 합니다.',
    'ends_with' => ':attribute 은(는) 다음 중 하나로 끝나야 합니다: :values.',
    'exists' => '선택한 :attribute 은(는) 유효하지 않습니다.',
    'file' => ':attribute 은(는) 파일이어야 합니다.',
    'filled' => ':attribute 필드에 값이 있어야 합니다.',
    'gt' => [
        'numeric' => ':attribute 은(는) :value 보다 커야 합니다.',
        'file' => ':attribute 은(는) :value 킬로바이트보다 커야 합니다.',
        'string' => ':attribute 은(는) :value 자보다 커야 합니다.',
        'array' => ':attribute 은(는) :value 개보다 많은 항목을 가져야 합니다.',
    ],
    'gte' => [
        'numeric' => ':attribute 은(는) :value 보다 크거나 같아야 합니다.',
        'file' => ':attribute 은(는) :value 킬로바이트보다 크거나 같아야 합니다.',
        'string' => ':attribute 은(는) :value 자보다 크거나 같아야 합니다.',
        'array' => ':attribute 은(는) :value 개 이상의 항목을 가져야 합니다.',
    ],
    'image' => ':attribute 은(는) 이미지여야 합니다.',
    'in' => '선택한 :attribute 은(는) 유효하지 않습니다.',
    'in_array' => ':attribute 필드가 :other 에 없습니다.',
    'integer' => ':attribute 은(는) 정수여야 합니다.',
    'ip' => ':attribute 은(는) 유효한 IP 주소여야 합니다.',
    'ipv4' => ':attribute 은(는) 유효한 IPv4 주소여야 합니다.',
    'ipv6' => ':attribute 은(는) 유효한 IPv6 주소여야 합니다.',
    'json' => ':attribute 은(는) 유효한 JSON 문자열이어야 합니다.',
    'lt' => [
        'numeric' => ':attribute 은(는) :value 보다 작아야 합니다.',
        'file' => ':attribute 은(는) :value 킬로바이트보다 작아야 합니다.',
        'string' => ':attribute 은(는) :value 자보다 작아야 합니다.',
        'array' => ':attribute 은(는) :value 개보다 적은 항목을 가져야 합니다.',
    ],
    'lte' => [
        'numeric' => ':attribute 은(는) :value 보다 작거나 같아야 합니다.',
        'file' => ':attribute 은(는) :value 킬로바이트보다 작거나 같아야 합니다.',
        'string' => ':attribute 은(는) :value 자보다 작거나 같아야 합니다.',
        'array' => ':attribute 은(는) :value 개 이하여야 합니다.',
    ],
    'max' => [
        'numeric' => ':attribute 은(는) :max 보다 클 수 없습니다.',
        'file' => ':attribute 은(는) :max 킬로바이트를 초과할 수 없습니다.',
        'string' => ':attribute 은(는) :max 자를 초과할 수 없습니다.',
        'array' => ':attribute 은(는) :max 개를 초과할 수 없습니다.',
    ],
    'mimes' => ':attribute 은(는) 다음 형식의 파일이어야 합니다: :values.',
    'mimetypes' => ':attribute 은(는) 다음 형식의 파일이어야 합니다: :values.',
    'min' => [
        'numeric' => ':attribute 은(는) 최소 :min 이어야 합니다.',
        'file' => ':attribute 은(는) 최소 :min 킬로바이트여야 합니다.',
        'string' => ':attribute 은(는) 최소 :min 자여야 합니다.',
        'array' => ':attribute 은(는) 최소 :min 개 항목을 가져야 합니다.',
    ],
    'multiple_of' => ':attribute 은(는) :value 의 배수여야 합니다',
    'not_in' => '선택한 :attribute 은(는) 유효하지 않습니다.',
    'not_regex' => ':attribute 형식이 유효하지 않습니다.',
    'numeric' => ':attribute 은(는) 숫자여야 합니다.',
    'password' => '비밀번호가 올바르지 않습니다.',
    'present' => ':attribute 필드가 있어야 합니다.',
    'regex' => ':attribute 형식이 유효하지 않습니다.',
    'required' => __('This field is required.'),
    'required_if' => __('This field is required when :other is :value.'),
    'required_unless' => __('This field is required unless :other is in :values.'),
    'required_with' => __('This field is required when :values is present.'),
    'required_with_all' => __('This field is required when :values are present.'),
    'required_without' => __('This field is required when :values is not present.'),
    'required_without_all' => __('This field is required when none of :values are present.'),
    'same' => ':attribute 과(와) :other 은(는) 일치해야 합니다.',
    'size' => [
        'numeric' => ':attribute 은(는) :size 여야 합니다.',
        'file' => ':attribute 은(는) :size 킬로바이트여야 합니다.',
        'string' => ':attribute 은(는) :size 자여야 합니다.',
        'array' => ':attribute 은(는) :size 개 항목을 포함해야 합니다.',
    ],
    'starts_with' => ':attribute 은(는) 다음 중 하나로 시작해야 합니다: :values.',
    'string' => ':attribute 은(는) 문자열이어야 합니다.',
    'timezone' => ':attribute 은(는) 유효한 시간대여야 합니다.',
    'unique' => ':attribute 은(는) 이미 사용 중입니다.',
    'uploaded' => ':attribute 업로드에 실패했습니다.',
    'url' => ':attribute 형식이 유효하지 않습니다.',
    'uuid' => ':attribute 은(는) 유효한 UUID여야 합니다.',

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
