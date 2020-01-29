<?php

namespace PHPCensor\Command;

use Exception;
use Monolog\Logger;
use Pheanstalk\Pheanstalk;
use PHPCensor\Config;
use PHPCensor\Service\BuildService;
use PHPCensor\Worker\BuildWorker;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Command to show the status of the queue used by the worker.
 *
 * @author Simon Heimberg <simon.heimberg@heimberg-ea.ch>
 */
class WorkerQueueCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('php-censor:status:worker-queue')
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'show all status'
            )
            ->addArgument(
                'names',
                InputArgument::IS_ARRAY,
                'list given stati, shows some by default'
            )
            ->setDescription('Shows stats about pheanstalk queue used by worker.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Config::getInstance()->get('php-censor.queue', []);
        if (empty($config['host']) || empty($config['name'])) {
            throw new RuntimeException(
                'The worker is not configured. You must set a host and queue in your config.yml file.'
            );
        }
        $queue = new PheanStalk($config['host'], Config::getInstance()->get('php-censor.queue.port', Pheanstalk::DEFAULT_PORT));

        $aw = $queue->statsTube($config['name']);

        if ($input->getOption('all')) {
            $keys = array_keys($aw->getArrayCopy());
        } elseif (!$input->getArgument('names')) {
            $keys = ['name', 'current-using', 'current-watching', 'current-jobs-ready', 'current-jobs-reserved'];
        } else {
            $keys = $input->getArgument('names');
        }
        foreach ($keys as $element) {
            $value = isset($aw[$element]) ? $aw[$element] : '<error>--</>';
            $output->writeln("<info>$element</>: $value");
        }
    }
}
