<?php


namespace AppBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class IterationCommand extends ContainerAwareCommand
{

	protected $iterationsCount = 1000;
	protected $interval = 5;
	

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($this->isLock()) {
			$output->writeln('The command is already locked in another process.');
			sleep(60);
			return 0;
		}

		$iterations = $this->getIterationsCount();
		while ($iterations-- > 0) {
			$this->executionBlock($input, $output);
			$this->prolongLock();
			sleep($this->getInterval());
		}
		return 0;
	}

	protected function isLock()
	{
		return $this->getContainer()->get('snc_redis.default')
				->set("COMMAND_LOCK:{$this->getName()}", '1', 'EX', 60, 'NX') === null;
	}

	protected function removeLock()
	{
		$this->getContainer()->get('snc_redis.default')->del(["COMMAND_LOCK:{$this->getName()}"]);
	}

	protected function prolongLock()
	{
		$this->getContainer()->get('snc_redis.default')->expire('COMMAND_LOCK:' . $this->getName(), 60);
	}

	public function __destruct()
	{
		$this->removeLock();
	}

	protected function executionBlock(InputInterface $input, OutputInterface $output)
	{
		return 1;
	}

	/**
	 * @param int $iterationsCount
	 * @return IterationCommand
	 */
	public function setIterationsCount(int $iterationsCount): IterationCommand
	{
		$this->iterationsCount = $iterationsCount;
		return $this;
	}

	/**
	 * @param int $interval
	 * @return IterationCommand
	 */
	public function setInterval(int $interval): IterationCommand
	{
		$this->interval = $interval;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getIterationsCount(): int
	{
		return $this->iterationsCount;
	}

	/**
	 * @return int
	 */
	public function getInterval(): int
	{
		return $this->interval;
	}


}