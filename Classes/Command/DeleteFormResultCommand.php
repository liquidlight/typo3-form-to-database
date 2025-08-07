<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Command;

use Lavitto\FormToDatabase\Domain\Model\FormResult;
use Lavitto\FormToDatabase\Domain\Repository\FormResultRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class DeleteFormResultCommand
 */
#[AsCommand(name: 'form_to_database:deleteFormResults', description: 'Deletes form results.')]
final class DeleteFormResultCommand extends Command
{
    protected FormResultRepository $formResultRepository;

    protected PersistenceManager $persistenceManager;

    /**
     * Initialize the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // needed for Extbase configuration
        // if not set, Configuration will not get loaded
        // and the repository can't create a query
        // @todo this is hacky! The command should be refactored not using Extbase!
        $GLOBALS['TYPO3_REQUEST'] ??= (new ServerRequest('/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->formResultRepository = GeneralUtility::makeInstance(FormResultRepository::class);
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Deletes form results')
            ->setHelp('Deletes results older than maxAge (in days).')
            ->addArgument(
                'maxAge',
                InputArgument::OPTIONAL,
                'Maximum age of form results in days',
                90
            );
    }

    /**
     * Executes the command for adding or removing the lock file
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws InvalidQueryException
     * @throws IllegalObjectTypeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $maxAge = (int)$input->getArgument('maxAge');

        $ret = 1;
        if ($maxAge > 0) {
            $formResults = $this->formResultRepository->findByMaxAge($maxAge);
            $count = $formResults->count();
            if ($count > 0) {
                /** @var FormResult $formResult */
                foreach ($formResults as $formResult) {
                    $this->formResultRepository->remove($formResult);
                }
                $this->persistenceManager->persistAll();
            }
            if ($output->isVerbose()) {
                if ($count > 0) {
                    $io->success($count . ' form results deleted.');
                } else {
                    $io->success('Nothing to delete.');
                }
            }
            $ret = 0;
        } else {
            $io->error('maxAge must be a valid integer');
        }

        return $ret;
    }
}
