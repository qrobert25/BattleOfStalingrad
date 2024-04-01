<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Battle of Stalingrad</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    </head>
    <body class="antialiased">
        <h1>Welcome to the Battle of Stalingrad Game</h1>

        <p>The following are the available links:</p>
        <ul>
            <li><a href="/api/v1/tanks/">Tanks</a></li>
            <li><a href="/api/v1/maps/">Maps</a></li>
            <li><a href="/api/v1/simulate/">Simulate game</a></li>
        </ul>
    </body>
</html>
