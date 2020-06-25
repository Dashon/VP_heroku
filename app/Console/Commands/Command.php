<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends \Illuminate\Console\Command
{

	/**
	 * Execute the console command.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return mixed
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Parent::execute($input, $output);
	}
}
