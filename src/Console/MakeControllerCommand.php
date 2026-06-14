<?php

namespace EntityForge\Console;

use EntityForge\Generator\Builder\ControllerBuilder;
use EntityForge\Generator\Writer\FileWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeControllerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:controller')
            ->setDescription('Scaffold a new controller class with CRUD stubs')
            ->addArgument('name', InputArgument::REQUIRED, 'Controller class name (e.g. UserController)')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output directory', 'app/Http/Controller');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $outputDir = (string) $input->getOption('output');

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            $output->writeln("<error>Invalid controller name '{$name}': use PascalCase letters and numbers only.</error>");
            return Command::FAILURE;
        }

        $code = (new ControllerBuilder())->build($name);
        $path = "{$outputDir}/{$name}.php";

        (new FileWriter())->write($path, $code);

        $output->writeln("<info>Created {$path}</info>");

        return Command::SUCCESS;
    }
}
