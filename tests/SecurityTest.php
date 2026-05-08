<?php
namespace DataForge\Tests;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testSanitizeInputStripsHtml(): void
    {
        $this->assertEquals('hello', sanitizeInput('<script>hello</script>'));
    }

    public function testSanitizeInputEncodesSpecialChars(): void
    {
        $result = sanitizeInput('1 < 2 & 3 > 0', false);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringContainsString('&lt;', $result);
    }

    public function testSanitizeInputTrimsWhitespace(): void
    {
        $this->assertEquals('hello', sanitizeInput('  hello  '));
    }

    public function testSanitizeArrayCleansValues(): void
    {
        $data = ['name' => '<b>test</b>', 'safe' => 'hello'];
        $clean = sanitizeArray($data);
        $this->assertEquals('test', $clean['name']);
        $this->assertEquals('hello', $clean['safe']);
    }

    public function testSanitizeArrayRespectsExclusions(): void
    {
        $data = ['html' => '<b>keep</b>', 'name' => '<b>strip</b>'];
        $clean = sanitizeArray($data, ['html']);
        $this->assertEquals('<b>keep</b>', $clean['html']);
        $this->assertEquals('strip', $clean['name']);
    }

    public function testDbIdentifierValidation(): void
    {
        $this->assertEquals('valid_name', sanitizeDbIdentifier('valid_name'));
        $this->assertEquals('Test123', sanitizeDbIdentifier('Test123'));
        $this->assertFalse(sanitizeDbIdentifier(''));
        $this->assertFalse(sanitizeDbIdentifier('has spaces'));
        $this->assertFalse(sanitizeDbIdentifier('has-dashes'));
        $this->assertFalse(sanitizeDbIdentifier('drop;--'));
    }

    public function testDbIdentifierMaxLength(): void
    {
        $long = str_repeat('a', 65);
        $this->assertFalse(sanitizeDbIdentifier($long));
        $valid = str_repeat('a', 64);
        $this->assertEquals($valid, sanitizeDbIdentifier($valid));
    }

    public function testRateLimitFileBasedAllows(): void
    {
        $id = 'test_' . uniqid();
        $result = checkRateLimitFile($id, 10, 60);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(9, $result['remaining']);
    }

    public function testRateLimitFileBasedBlocks(): void
    {
        $id = 'block_' . uniqid();
        for ($i = 0; $i < 5; $i++) {
            checkRateLimitFile($id, 5, 60);
        }
        $result = checkRateLimitFile($id, 5, 60);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    public function testPasswordHashCost(): void
    {
        $hash = password_hash('test_pass', PASSWORD_BCRYPT, ['cost' => 12]);
        $this->assertStringStartsWith('$2y$12$', $hash);
        $this->assertTrue(password_verify('test_pass', $hash));
        $this->assertFalse(password_verify('wrong', $hash));
    }
}
