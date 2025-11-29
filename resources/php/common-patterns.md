# PHP Common Patterns

Common design patterns and architectural idioms in modern PHP development.

## Repository Pattern

Abstraction layer for data access, separating business logic from data persistence.

```php
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
    public function delete(User $user): void;
}

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function find(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? User::fromArray($data) : null;
    }

    public function save(User $user): void
    {
        // Insert or update logic
    }
}
```

## Factory Pattern

Object creation logic encapsulated in a factory class.

```php
class UserFactory
{
    public function createFromArray(array $data): User
    {
        return new User(
            email: $data['email'],
            name: $data['name'],
            age: $data['age'] ?? 0,
        );
    }

    public function createGuest(): User
    {
        return new User(
            email: 'guest@example.com',
            name: 'Guest',
            age: 0,
        );
    }
}
```

## Service Layer Pattern

Business logic organized into service classes.

```php
class UserRegistrationService
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private PasswordHasherInterface $hasher,
        private EmailServiceInterface $emailService,
    ) {
    }

    public function register(string $email, string $password): User
    {
        // Validation
        if ($this->repository->findByEmail($email)) {
            throw new UserAlreadyExistsException($email);
        }

        // Create user
        $user = new User(
            email: $email,
            password: $this->hasher->hash($password),
        );

        // Persist
        $this->repository->save($user);

        // Send welcome email
        $this->emailService->sendWelcome($user);

        return $user;
    }
}
```

## Value Objects

Immutable objects representing a value with validation logic.

```php
final readonly class Email
{
    private function __construct(
        private string $value
    ) {
    }

    public static function fromString(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$email}");
        }

        return new self(strtolower($email));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }
}
```

## Data Transfer Objects (DTOs)

Objects that carry data between processes or layers.

```php
final readonly class CreateUserDTO
{
    public function __construct(
        public string $email,
        public string $name,
        public int $age,
        public ?string $phone = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            name: $data['name'],
            age: $data['age'],
            phone: $data['phone'] ?? null,
        );
    }
}
```

## Strategy Pattern

Encapsulate algorithms and make them interchangeable.

```php
interface PaymentStrategyInterface
{
    public function process(float $amount): PaymentResult;
}

class CreditCardPayment implements PaymentStrategyInterface
{
    public function process(float $amount): PaymentResult
    {
        // Credit card processing logic
        return new PaymentResult(success: true, transactionId: 'cc_123');
    }
}

class PayPalPayment implements PaymentStrategyInterface
{
    public function process(float $amount): PaymentResult
    {
        // PayPal processing logic
        return new PaymentResult(success: true, transactionId: 'pp_456');
    }
}

class PaymentProcessor
{
    public function __construct(
        private PaymentStrategyInterface $strategy
    ) {
    }

    public function processPayment(float $amount): PaymentResult
    {
        return $this->strategy->process($amount);
    }
}
```

## Decorator Pattern

Add behavior to objects dynamically.

```php
interface LoggerInterface
{
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        file_put_contents('app.log', $message . PHP_EOL, FILE_APPEND);
    }
}

class TimestampedLogger implements LoggerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function log(string $message): void
    {
        $timestamped = sprintf('[%s] %s', date('Y-m-d H:i:s'), $message);
        $this->logger->log($timestamped);
    }
}

// Usage
$logger = new TimestampedLogger(new FileLogger());
$logger->log('Application started');
```

## Dependency Injection Container

```php
class Container
{
    private array $services = [];

    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (!isset($this->services[$id])) {
            throw new ServiceNotFoundException($id);
        }

        return ($this->services[$id])($this);
    }
}

// Usage
$container = new Container();

$container->set(PDO::class, function() {
    return new PDO('mysql:host=localhost;dbname=app', 'user', 'pass');
});

$container->set(UserRepository::class, function(Container $c) {
    return new UserRepository($c->get(PDO::class));
});

$repository = $container->get(UserRepository::class);
```

## Further Reading

- [Design Patterns: Elements of Reusable Object-Oriented Software](https://en.wikipedia.org/wiki/Design_Patterns)
- [PHP Design Patterns](https://designpatternsphp.readthedocs.io/)
- [Domain-Driven Design in PHP](https://leanpub.com/ddd-in-php)
