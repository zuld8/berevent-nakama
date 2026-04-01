<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', env('APP_NAME'))</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Poppins', sans-serif; }
    </style>
    @stack('head')
  </head>
  <body class="bg-gray-100 text-gray-900 storefront-fixed">
    <div class="sf-canvas mx-auto bg-white min-h-screen">
        @yield('content')
    </div>
    @include('partials.bottom-nav')
  </body>
 </html>
