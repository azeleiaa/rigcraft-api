<?php

namespace Tests\Feature;

use Tests\TestCase;

class RigCraftApiTest extends TestCase
{
    /**
     * Test public catalog endpoint.
     */
    public function test_can_access_products_catalog(): void
    {
        $response = $this->getJson('/api/v1/products');

        // Status code must be 200 OK
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    /**
     * Test builder validation logic without components returns success.
     */
    public function test_builder_validate_endpoint(): void
    {
        $response = $this->postJson('/api/v1/builder/validate', [
            'components' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_compatible' => true,
        ]);
    }

    /**
     * Test JWT Security: Protected endpoints block unauthenticated requests.
     */
    public function test_protected_routes_require_jwt_bearer_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        // Should return 401 Unauthorized when no Bearer token provided
        $response->assertStatus(401);
    }

    /**
     * Test Admin routes require admin privileges.
     */
    public function test_admin_routes_require_authorization(): void
    {
        $response = $this->getJson('/api/v1/admin/stats');

        // Blocked without token
        $response->assertStatus(401);
    }

    /**
     * Test register -> login -> me flow (FR-001).
     */
    public function test_register_login_and_me_flow(): void
    {
        $email = 'test_' . uniqid() . '@example.com';

        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Tester RigCraft',
            'email' => $email,
            'password' => 'password123',
        ]);
        $register->assertStatus(201)->assertJsonPath('success', true);
        $this->assertNotEmpty($register->json('data.token'));

        // Register with short password must fail (min 8)
        $short = $this->postJson('/api/v1/auth/register', [
            'name' => 'Short',
            'email' => 'short_' . uniqid() . '@example.com',
            'password' => '123',
        ]);
        $short->assertStatus(422);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ]);
        $login->assertStatus(200);
        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $me = $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer {$token}"]);
        $me->assertStatus(200)->assertJsonPath('data.email', $email);
    }

    /**
     * Test customer token cannot access admin endpoints (403).
     */
    public function test_customer_token_cannot_access_admin(): void
    {
        $email = 'cust_' . uniqid() . '@example.com';
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Customer',
            'email' => $email,
            'password' => 'password123',
        ])->assertStatus(201);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('data.token');

        $this->getJson('/api/v1/admin/products', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(403);
    }

    /**
     * Test cart & orders require auth.
     */
    public function test_cart_and_orders_require_auth(): void
    {
        $this->getJson('/api/v1/cart')->assertStatus(401);
        $this->postJson('/api/v1/orders', [])->assertStatus(401);
    }

    /**
     * Test invalid voucher returns 404.
     */
    public function test_invalid_voucher_returns_404(): void
    {
        $this->postJson('/api/v1/vouchers/validate', [
            'code' => 'TIDAKADA_' . uniqid(),
            'subtotal' => 10000000,
        ])->assertStatus(404);
    }

    /**
     * Test builder accepts PRD shape and exposes both naming conventions.
     */
    public function test_builder_prd_shape_aliases(): void
    {
        $response = $this->postJson('/api/v1/builder/validate', [
            'processor' => '000000000000000000000000',
            'motherboard' => '000000000000000000000000',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'compatible',
            'is_compatible',
            'issues',
            'warnings',
            'estimatedWattage',
            'estimated_wattage',
        ]);
    }

    /**
     * Test products pagination meta and 404 detail.
     */
    public function test_products_pagination_and_404(): void
    {
        $response = $this->getJson('/api/v1/products?page=1&per_page=2');
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data', 'meta' => ['total', 'page', 'per_page', 'total_pages']]);

        $this->getJson('/api/v1/products/000000000000000000000000')->assertStatus(404);
    }

    /**
     * Test new endpoints exist and guard auth correctly.
     */
    public function test_new_endpoints_auth_guards(): void
    {
        // Public reviews list
        $this->getJson('/api/v1/reviews')->assertStatus(200);

        // Submit review requires auth
        $this->postJson('/api/v1/reviews', [
            'productId' => '000000000000000000000000',
            'rating' => 5,
            'comment' => 'Bagus',
        ])->assertStatus(401);

        // Update cart item requires auth
        $this->putJson('/api/v1/cart/000000000000000000000000', ['quantity' => 2])
            ->assertStatus(401);

        // Cancel order requires auth
        $this->putJson('/api/v1/orders/000000000000000000000000/cancel')
            ->assertStatus(401);

        // Admin vouchers require auth + admin
        $this->getJson('/api/v1/admin/vouchers')->assertStatus(401);
    }

    /**
     * Test review validation rejects bad input (with customer token).
     */
    public function test_review_validation(): void
    {
        $email = 'review_' . uniqid() . '@example.com';
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Reviewer',
            'email' => $email,
            'password' => 'password123',
        ])->assertStatus(201);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('data.token');

        // Unknown product -> 422
        $this->postJson('/api/v1/reviews', [
            'productId' => '000000000000000000000000',
            'rating' => 5,
            'comment' => 'Bagus',
        ], ['Authorization' => "Bearer {$token}"])->assertStatus(422);

        // Rating out of range -> 422 (validates before product lookup)
        $this->postJson('/api/v1/reviews', [
            'productId' => '000000000000000000000000',
            'rating' => 9,
            'comment' => 'Bagus',
        ], ['Authorization' => "Bearer {$token}"])->assertStatus(422);
    }
}

