<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0" />

  @isset($title)
    <title>{{ $title }} | {{ config('app.name') }}</title>
  @else
    <title>{{ config('app.name') }}</title>
  @endisset

    @vite('resources/js/app.js')
  </head>
  <body class="h-full text-gray-900 dark:text-gray-100 bg-white dark:bg-black">
    @inertia
  </body>
</html>
