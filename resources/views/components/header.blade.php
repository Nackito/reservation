<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ $title ?? 'Afridayz'}}</title>


  <!-- Resource Hints -->
  <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
  <link rel="dns-prefetch" href="//fonts.bunny.net">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
  <link rel="preconnect" href="https://unpkg.com" crossorigin>
  <link rel="dns-prefetch" href="//unpkg.com">
  <link rel="preconnect" href="https://api.maptiler.com" crossorigin>
  <link rel="dns-prefetch" href="//api.maptiler.com">
  <link rel="preconnect" href="https://api-eu.pusher.com" crossorigin>
  <link rel="dns-prefetch" href="//api-eu.pusher.com">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <!-- Scripts -->
  @vite('resources/css/app.css')
  @vite('resources/js/app.js')
  @livewireStyles
</head>

<body class="bg-slate-200 dark:bg-slate-700">