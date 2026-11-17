<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Gate;

function qualifiedAdmin(): User
{
    return User::factory()->admin()->withTwoFactor()->create();
}

test('a guest is redirected to the Hub login', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

test('the panel serves no login page of its own', function () {
    $response = $this->get('/admin/login');

    expect($response->status())->toBeIn([302, 404]);

    if ($response->isRedirect()) {
        $response->assertRedirect(route('login'));
    }
});

test('a guest returns to the panel after signing in', function () {
    // Without two-factor, Fortify signs in directly; the intended URL is what
    // matters here (the panel then answers 403 for this user).
    $admin = User::factory()->admin()->create();

    $this->get('/admin')->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(url('/admin'));

    $this->assertAuthenticatedAs($admin);
});

test('a learner gets 403', function () {
    $this->actingAs(User::factory()->withTwoFactor()->create())
        ->get('/admin')
        ->assertForbidden();
});

test('an admin without confirmed two-factor gets 403', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertForbidden();
});

test('an admin with an unverified email gets 403', function () {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->unverified()->create())
        ->get('/admin')
        ->assertForbidden();
});

test('a qualified admin gets 200', function () {
    $this->actingAs(qualifiedAdmin())
        ->get('/admin')
        ->assertOk();
});

test('the 403 page explains what the panel requires', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden()
        ->assertSee('two-factor authentication', escape: false);
});

test('the gate follows the three conditions', function () {
    expect(Gate::forUser(qualifiedAdmin())->allows('accessContentPanel'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->admin()->create())->allows('accessContentPanel'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->withTwoFactor()->create())->allows('accessContentPanel'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->admin()->withTwoFactor()->unverified()->create())->allows('accessContentPanel'))->toBeFalse();
});

test('the user model delegates panel access to the gate', function () {
    $panel = Filament::getPanel('admin');

    expect($panel)->toBeInstanceOf(Panel::class)
        ->and(qualifiedAdmin()->canAccessPanel($panel))->toBeTrue()
        ->and(User::factory()->create()->canAccessPanel($panel))->toBeFalse();
});

test('access revoked mid-session is rejected on the next request', function () {
    $admin = qualifiedAdmin();

    $this->actingAs($admin)->get('/admin')->assertOk();

    $admin->forceFill(['is_admin' => false])->save();

    $this->actingAs($admin->fresh())->get('/admin')->assertForbidden();
});

test('a Livewire update from a revoked admin is rejected', function () {
    $admin = qualifiedAdmin();

    $page = $this->actingAs($admin)->get('/admin')->assertOk();
    preg_match('/wire:snapshot="([^"]+)"/', $page->getContent(), $matches);
    $snapshot = html_entity_decode($matches[1] ?? '{}');

    $admin->forceFill(['is_admin' => false])->save();

    $this->actingAs($admin->fresh())
        ->withHeaders(['X-Livewire' => 'true'])
        ->postJson(route('default-livewire.update'), [
            'components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => []]],
        ])
        ->assertForbidden();
});

test('the panel is mounted at /admin without a login page', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->getPath())->toBe('admin')
        ->and($panel->getLoginUrl())->toBeNull();
});

test('the panel primary colour is the brand blue', function () {
    $colors = Filament::getPanel('admin')->getColors();

    expect($colors['primary'])->toBe(Color::hex('#3550E6'));
});

test('the panel loads Figtree from this host and no third-party font host', function () {
    $html = $this->actingAs(qualifiedAdmin())->get('/admin')->assertOk()->getContent();

    expect($html)->toContain('fonts/figtree/figtree.css')
        ->and($html)->toContain('Figtree Variable');

    preg_match_all('/<link[^>]+href="(https?:\/\/[^"]+)"/i', $html, $links);
    preg_match_all('/@import\s+url\(["\']?(https?:\/\/[^"\')]+)/i', $html, $imports);

    $external = collect([...$links[1], ...$imports[1]])
        ->reject(fn (string $url) => parse_url($url, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST)
            || parse_url($url, PHP_URL_HOST) === 'localhost');

    expect($external->all())->toBe([]);
});

test('the Figtree files exist in public', function () {
    foreach (['figtree.css', 'figtree-latin-wght-normal.woff2', 'figtree-latin-ext-wght-normal.woff2', 'OFL.txt'] as $file) {
        expect(public_path("fonts/figtree/{$file}"))->toBeFile();
    }
});
