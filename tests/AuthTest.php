<?php
/**
 * ============================================
 * DataForge — Authentication Tests
 * ============================================
 *
 * Tests for login, registration, CSRF, and session management.
 *
 * @package DataForge\Tests
 */

namespace DataForge\Tests;

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clean session before each test
        $_SESSION = [];
    }

    // ─── Registration Validation Tests ────────────────────────────────────

    public function testRegistrationRejectsEmptyFields(): void
    {
        $result = registerUser('', '', '', '');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('obligatorios', $result['message']);
    }

    public function testRegistrationRejectsInvalidUsername(): void
    {
        $result = registerUser('ab', 'test@test.com', 'password123', 'password123');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('3-30', $result['message']);
    }

    public function testRegistrationRejectsSpecialCharsInUsername(): void
    {
        $result = registerUser('user<script>', 'test@test.com', 'password123', 'password123');
        $this->assertFalse($result['success']);
    }

    public function testRegistrationRejectsInvalidEmail(): void
    {
        $result = registerUser('validuser', 'not-an-email', 'password123', 'password123');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('correo', $result['message']);
    }

    public function testRegistrationRejectsShortPassword(): void
    {
        $result = registerUser('validuser', 'test@test.com', '1234567', '1234567');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('8 caracteres', $result['message']);
    }

    public function testRegistrationRejectsMismatchedPasswords(): void
    {
        $result = registerUser('validuser', 'test@test.com', 'password123', 'different456');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no coinciden', $result['message']);
    }

    // ─── Login Validation Tests ───────────────────────────────────────────

    public function testLoginRejectsEmptyFields(): void
    {
        $result = loginUser('', '');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('requeridos', $result['message']);
    }

    public function testLoginRejectsInvalidCredentials(): void
    {
        // This test requires a running database — skip if unavailable
        try {
            $result = loginUser('nonexistent@test.com', 'wrongpassword');
            $this->assertFalse($result['success']);
        } catch (\Exception $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }

    // ─── CSRF Token Tests ─────────────────────────────────────────────────

    public function testCsrfTokenGeneration(): void
    {
        $token = generateCsrfToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testCsrfTokenConsistentWithinSession(): void
    {
        $token1 = generateCsrfToken();
        $token2 = generateCsrfToken();
        $this->assertEquals($token1, $token2, 'CSRF token should be the same within a session');
    }

    public function testCsrfTokenValidation(): void
    {
        $token = generateCsrfToken();
        $this->assertTrue(validateCsrfToken($token));
    }

    public function testCsrfTokenInvalidatedAfterUse(): void
    {
        $token = generateCsrfToken();
        validateCsrfToken($token);
        // After validation, token is consumed — a new validation with same token should fail
        $this->assertFalse(validateCsrfToken($token));
    }

    public function testCsrfTokenRejectsInvalidToken(): void
    {
        generateCsrfToken();
        $this->assertFalse(validateCsrfToken('completely_invalid_token'));
    }

    public function testCsrfTokenRejectsEmptyToken(): void
    {
        generateCsrfToken();
        $this->assertFalse(validateCsrfToken(''));
    }

    // ─── Session Management Tests ─────────────────────────────────────────

    public function testGetCurrentUserReturnsNullWhenNotLoggedIn(): void
    {
        $this->assertNull(getCurrentUser());
    }

    public function testGetCurrentUserReturnsUserWhenLoggedIn(): void
    {
        $_SESSION['df_user'] = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@test.com',
            'theme' => 'neutral',
        ];

        $user = getCurrentUser();
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
    }

    // ─── Password Hashing Tests ──────────────────────────────────────────

    public function testPasswordHashingUsesBcrypt(): void
    {
        $password = 'secure_password_123';
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->assertTrue(password_verify($password, $hash));
        $this->assertStringStartsWith('$2y$12$', $hash); // Bcrypt with cost 12
    }

    public function testPasswordHashingRejectsWrongPassword(): void
    {
        $hash = password_hash('correct_password', PASSWORD_BCRYPT, ['cost' => 12]);
        $this->assertFalse(password_verify('wrong_password', $hash));
    }

    // ─── Theme Validation Tests ───────────────────────────────────────────

    public function testAvailableThemesExist(): void
    {
        $this->assertNotEmpty(AVAILABLE_THEMES);
        $this->assertArrayHasKey('neutral', AVAILABLE_THEMES);
        $this->assertArrayHasKey('medico', AVAILABLE_THEMES);
        $this->assertArrayHasKey('alimentos', AVAILABLE_THEMES);
        $this->assertArrayHasKey('ferreteria', AVAILABLE_THEMES);
        $this->assertArrayHasKey('legal', AVAILABLE_THEMES);
        $this->assertArrayHasKey('educacion', AVAILABLE_THEMES);
        $this->assertArrayHasKey('retail', AVAILABLE_THEMES);
    }
}
