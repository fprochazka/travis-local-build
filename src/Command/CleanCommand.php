<?php

declare(strict_types = 1);

namespace Fprochazka\TravisLocalBuild\Command;

use Fprochazka\TravisLocalBuild\Docker\Docker;
use Fprochazka\TravisLocalBuild\Travis\BuildExecutor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CleanCommand extends \Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('clean');
		$this->setDescription('Removes Docker images of Travis-local jobs');
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$docker = new Docker(true, BuildExecutor::CONTAINER_MARKER_LABEL);

		$imageIds = $docker->getImageIds();
		if (count($imageIds) === 0) {
			$output->writeln('<info>No images found, all clean</info>');
		}

		while (count($imageIds) > 0) {
			$image = $docker->getImageDetails(array_shift($imageIds));

			try {
				$docker->removeImage($image->getId())->wait();
				$output->writeln(sprintf('<info>Removed %s with id %s</info>', $image->getTag(), $image->getId()));

			} catch (ProcessFailedException $e) {
				$imageIds[] = $image->getId();
				$output->writeln(sprintf('<error>Failed to remove %s with id %s</error>', $image->getTag(), $image->getId()));
			}
		}

		return 0;
	}

}
