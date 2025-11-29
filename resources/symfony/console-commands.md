# Symfony Console Commands

Guide to creating and using Symfony Console commands for CLI applications.

## Creating a Command

### Basic Command Structure

```php
<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:process-data',
    description: 'Process application data',
)]
class ProcessDataCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Processing data...');

        // Command logic here

        $output->writeln('Data processed successfully!');

        return Command::SUCCESS;
    }
}
```

### Command with Arguments

```php
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'app:create-user')]
class CreateUserCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addArgument('name', InputArgument::REQUIRED, 'User full name')
            ->addArgument('role', InputArgument::OPTIONAL, 'User role', 'user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $name = $input->getArgument('name');
        $role = $input->getArgument('role');

        $output->writeln(sprintf('Creating user: %s (%s) with role: %s', $name, $email, $role));

        return Command::SUCCESS;
    }
}
```

### Command with Options

```php
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'app:export-users')]
class ExportUsersCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Export format (csv, json)', 'csv')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of users to export', 100)
            ->addOption('active-only', null, InputOption::VALUE_NONE, 'Export only active users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        $limit = (int) $input->getOption('limit');
        $activeOnly = $input->getOption('active-only');

        $output->writeln(sprintf('Exporting %d users in %s format', $limit, $format));

        if ($activeOnly) {
            $output->writeln('Filtering for active users only');
        }

        return Command::SUCCESS;
    }
}
```

## Input and Output

### Output Styling

```php
use Symfony\Component\Console\Style\SymfonyStyle;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    $io->title('User Import Process');
    $io->section('Step 1: Validation');

    $io->success('Users imported successfully!');
    $io->warning('Some users already exist');
    $io->error('Import failed');
    $io->note('This is a note');

    $io->table(
        ['ID', 'Email', 'Name'],
        [
            [1, 'user@example.com', 'John Doe'],
            [2, 'admin@example.com', 'Jane Admin'],
        ]
    );

    return Command::SUCCESS;
}
```

### Progress Bar

```php
use Symfony\Component\Console\Helper\ProgressBar;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $users = $this->getUsersToProcess();

    $progressBar = new ProgressBar($output, count($users));
    $progressBar->start();

    foreach ($users as $user) {
        $this->processUser($user);
        $progressBar->advance();
    }

    $progressBar->finish();
    $output->writeln('');

    return Command::SUCCESS;
}
```

### Interactive Input

```php
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $helper = $this->getHelper('question');

    // Text question
    $question = new Question('Please enter the username: ');
    $username = $helper->ask($input, $output, $question);

    // Confirmation question
    $question = new ConfirmationQuestion('Continue with this action? (y/n) ', false);
    if (!$helper->ask($input, $output, $question)) {
        return Command::FAILURE;
    }

    // Choice question
    $question = new ChoiceQuestion(
        'Please select your role',
        ['admin', 'user', 'moderator'],
        0
    );
    $role = $helper->ask($input, $output, $question);

    return Command::SUCCESS;
}
```

## Dependency Injection

### Constructor Injection

```php
use App\Service\UserService;
use Psr\Log\LoggerInterface;

#[AsCommand(name: 'app:sync-users')]
class SyncUsersCommand extends Command
{
    public function __construct(
        private UserService $userService,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Starting user sync');

        $count = $this->userService->syncFromExternalSource();

        $output->writeln(sprintf('Synced %d users', $count));

        return Command::SUCCESS;
    }
}
```

## Command Lifecycle

### Initialize Method

```php
protected function initialize(InputInterface $input, OutputInterface $output): void
{
    // Called before interact() and execute()
    // Set up state, validate environment
    $this->startTime = microtime(true);
}
```

### Interact Method

```php
protected function interact(InputInterface $input, OutputInterface $output): void
{
    // Called after initialize() and before execute()
    // Gather missing input interactively

    if (!$input->getArgument('email')) {
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the email address: ');
        $email = $helper->ask($input, $output, $question);
        $input->setArgument('email', $email);
    }
}
```

## Error Handling

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    try {
        $this->processData();
        $io->success('Operation completed successfully');
        return Command::SUCCESS;

    } catch (\Exception $e) {
        $io->error('Operation failed: ' . $e->getMessage());
        $this->logger->error('Command failed', [
            'exception' => $e,
            'command' => self::class,
        ]);
        return Command::FAILURE;
    }
}
```

## Testing Commands

```php
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Creating user: Test User', $output);
    }
}
```

## Running Commands

### From CLI

```bash
# Basic usage
php bin/console app:create-user user@example.com "John Doe"

# With options
php bin/console app:export-users --format=json --limit=50 --active-only

# Help
php bin/console app:create-user --help
```

### From Code

```php
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$command = $application->find('app:create-user');
$input = new ArrayInput([
    'email' => 'user@example.com',
    'name' => 'John Doe',
]);
$output = new BufferedOutput();

$returnCode = $command->run($input, $output);
$content = $output->fetch();
```

## Further Reading

- [Symfony Console Component Documentation](https://symfony.com/doc/current/components/console.html)
- [Console Commands - Symfony Docs](https://symfony.com/doc/current/console.html)
- [Symfony Console Best Practices](https://symfony.com/doc/current/best_practices.html#console-commands)
