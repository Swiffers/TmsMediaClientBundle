<?php

/**
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @license: MIT
 */

namespace Tms\Bundle\MediaClientBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;

class CleanUnlinkedMediaCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('tms-media:clean:unlinked')
            ->setDescription('Display or remove media with no relation.')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'The limit to processed', 10000)
            ->addOption('offset', 'o', InputOption::VALUE_REQUIRED, 'The offset to processed', 1)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'if present, orphan media will be removed')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command', 'default')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command.

To list all unlinked media:

<info>php app/console %command.name%</info>

To clean the unlinked media:

<info>php app/console %command.name% --force|-f</info>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timeStart = microtime(true);
        DoctrineCommandHelper::setApplicationEntityManager(
            $this->getApplication(),
            $input->getOption('em')
        );

        $entityManager = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
        ;

        $medias = $entityManager
            ->getRepository('TmsMediaClientBundle:Media')
            ->findBy(
                array(),
                array(),
                $input->getOption('limit'),
                $input->getOption('offset')
        );
        $orphans = array();

        $progress = new ProgressBar($output, count($medias));
        $output->writeln('');
        $progress->start();
        $table = new Table($output);
        $table->setHeaders(array('Action', 'ID', 'PublicUri'));

        foreach ($medias as $media) {
            $url = sprintf('http:%s.json', $media->getPublicUri());

            try {
                $statusCode = $this->getHttpStatusCode($url);
                if (200 !== $statusCode) {
                    $orphans[] = $media;
                    $action = 'TO REMOVE';

                    if ($input->getOption('force')) {
                        $entityManager->remove($media);
                        $action = 'REMOVED';
                    }

                    $table->addRow(array(
                        $action,
                        $media->getId(),
                        $media->getPublicUri()
                    ));
                }
            } catch (\Exception $e) {
                $table->addRow(array(
                    'ERROR: '.$e->getMessage(),
                    $media->getId(),
                    $media->getPublicUri()
                ));
            }

            $progress->advance();
        }

        $entityManager->flush();

        $progress->finish();
        $output->writeln('');
        $output->writeln('');

        $table->setStyle('borderless');
        $table->render();

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;

        $output->writeln('');
        $output->writeln(sprintf(
            '<comment>%d orphan media processed [%d sec]</comment>',
            count($orphans),
            $time
        ));
    }
}
