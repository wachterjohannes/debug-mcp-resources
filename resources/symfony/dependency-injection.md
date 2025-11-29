# Symfony Dependency Injection

Guide to Symfony's dependency injection container and service configuration.

## Service Autowiring

### Automatic Service Registration

Services in `src/` are automatically registered with autowiring enabled:

```php
namespace App\Service;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function send(string $to, string $subject, string $body): void
    {
        // Send email
    }
}
```

No configuration needed - Symfony automatically:
1. Registers the service
2. Detects dependencies from constructor
3. Injects required services

### Using Autowired Services

```php
namespace App\Controller;

use App\Service\EmailService;

class UserController extends AbstractController
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    #[Route('/register', methods: ['POST'])]
    public function register(): Response
    {
        $this->emailService->send('user@example.com', 'Welcome', 'Welcome to our app!');

        return new Response('User registered');
    }
}
```

## Manual Service Configuration

### services.yaml

```yaml
services:
    # Default configuration
    _defaults:
        autowire: true
        autoconfigure: true

    # Service with specific arguments
    App\Service\ApiClient:
        arguments:
            $apiKey: '%env(API_KEY)%'
            $baseUrl: 'https://api.example.com'

    # Service with factory
    App\Service\CacheService:
        factory: ['App\Factory\CacheFactory', 'create']
        arguments:
            - '%kernel.cache_dir%'

    # Service alias
    App\Contract\LoggerInterface:
        alias: Monolog\Logger

    # Public service (accessible via container->get())
    App\Service\PublicService:
        public: true
```

## Interface Binding

### Binding Interfaces to Implementations

```yaml
services:
    # Bind interface to implementation globally
    App\Contract\PaymentGatewayInterface:
        alias: App\Service\StripePaymentGateway

    # Or use _defaults
    _defaults:
        bind:
            App\Contract\PaymentGatewayInterface: '@App\Service\StripePaymentGateway'
            $projectDir: '%kernel.project_dir%'
```

```php
namespace App\Service;

use App\Contract\PaymentGatewayInterface;

class OrderService
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway
    ) {
        // Symfony injects StripePaymentGateway automatically
    }
}
```

## Service Tags

### Tagging Services

```yaml
services:
    App\EventSubscriber\UserSubscriber:
        tags:
            - { name: kernel.event_subscriber }

    App\Validator\EmailValidator:
        tags:
            - { name: validator.constraint_validator, alias: email }

    # Auto-tag all services implementing an interface
    _instanceof:
        App\Contract\MessageHandlerInterface:
            tags: ['app.message_handler']
```

### Using Tagged Services

```php
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class MessageBus
{
    public function __construct(
        #[TaggedIterator('app.message_handler')]
        private iterable $handlers
    ) {
    }

    public function dispatch(Message $message): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($message);
        }
    }
}
```

## Service Decoration

### Decorating Existing Services

```yaml
services:
    # Original service
    App\Service\OriginalService:
        # ...

    # Decorator
    App\Service\DecoratedService:
        decorates: App\Service\OriginalService
        arguments:
            $inner: '@.inner'
```

```php
namespace App\Service;

class DecoratedService implements ServiceInterface
{
    public function __construct(
        private ServiceInterface $inner
    ) {
    }

    public function doSomething(): void
    {
        // Add behavior before
        $this->log('Starting operation');

        // Call original service
        $this->inner->doSomething();

        // Add behavior after
        $this->log('Operation completed');
    }
}
```

## Service Locator

### Creating Service Locators

```yaml
services:
    App\Handler\MessageHandlerLocator:
        arguments:
            - !tagged_locator
                tag: 'app.message_handler'
                index_by: 'key'
```

```php
use Psr\Container\ContainerInterface;

class MessageHandlerLocator
{
    public function __construct(
        private ContainerInterface $locator
    ) {
    }

    public function getHandler(string $key): MessageHandlerInterface
    {
        return $this->locator->get($key);
    }
}
```

## Lazy Services

### Lazy Loading Services

```yaml
services:
    App\Service\HeavyService:
        lazy: true
```

The service is only instantiated when actually used, not when injected.

## Service Attributes

### Using PHP Attributes for Configuration

```php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class ConfigurableService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,

        #[Autowire(service: 'monolog.logger.app')]
        private LoggerInterface $logger,

        #[TaggedIterator('app.plugin')]
        private iterable $plugins,
    ) {
    }
}
```

## Environment Variables

### Using Environment Variables

```yaml
# .env
DATABASE_URL="mysql://user:pass@localhost/dbname"
API_KEY="secret_key"

# services.yaml
services:
    App\Service\DatabaseService:
        arguments:
            $databaseUrl: '%env(DATABASE_URL)%'

    App\Service\ApiClient:
        arguments:
            $apiKey: '%env(API_KEY)%'
```

```php
namespace App\Service;

class ApiClient
{
    public function __construct(
        private string $apiKey
    ) {
    }
}
```

## Factory Pattern

### Using Factories

```yaml
services:
    # Factory service
    App\Factory\ConnectionFactory:
        # ...

    # Service created by factory
    App\Service\Connection:
        factory: ['@App\Factory\ConnectionFactory', 'create']
        arguments:
            - '%database_host%'
            - '%database_port%'
```

```php
namespace App\Factory;

class ConnectionFactory
{
    public function create(string $host, int $port): Connection
    {
        return new Connection($host, $port);
    }
}
```

## Compiler Passes

### Creating Custom Compiler Passes

```php
namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CustomCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('app.service_collector')) {
            return;
        }

        $definition = $container->findDefinition('app.service_collector');
        $taggedServices = $container->findTaggedServiceIds('app.custom_tag');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addService', [new Reference($id)]);
        }
    }
}
```

### Registering Compiler Pass

```php
namespace App;

use App\DependencyInjection\Compiler\CustomCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new CustomCompilerPass());
    }
}
```

## Service Injection Methods

### Constructor Injection (Recommended)

```php
class UserService
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger,
    ) {
    }
}
```

### Setter Injection

```yaml
services:
    App\Service\MyService:
        calls:
            - setLogger: ['@logger']
```

```php
class MyService
{
    private LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
```

### Property Injection (Not Recommended)

```yaml
services:
    App\Service\MyService:
        properties:
            logger: '@logger'
```

## Best Practices

### Do's

1. **Use Constructor Injection**: Preferred for required dependencies
2. **Type-hint Interfaces**: Depend on abstractions, not implementations
3. **Keep Services Stateless**: Services should not maintain state between requests
4. **Use Autowiring**: Let Symfony handle wiring automatically
5. **Tag Services Appropriately**: Use tags for service collections

### Don'ts

1. **Don't Inject Container**: Avoid service locator anti-pattern
2. **Don't Create Circular Dependencies**: A → B → A
3. **Don't Make Everything Public**: Only expose services that need to be public
4. **Don't Override _defaults Unnecessarily**: Use when really needed

## Further Reading

- [Symfony Service Container Documentation](https://symfony.com/doc/current/service_container.html)
- [Dependency Injection Component](https://symfony.com/doc/current/components/dependency_injection.html)
- [Service Autowiring](https://symfony.com/doc/current/service_container/autowiring.html)
