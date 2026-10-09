<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testRegistrationAcceptsValidInput(): void
    {
        $this->assertSame([], registration_errors('alice', 'alice@example.com', 'secret123', 'secret123'));
    }

    public function testRegistrationRequiresEveryField(): void
    {
        $this->assertSame([
            'Username is required',
            'Email is required',
            'Password is required',
        ], registration_errors('', '', '', ''));
    }

    public function testRegistrationRejectsAShortUsername(): void
    {
        $this->assertSame(['Username must be at least 3 characters'], registration_errors('al', 'al@example.com', 'secret123', 'secret123'));
    }

    public function testRegistrationRejectsAnInvalidEmail(): void
    {
        $this->assertSame(['Invalid email format'], registration_errors('alice', 'not-an-email', 'secret123', 'secret123'));
    }

    public function testRegistrationRejectsAShortPassword(): void
    {
        $this->assertSame(['Password must be at least 6 characters'], registration_errors('alice', 'alice@example.com', '12345', '12345'));
    }

    public function testRegistrationRejectsAMismatchedConfirmation(): void
    {
        $this->assertSame(['Passwords do not match'], registration_errors('alice', 'alice@example.com', 'secret123', 'secret124'));
    }

    public function testLoginRequiresBothFields(): void
    {
        $this->assertSame([
            'Username is required',
            'Password is required',
        ], login_errors('', ''));

        $this->assertSame([], login_errors('alice', 'secret123'));
    }

    public function testTaskRequiresATitle(): void
    {
        $this->assertSame(['Title is required'], task_errors('', ''));
        $this->assertSame([], task_errors('Ship it', 'Details'));
    }

    public function testTheStringZeroIsNotTreatedAsMissing(): void
    {
        // empty('0') is true in PHP, which used to reject a title of "0" and
        // report "Username is required" for a username of "0".
        $this->assertSame([], task_errors('0', ''));
        $this->assertSame([], login_errors('0', 'secret123'));
        $this->assertSame(['Username must be at least 3 characters'], registration_errors('0', 'alice@example.com', 'secret123', 'secret123'));
        $this->assertSame(['Password must be at least 6 characters'], registration_errors('alice', 'alice@example.com', '0', '0'));
    }

    public function testRegistrationRejectsNulBytesInThePassword(): void
    {
        // password_hash() throws a ValueError on a NUL byte, which used to
        // escape the controller's catches and answer with a blank 500.
        $this->assertSame(
            ['Password contains characters that are not supported'],
            registration_errors('alice', 'alice@example.com', "ab\0cdef", "ab\0cdef")
        );
    }

    public function testRegistrationRejectsOverlongInput(): void
    {
        $this->assertSame(
            ['Username must be at most 50 characters'],
            registration_errors(str_repeat('a', 51), 'alice@example.com', 'secret123', 'secret123')
        );
        $this->assertSame(
            ['Email must be at most 100 characters'],
            registration_errors('alice', str_repeat('a', 90) . '@example.com', 'secret123', 'secret123')
        );
        $this->assertSame(
            ['Password must be at most 72 characters'],
            registration_errors('alice', 'alice@example.com', str_repeat('a', 73), str_repeat('a', 73))
        );
    }

    public function testRegistrationAcceptsBoundaryLengths(): void
    {
        $this->assertSame(
            [],
            registration_errors(
                str_repeat('a', 50),
                str_repeat('a', 32) . '@' . str_repeat('b', 55) . '.example.com',
                str_repeat('a', 72),
                str_repeat('a', 72)
            )
        );
    }

    public function testTaskRejectsOverlongFields(): void
    {
        $this->assertSame(['Title must be at most 255 characters'], task_errors(str_repeat('a', 256), ''));
        $this->assertSame(['Description must be at most 65535 characters'], task_errors('Ship it', str_repeat('a', 65536)));
        $this->assertSame([], task_errors(str_repeat('a', 255), str_repeat('a', 65535)));
    }

    public function testPriorityFallsBackToMedium(): void
    {
        $this->assertSame('low', normalize_priority('low'));
        $this->assertSame('medium', normalize_priority('medium'));
        $this->assertSame('high', normalize_priority('high'));
        $this->assertSame('medium', normalize_priority('urgent'));
        $this->assertSame('medium', normalize_priority('LOW'));
        $this->assertSame('medium', normalize_priority(null));
        $this->assertSame('medium', normalize_priority(['high']));
    }

    public function testStatusValidation(): void
    {
        $this->assertTrue(valid_status('pending'));
        $this->assertTrue(valid_status('in_progress'));
        $this->assertTrue(valid_status('completed'));
        $this->assertFalse(valid_status('bogus'));
        $this->assertFalse(valid_status(['pending']));
        $this->assertFalse(valid_status(null));
    }

    public function testStatusFilterIgnoresUnknownValues(): void
    {
        $this->assertSame('pending', status_filter('pending'));
        $this->assertNull(status_filter('bogus'));
        $this->assertNull(status_filter(['pending']));
        $this->assertNull(status_filter(null));
    }

    public function testTaskIdAcceptsPositiveIntegers(): void
    {
        $this->assertSame(5, task_id('5'));
        $this->assertSame(5, task_id(5));
        $this->assertSame(7, task_id('007'));
    }

    public function testTaskIdRejectsEverythingElse(): void
    {
        $this->assertNull(task_id(null));
        $this->assertNull(task_id('abc'));
        $this->assertNull(task_id('1.5'));
        $this->assertNull(task_id('0'));
        $this->assertNull(task_id('-1'));
        $this->assertNull(task_id('5abc'));
        $this->assertNull(task_id(['5']));
        $this->assertNull(task_id(0));
    }

    public function testFormStringHandlesArraysAndWhitespace(): void
    {
        $this->assertSame('alice', form_string('  alice  '));
        $this->assertSame('secret ', form_string('secret ', false));
        $this->assertSame('', form_string(['nested']));
        $this->assertSame('', form_string(null));
    }
}
