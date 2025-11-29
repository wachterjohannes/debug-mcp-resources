# PHP Best Practices

Modern PHP development guidelines for PHP 8.1+ emphasizing type safety, security, and maintainability.

## Type Declarations

### Strict Types

Always enable strict types at the beginning of each file:

```php
<?php
declare(strict_types=1);

namespace App;

class UserService
{
    public function createUser(string $email, int $age): User
    {
        // Implementation
    }
}
```

### Parameter and Return Types

Use type declarations for all parameters and return values:

```php
// Good
public function calculateTotal(float $price, int $quantity): float
{
    return $price * $quantity;
}

// Avoid
public function calculateTotal($price, $quantity)
{
    return $price * $quantity;
}
```

### Nullable Types

Use nullable types explicitly:

```php
public function findUser(int $id): ?User
{
    $user = $this->repository->find($id);
    return $user ?? null;
}
```

## Error Handling

### Typed Exceptions

Use specific exception types and provide context:

```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException(
        sprintf('Invalid email address: %s', $email)
    );
}
```

### Try-Catch Best Practices

Catch specific exceptions, not generic ones:

```php
// Good
try {
    $this->processPayment($order);
} catch (PaymentFailedException $e) {
    $this->logger->error('Payment failed', ['order' => $order->id, 'error' => $e->getMessage()]);
    throw $e;
}

// Avoid catching \Exception or \Throwable unless necessary
```

## Security

### Input Validation

Always validate and sanitize user input:

```php
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
if ($email === false) {
    throw new InvalidArgumentException('Invalid email');
}
```

### SQL Injection Prevention

Use prepared statements with parameter binding:

```php
// Good: PDO with prepared statements
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// Never concatenate user input into SQL
```

### XSS Prevention

Escape output in templates:

```php
// In PHP templates
<?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') ?>
```

## Modern PHP Features

### Constructor Property Promotion (PHP 8.0+)

```php
class User
{
    public function __construct(
        private string $email,
        private int $age,
        private readonly string $id = '',
    ) {
    }
}
```

### Named Arguments (PHP 8.0+)

```php
$user = new User(
    email: 'user@example.com',
    age: 25,
    id: 'usr_123'
);
```

### Match Expressions (PHP 8.0+)

```php
$result = match ($status) {
    'pending' => 'Awaiting approval',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    default => 'Unknown status',
};
```

### Nullsafe Operator (PHP 8.0+)

```php
// Old way
$country = $user !== null ? $user->getAddress()?->getCountry() : null;

// New way
$country = $user?->getAddress()?->getCountry();
```

## Code Organization

### Single Responsibility Principle

Each class should have one reason to change:

```php
// Good: Separate concerns
class UserRepository
{
    public function find(int $id): ?User { }
}

class UserValidator
{
    public function validate(array $data): bool { }
}

class UserService
{
    public function __construct(
        private UserRepository $repository,
        private UserValidator $validator,
    ) {
    }
}
```

### Dependency Injection

Inject dependencies via constructor:

```php
class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function send(string $to, string $message): void
    {
        try {
            $this->mailer->send($to, $message);
        } catch (MailerException $e) {
            $this->logger->error('Email send failed', ['to' => $to, 'error' => $e]);
            throw $e;
        }
    }
}
```

## Testing

### Write Testable Code

Design for testability using dependency injection:

```php
// Testable
class OrderProcessor
{
    public function __construct(
        private PaymentGatewayInterface $gateway
    ) {
    }
}

// In tests, inject mock gateway
$processor = new OrderProcessor(new MockPaymentGateway());
```

## Further Reading

- [PHP: The Right Way](https://phptherightway.com/)
- [PSR-12: Extended Coding Style](https://www.php-fig.org/psr/psr-12/)
- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
