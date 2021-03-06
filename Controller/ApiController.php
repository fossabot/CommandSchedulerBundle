<?php

namespace JMose\CommandSchedulerBundle\Controller;

use JMose\CommandSchedulerBundle\Entity\ScheduledCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiController.
 *
 * @author Julien Guyon <julienguyon@hotmail.com>
 */
class ApiController extends AbstractBaseController
{
    private int $lockTimeout = 3600;
    private LoggerInterface $logger;

    /**
     * @param int $lockTimeout
     */
    public function setLockTimeout(int $lockTimeout): void
    {
        $this->lockTimeout = $lockTimeout;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param array $commands
     *
     * @return array
     */
    private function getCommandsAsArray(array $commands): array
    {
        $jsonArray = [];

        if (is_iterable($commands)) {
            foreach ($commands as $command) {
                $jsonArray[$command->getName()] = [
                    'NAME' => $command->getName(),
                    'COMMAND' => $command->getCommand(),
                    'ARGUMENTS' => $command->getArguments(),
                    'LAST_RETURN_CODE' => $command->getLastReturnCode(),
                    'B_LOCKED' => $command->getLocked() ? 'true' : 'false',
                    'DH_LAST_EXECUTION' => $command->getLastExecution(),
                    'DH_NEXT_EXECUTION' => $command->getNextRunDate(),
                    'LOGFILE' => $command->getLogFile(),
                ];
            }
        }

        return $jsonArray;
    }

    /**
     * List all commands.
     *
     * @return JsonResponse
     */
    public function listAction(): JsonResponse
    {
        $commands = $this->getDoctrineManager()
            ->getRepository(ScheduledCommand::class)
            ->findAll();

        return $this->json($this->getCommandsAsArray($commands));
    }

    /**
     * External check to monitor the health of the sheduled commands.
     *
     * method checks if there are jobs which are enabled but did not return 0 on last execution or are locked.
     * if a match is found, HTTP status 417 is sent along with an array
     * if no matches found, HTTP status 200 is sent with an empty array.
     *
     * @return JsonResponse
     */
    public function monitorAction(): JsonResponse
    {
        $failedCommands = $this->getDoctrineManager()
            ->getRepository(ScheduledCommand::class)
            ->findFailedAndTimeoutCommands($this->lockTimeout);

        $jsonArray = $this->getCommandsAsArray($failedCommands);

        if (count($failedCommands) > 1) {
            $this->logger->debug(
                'MonitorCommand found locked or timed out commands',
                ['amount' => count($failedCommands)]
            );
        } else {
            // HTTP_OK: no failed or timeout commands
            return new JsonResponse();
        }

        $response = new JsonResponse();
        try {
            $response->setContent(json_encode($jsonArray, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            $this->logger->error('MonitorCommand failed', ['message' => $e->getMessage()]);
        }

        // StatusCode 417 (error)
        return $response->setStatusCode(Response::HTTP_EXPECTATION_FAILED);
    }
}
