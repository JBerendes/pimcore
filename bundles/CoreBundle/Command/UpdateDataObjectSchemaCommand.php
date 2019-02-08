<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\ClassDefinition\SchemaBuilderInterface;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDataObjectSchemaCommand extends AbstractCommand
{
    /**
     * @var SchemaBuilderInterface
     */
    private $schemaBuilder;

    /**
     * @param SchemaBuilderInterface $schemaBuilder
     */
    public function __construct(SchemaBuilderInterface $schemaBuilder)
    {
        parent::__construct();

        $this->schemaBuilder = $schemaBuilder;
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pimcore:data-object:schema:update')
            ->setDescription('Updates the DB Schema')
            ->addOption(
                'dump-sql',
                null,
                InputOption::VALUE_OPTIONAL,
                'Dump Generated SQL'
            )
            ->addOption(
                'classes',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Classes to Rebuild'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $classDefinitions = new ClassDefinition\Listing();

        if ($input->getOption('classes')) {
            $classDefinitions->setCondition('name IN (?)', explode(',', $input->getOption('classes')));
        }

        $classDefinitions->load();

        /**
         * @var ClassDefinition $classDefinition
         */
        foreach ($classDefinitions->getClasses() as $classDefinition) {
            $output->writeln(sprintf('Schema Diff for %s', $classDefinition->getName()));
            $output->writeln($this->schemaBuilder->getMigrateSchema($classDefinition));
            $output->writeln('');
        }

        return 0;
    }
}
