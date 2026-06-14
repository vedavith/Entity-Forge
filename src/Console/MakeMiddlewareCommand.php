<?php

namespace EntityForge\Console;

use EntityForge\Generator\Builder\MiddlewareBuilder;
use EntityForge\Generator\Writer\FileWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddlewareCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:middleware')
            ->setDescription('Scaffold a new middleware class')
            ->addArgument('name', InputArgument::REQUIRED, 'Middleware class name (e.g. AuthMiddleware)')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output directory', 'app/Http/Middleware')
            ->addOption('auth', null, InputOption::VALUE_NONE, 'Scaffold an auth middleware stub (implements AuthMiddlewareInterface)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $outputDir = (string) $input->getOption('output');

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            $output->writeln("<error>Invalid middleware name '{$name}': use PascalCase letters and numbers only.</error>");
            return Command::FAILURE;
        }

        $builder = new MiddlewareBuilder();
        $code = $input->getOption('auth') ? $builder->buildAuth($name) : $builder->build($name);
        $path = "{$outputDir}/{$name}.php";

        (new FileWriter())->write($path, $code);

        $output->writeln("<info>Created {$path}</info>");

        return Command::SUCCESS;
    }
}
