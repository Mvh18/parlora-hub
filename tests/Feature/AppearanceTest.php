<?php

/*
 * The theme is applied on the server from the `appearance` cookie that the
 * appearance settings write, so a reload never flashes the wrong theme.
 * "system" defers to the browser's colour-scheme preference.
 */

function htmlTag(string $html): string
{
    preg_match('/<html[^>]*>/', $html, $match);

    return $match[0] ?? '';
}

test('the dark preference puts the dark theme on the root element', function () {
    $response = $this->withUnencryptedCookie('appearance', 'dark')->get('/login');

    expect(htmlTag($response->getContent()))->toContain('class="dark"');
});

test('the light preference leaves the root element in the light theme', function () {
    $response = $this->withUnencryptedCookie('appearance', 'light')->get('/login');

    expect(htmlTag($response->getContent()))->not->toContain('dark');
});

test('the system preference follows the browser colour scheme', function () {
    $response = $this->get('/login');

    expect(htmlTag($response->getContent()))->not->toContain('dark')
        ->and($response->getContent())->toContain("matchMedia('(prefers-color-scheme: dark)')");
});
