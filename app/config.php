<?php

use Colors\Color;
use function DI\object;
use function DI\factory;
use Kadet\Highlighter\KeyLighter;
use function PhpSchool\PhpWorkshop\Event\containerListener;
use Interop\Container\ContainerInterface;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use PhpParser\PrettyPrinter\Standard;
use PhpSchool\CliMenu\Terminal\TerminalFactory;
use PhpSchool\CliMenu\Terminal\TerminalInterface;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpSchool\PhpWorkshop\Check\CheckRepository;
use PhpSchool\PhpWorkshop\Check\CodeParseCheck;
use PhpSchool\PhpWorkshop\Check\ComposerCheck;
use PhpSchool\PhpWorkshop\Check\DatabaseCheck;
use PhpSchool\PhpWorkshop\CodeInsertion as Insertion;
use PhpSchool\PhpWorkshop\CodePatcher;
use PhpSchool\PhpWorkshop\Event\EventDispatcher;
use PhpSchool\PhpWorkshop\ExerciseDispatcher;
use PhpSchool\PhpWorkshop\ExerciseRunner\Factory\CgiRunnerFactory;
use PhpSchool\PhpWorkshop\ExerciseRunner\Factory\CliRunnerFactory;
use PhpSchool\PhpWorkshop\ExerciseRunner\Factory\CustomVerifyingRunnerFactory;
use PhpSchool\PhpWorkshop\ExerciseRunner\RunnerManager;
use PhpSchool\PhpWorkshop\Factory\EventDispatcherFactory;
use PhpSchool\PhpWorkshop\Factory\MenuFactory;
use PhpSchool\PhpWorkshop\Factory\ResultRendererFactory;
use PhpSchool\PhpWorkshop\Listener\CheckExerciseAssignedListener;
use PhpSchool\PhpWorkshop\Listener\CodePatchListener;
use PhpSchool\PhpWorkshop\Listener\ConfigureCommandListener;
use PhpSchool\PhpWorkshop\Listener\PrepareSolutionListener;
use PhpSchool\PhpWorkshop\Listener\RealPathListener;
use PhpSchool\PhpWorkshop\Listener\SelfCheckListener;
use PhpSchool\PhpWorkshop\MenuItem\ResetProgress;
use PhpSchool\PhpWorkshop\Output\OutputInterface;
use PhpSchool\PhpWorkshop\Output\StdOutput;
use PhpSchool\PhpWorkshop\Patch;
use PhpSchool\PhpWorkshop\Result\Cgi\GenericFailure as CgiGenericFailure;
use PhpSchool\PhpWorkshop\Result\Cgi\RequestFailure as CgiRequestFailure;
use PhpSchool\PhpWorkshop\Result\Cgi\CgiResult;
use PhpSchool\PhpWorkshop\Result\Cli\CliResult;
use PhpSchool\PhpWorkshop\Result\Cli\GenericFailure as CliGenericFailure;
use PhpSchool\PhpWorkshop\Result\Cli\RequestFailure as CliRequestFailure;
use PhpSchool\PhpWorkshop\Result\ComparisonFailure;
use PhpSchool\PhpWorkshop\Result\Failure;
use PhpSchool\PhpWorkshop\Result\FunctionRequirementsFailure;
use PhpSchool\PhpWorkshop\ResultAggregator;
use PhpSchool\PhpWorkshop\ResultRenderer\CgiResultRenderer;
use PhpSchool\PhpWorkshop\ResultRenderer\CliResultRenderer;
use PhpSchool\PhpWorkshop\ResultRenderer\ComparisonFailureRenderer;
use PhpSchool\PhpWorkshop\ResultRenderer\FailureRenderer;
use PhpSchool\PhpWorkshop\ResultRenderer\FunctionRequirementsFailureRenderer;
use PhpSchool\PhpWorkshop\ResultRenderer\Cli\RequestFailureRenderer as CliRequestFailureRenderer;
use PhpSchool\PhpWorkshop\ResultRenderer\Cgi\RequestFailureRenderer as CgiRequestFailureRenderer;
use PhpSchool\PhpWorkshop\Utils\RequestRenderer;
use PhpSchool\PhpWorkshop\WorkshopType;
use PhpSchool\PhpWorkshop\Check\FileExistsCheck;
use PhpSchool\PhpWorkshop\Check\FunctionRequirementsCheck;
use PhpSchool\PhpWorkshop\Check\PhpLintCheck;
use PhpSchool\PhpWorkshop\Command\CreditsCommand;
use PhpSchool\PhpWorkshop\Command\HelpCommand;
use PhpSchool\PhpWorkshop\Command\MenuCommand;
use PhpSchool\PhpWorkshop\Command\PrintCommand;
use PhpSchool\PhpWorkshop\Command\VerifyCommand;
use PhpSchool\PhpWorkshop\Command\RunCommand;
use PhpSchool\PhpWorkshop\CommandDefinition;
use PhpSchool\PhpWorkshop\CommandRouter;
use PhpSchool\PhpWorkshop\ExerciseRenderer;
use PhpSchool\PhpWorkshop\ExerciseRepository;
use PhpSchool\PhpWorkshop\Factory\MarkdownCliRendererFactory;
use PhpSchool\PhpWorkshop\MarkdownRenderer;
use PhpSchool\PhpWorkshop\ResultRenderer\ResultsRenderer;
use PhpSchool\PhpWorkshop\UserState;
use PhpSchool\PhpWorkshop\UserStateSerializer;
use Symfony\Component\Filesystem\Filesystem;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;

return [
    'appName' => basename($_SERVER['argv'][0]),
    WorkshopType::class => WorkshopType::STANDARD(),
    ExerciseDispatcher::class => function (ContainerInterface $c) {
        return new ExerciseDispatcher(
            $c->get(RunnerManager::class),
            $c->get(ResultAggregator::class),
            $c->get(EventDispatcher::class),
            $c->get(CheckRepository::class)
        );
    },
    ResultAggregator::class => object(ResultAggregator::class),
    CheckRepository::class => function (ContainerInterface $c) {
        return new CheckRepository([
            $c->get(FileExistsCheck::class),
            $c->get(PhpLintCheck::class),
            $c->get(CodeParseCheck::class),
            $c->get(ComposerCheck::class),
            $c->get(FunctionRequirementsCheck::class),
            $c->get(DatabaseCheck::class),
        ]);
    },
    CommandRouter::class => function (ContainerInterface $c) {
        return new CommandRouter(
            [
                new CommandDefinition('menu', [], MenuCommand::class),
                new CommandDefinition('help', [], HelpCommand::class),
                new CommandDefinition('print', [], PrintCommand::class),
                new CommandDefinition('verify', [], VerifyCommand::class),
                new CommandDefinition('run', [], RunCommand::class),
                new CommandDefinition('credits', [], CreditsCommand::class)
            ],
            'menu',
            $c->get(EventDispatcher::class),
            $c
        );
    },

    Color::class => function () {
        $colors = new Color;
        $colors->setForceStyle(true);
        return $colors;
    },
    OutputInterface::class => function (ContainerInterface $c) {
        return new StdOutput($c->get(Color::class), $c->get(TerminalInterface::class));
    },

    ExerciseRepository::class => function (ContainerInterface $c) {
        return new ExerciseRepository(
            array_map(function ($exerciseClass) use ($c) {
                return $c->get($exerciseClass);
            }, $c->get('exercises'))
        );
    },

    EventDispatcher::class => factory(EventDispatcherFactory::class),
    EventDispatcherFactory::class => object(),

    //Exercise Runners
    RunnerManager::class => function (ContainerInterface $c) {
        $manager = new RunnerManager;
        $manager->addFactory(new CliRunnerFactory($c->get(EventDispatcher::class)));
        $manager->addFactory(new CgiRunnerFactory($c->get(EventDispatcher::class), $c->get(RequestRenderer::class)));
        $manager->addFactory(new CustomVerifyingRunnerFactory);
        return $manager;
    },

    //commands
    MenuCommand::class => function (ContainerInterface $c) {
        return new MenuCommand($c->get('menu'));
    },

    PrintCommand::class => function (ContainerInterface $c) {
        return new PrintCommand(
            $c->get('appName'),
            $c->get(ExerciseRepository::class),
            $c->get(UserState::class),
            $c->get(MarkdownRenderer::class),
            $c->get(OutputInterface::class)
        );
    },

    VerifyCommand::class => function (ContainerInterface $c) {
        return new VerifyCommand(
            $c->get(ExerciseRepository::class),
            $c->get(ExerciseDispatcher::class),
            $c->get(UserState::class),
            $c->get(UserStateSerializer::class),
            $c->get(OutputInterface::class),
            $c->get(ResultsRenderer::class)
        );
    },

    RunCommand::class => function (ContainerInterface $c) {
        return new RunCommand(
            $c->get(ExerciseRepository::class),
            $c->get(ExerciseDispatcher::class),
            $c->get(UserState::class),
            $c->get(OutputInterface::class)
        );
    },

    CreditsCommand::class => function (ContainerInterface $c) {
        return new CreditsCommand(
            $c->get('coreContributors'),
            $c->get('appContributors'),
            $c->get(OutputInterface::class),
            $c->get(Color::class)
        );
    },

    HelpCommand::class => function (ContainerInterface $c) {
        return new HelpCommand(
            $c->get('appName'),
            $c->get(OutputInterface::class),
            $c->get(Color::class)
        );
    },

    //Listeners
    PrepareSolutionListener::class       => object(),
    CodePatchListener::class             => function (ContainerInterface $c) {
        return new CodePatchListener($c->get(CodePatcher::class));
    },
    SelfCheckListener::class             => function (ContainerInterface $c) {
        return new SelfCheckListener($c->get(ResultAggregator::class));
    },
    CheckExerciseAssignedListener::class => function (ContainerInterface $c) {
        return new CheckExerciseAssignedListener($c->get(UserState::class));
    },
    ConfigureCommandListener::class      => function (ContainerInterface $c) {
        return new ConfigureCommandListener(
            $c->get(UserState::class),
            $c->get(ExerciseRepository::class),
            $c->get(RunnerManager::class)
        );
    },
    RealPathListener::class => object(),

    //checks
    FileExistsCheck::class              => object(),
    PhpLintCheck::class                 => object(),
    CodeParseCheck::class               => function (ContainerInterface $c) {
        return new CodeParseCheck($c->get(Parser::class));
    },
    FunctionRequirementsCheck::class    => function (ContainerInterface $c) {
        return new FunctionRequirementsCheck($c->get(Parser::class));
    },
    DatabaseCheck::class                => object(),
    ComposerCheck::class                => object(),

    //Utils
    Filesystem::class   => object(),
    Parser::class       => function () {
        $parserFactory = new ParserFactory;
        return $parserFactory->create(ParserFactory::PREFER_PHP7);
    },
    CodePatcher::class  => function (ContainerInterface $c) {
        $patch = (new Patch)
            ->withInsertion(new Insertion(Insertion::TYPE_BEFORE, 'ini_set("display_errors", 1);'))
            ->withInsertion(new Insertion(Insertion::TYPE_BEFORE, 'error_reporting(E_ALL);'))
            ->withInsertion(new Insertion(Insertion ::TYPE_BEFORE, 'date_default_timezone_set("Europe/London");'));
        
        return new CodePatcher($c->get(Parser::class), new Standard, $patch);
    },
    FakerGenerator::class => function () {
        return FakerFactory::create();
    },
    RequestRenderer::class => object(),
    
    TerminalInterface::class => factory([TerminalFactory::class, 'fromSystem']),
    'menu' => factory(MenuFactory::class),
    MenuFactory::class => object(),
    ExerciseRenderer::class => function (ContainerInterface $c) {
        return new ExerciseRenderer(
            $c->get('appName'),
            $c->get(ExerciseRepository::class),
            $c->get(UserState::class),
            $c->get(UserStateSerializer::class),
            $c->get(MarkdownRenderer::class),
            $c->get(Color::class),
            $c->get(OutputInterface::class)
        );
    },
    MarkdownRenderer::class => function (ContainerInterface $c) {
        $docParser =   new DocParser(Environment::createCommonMarkEnvironment());
        $cliRenderer = (new MarkdownCliRendererFactory)->__invoke($c);
        return new MarkdownRenderer($docParser, $cliRenderer);
    },
    UserStateSerializer::class => function (ContainerInterface $c) {
        return new UserStateSerializer(
            getenv('HOME'),
            $c->get('workshopTitle'),
            $c->get(ExerciseRepository::class)
        );
    },
    UserState::class => function (ContainerInterface $c) {
        return $c->get(UserStateSerializer::class)->deSerialize();
    },
    ResetProgress::class => function (ContainerInterface $c) {
        return new ResetProgress($c->get(UserStateSerializer::class));
    },
    ResultRendererFactory::class => function (ContainerInterface $c) {
        $factory = new ResultRendererFactory;
        $factory->registerRenderer(FunctionRequirementsFailure::class, FunctionRequirementsFailureRenderer::class);
        $factory->registerRenderer(Failure::class, FailureRenderer::class);
        $factory->registerRenderer(
            CgiResult::class,
            CgiResultRenderer::class,
            function (CgiResult $result) use ($c) {
                return new CgiResultRenderer($result, $c->get(RequestRenderer::class));
            }
        );
        $factory->registerRenderer(CgiGenericFailure::class, FailureRenderer::class);
        $factory->registerRenderer(CgiRequestFailure::class, CgiRequestFailureRenderer::class);

        $factory->registerRenderer(CliResult::class, CliResultRenderer::class);
        $factory->registerRenderer(CliGenericFailure::class, FailureRenderer::class);
        $factory->registerRenderer(CliRequestFailure::class, CliRequestFailureRenderer::class);

        $factory->registerRenderer(ComparisonFailure::class, ComparisonFailureRenderer::class);

        return $factory;
    },
    ResultsRenderer::class => function (ContainerInterface $c) {
        return new ResultsRenderer(
            $c->get('appName'),
            $c->get(Color::class),
            $c->get(TerminalInterface::class),
            $c->get(ExerciseRepository::class),
            $c->get(KeyLighter::class),
            $c->get(ResultRendererFactory::class)
        );
    },

    KeyLighter::class => function () {
        return new KeyLighter;
    },

    'coreContributors' => [
        '@AydinHassan' => 'Aydin Hassan',
        '@mikeymike'   => 'Michael Woodward',
        '@shakeyShane' => 'Shane Osbourne',
        '@chris3ailey' => 'Chris Bailey'
    ],
    'appContributors' => [],
    'eventListeners'  => [
        'realpath-student-submission' => [
            'verify.start' => [
                containerListener(RealPathListener::class)
            ],
            'run.start' => [
                containerListener(RealPathListener::class)
            ],
        ],
        'check-exercise-assigned' => [
            'route.pre.resolve.args' => [
                containerListener(CheckExerciseAssignedListener::class)
            ],
        ],
        'configure-command-arguments' => [
            'route.pre.resolve.args' => [
                containerListener(ConfigureCommandListener::class)
            ],
        ],
        'prepare-solution' => [
            'cli.verify.start' => [
                containerListener(PrepareSolutionListener::class),
            ],
            'cli.run.start' => [
                containerListener(PrepareSolutionListener::class),
            ],
            'cgi.verify.start' => [
                containerListener(PrepareSolutionListener::class),
            ],
            'cgi.run.start' => [
                containerListener(PrepareSolutionListener::class),
            ],
        ],
        'code-patcher' => [
            'cli.verify.start' => [
                containerListener(CodePatchListener::class, 'patch'),
            ],
            'cli.verify.finish' => [
                containerListener(CodePatchListener::class, 'revert'),
            ],
            'cli.run.start' => [
                containerListener(CodePatchListener::class, 'patch'),
            ],
            'cli.run.finish' => [
                containerListener(CodePatchListener::class, 'revert'),
            ],
            'cgi.verify.start' => [
                containerListener(CodePatchListener::class, 'patch'),
            ],
            'cgi.verify.finish' => [
                containerListener(CodePatchListener::class, 'revert'),
            ],
            'cgi.run.start' => [
                containerListener(CodePatchListener::class, 'patch'),
            ],
            'cgi.run.finish' => [
                containerListener(CodePatchListener::class, 'revert'),
            ],
        ],
        'self-check' => [
            'verify.post.check' => [
                containerListener(SelfCheckListener::class)
            ],
        ],
    ],
];
