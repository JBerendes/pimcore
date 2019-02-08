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

namespace Pimcore\ClassDefinition;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Pimcore\Model;

class SchemaBuilder implements SchemaBuilderInterface
{
    /**
     * @var Connection
     */
    private $database;

    /**
     * @param Connection $database
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(Model\DataObject\ClassDefinition $classDefinition): Schema
    {
        $queryTableName = 'object_query_'.$classDefinition->getId();
        $storeTableName = 'object_store_'.$classDefinition->getId();
        $relationsTableName = 'object_relations_'.$classDefinition->getId();
        $objectView = 'object_'.$classDefinition->getId();

        $newSchema = new Schema();

        $queryTable = $this->getQueryTable($queryTableName, $newSchema, $classDefinition);
        $storeTable = $this->getStoreTable($storeTableName, $newSchema, $classDefinition);
        $relationsTable = $this->getRelationsTable($relationsTableName, $newSchema, $classDefinition);

        $fieldDefinitions = $classDefinition->getFieldDefinitions();

        if (is_array($fieldDefinitions) && count($fieldDefinitions)) {
            foreach ($fieldDefinitions as $key => $value) {
                if ($value instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface || method_exists($value,
                        'getDataForResource')) {
                    if (!$value->isRelationType()) {

                        if (!$value instanceof Model\DataObject\ClassDefinition\Data\ResourceSchemaColumnsAwareInterface) {
                            //throw new \InvalidArgumentException(sprintf('Your data "%s" has to implement "%s"', get_class($value), Model\DataObject\ClassDefinition\Data\ResourceSchemaColumnsAwareInterface::class));
                        }
                        else {
                            $this->addColumnsToTable($storeTable, $value, $value->getSchemaColumns());
                        }
                    }
                }

                if ($value instanceof Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface || method_exists($value, 'getDataForQueryResource')) {
                    if (!$value instanceof Model\DataObject\ClassDefinition\Data\QueryResourceSchemaColumnsAwareInterface) {
                        //throw new \InvalidArgumentException(sprintf('Your data "%s" has to implement "%s"', get_class($value), Model\DataObject\ClassDefinition\Data\QueryResourceSchemaColumnsAwareInterface::class));
                    }
                    else {
                        $this->addColumnsToTable($queryTable, $value, $value->getQuerySchemaColumns());
                    }
                }
            }
        }

        return $newSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrateSchema(Model\DataObject\ClassDefinition $classDefinition): string
    {
        $schemaManager = $this->database->getSchemaManager();

        $queryTableName = 'object_query_'.$classDefinition->getId();
        $storeTableName = 'object_store_'.$classDefinition->getId();
        $relationsTableName = 'object_relations_'.$classDefinition->getId();
        $objectView = 'object_'.$classDefinition->getId();
        $oldTables = [];

         foreach ([$queryTableName, $storeTableName, $relationsTableName] as $searchTableName) {
            if ($schemaManager->tablesExist([$searchTableName])) {
                $oldTables[] = $schemaManager->listTableDetails($searchTableName);
            }
        }

        $newSchema = $this->buildSchema($classDefinition);
        $oldSchema = new Schema($oldTables);

        //Doctrine Schema doesn't support Views....
        $view = 'CREATE OR REPLACE VIEW `'.$objectView.'` AS SELECT * FROM `'.$queryTableName.'` JOIN `objects` ON `objects`.`o_id` = `'.$queryTableName.'`.`oo_id`;';

        $diff = implode(PHP_EOL, $newSchema->getMigrateFromSql($oldSchema, $this->database->getDatabasePlatform()));
        $diff .= PHP_EOL.$view;

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($oldSchema, $newSchema);
        $relationDeleteSqls = [];

        foreach ($schemaDiff->changedTables as $changedTable) {
            if ($changedTable->getNewName() !== $storeTableName) {
                continue;
            }

            foreach ($changedTable->removedColumns as $column) {
                $qb = new QueryBuilder($this->database);
                $qb->from($relationsTableName)
                    ->where('fieldname = :fieldname')
                    ->andWhere('ownertype = "object"')
                    ->setParameter('fieldname', $column)
                    ->delete();

                $relationDeleteSqls[] = $qb->getSQL();
            }
        }

        $diff .= PHP_EOL.implode(PHP_EOL, $relationDeleteSqls);

        return $diff;
    }

    /**
     * @param Table                                 $table
     * @param Model\DataObject\ClassDefinition\Data $fd
     * @param array                                 $schemaColumns
     */
    private function addColumnsToTable(Table $table, Model\DataObject\ClassDefinition\Data $fd, array $schemaColumns)
    {
        foreach ($schemaColumns as $col) {
            if (!$col instanceof Column) {
                throw new \InvalidArgumentException(sprintf('Expected Type %s, got type %s', Column::class, get_class($col)));
            }

            $table->addColumn($col->getName(), $col->getType()->getName(), $col->toArray());
        }

        if ($fd->getIndex()) {
            $indexFields = [];

            foreach ($schemaColumns as $column) {
                $indexFields[] = $column->getName();
            }

            if ($fd->getUnique()) {
                $table->addUniqueIndex($indexFields);
            } else {
                $table->addIndex($indexFields);
            }
        }
    }

    /**
     * @param                                  $tableName
     * @param Schema                           $schema
     * @param Model\DataObject\ClassDefinition $classDefinition
     * @return Table
     */
    private function getQueryTable(
        $tableName,
        Schema $schema,
        Model\DataObject\ClassDefinition $classDefinition
    ): Table {
        $objectTable = $schema->createTable($tableName);
        $objectTable->addColumn('oo_id', 'integer', [
            'notnull' => true,
            'length' => 11,
            'default' => 0,
        ]);
        $objectTable->addColumn('oo_classId', 'string', [
            'length' => 50,
            'notnull' => false,
            'default' => $classDefinition->getId(),
        ]);
        $objectTable->addColumn('oo_className', 'string', [
            'length' => 255,
            'notnull' => false,
            'default' => $classDefinition->getName(),
        ]);
        $objectTable->setPrimaryKey(['oo_id']);

        return $objectTable;
    }

    /**
     * @param                                  $tableName
     * @param Schema                           $schema
     * @param Model\DataObject\ClassDefinition $classDefinition
     * @return Table
     */
    private function getStoreTable(
        $tableName,
        Schema $schema,
        Model\DataObject\ClassDefinition $classDefinition
    ): Table {
        $dataStoreTable = $schema->createTable($tableName);
        $dataStoreTable->addColumn('oo_id', 'integer', [
            'length' => 11,
            'notnull' => true,
            'default' => 0,
        ]);
        $dataStoreTable->setPrimaryKey(['oo_id']);

        return $dataStoreTable;
    }

    /**
     * @param                                  $tableName
     * @param Schema                           $schema
     * @param Model\DataObject\ClassDefinition $classDefinition
     * @return Table
     */
    private function getRelationsTable(
        $tableName,
        Schema $schema,
        Model\DataObject\ClassDefinition $classDefinition
    ): Table {
        $dataStoreRelationsTable = $schema->createTable($tableName);
        $dataStoreRelationsTable->addColumn('src_id', 'integer', [
            'length' => 11,
            'notnull' => true,
            'default' => 0,
        ]);
        $dataStoreRelationsTable->addColumn('dest_id', 'integer', [
            'length' => 11,
            'notnull' => true,
            'default' => 0,
        ]);
        $dataStoreRelationsTable->addColumn('type', 'string', [
            'length' => 50,
            'notnull' => true,
            'default' => '',
        ]);
        $dataStoreRelationsTable->addColumn('fieldname', 'string', [
            'length' => 70,
            'notnull' => true,
            'default' => '0',
        ]);
        $dataStoreRelationsTable->addColumn('index', 'integer', [
            'length' => 11,
            'unsigned' => true,
            'notnull' => true,
            'default' => '0',
        ]);
        $dataStoreRelationsTable->addColumn('ownertype', 'string', [
            'columnDefinition' => "enum('object','fieldcollection','localizedfield','objectbrick')",
            'notnull' => true,
            'default' => 'object',
        ]);
        $dataStoreRelationsTable->addColumn('ownername', 'string', [
            'length' => 70,
            'notnull' => true,
            'default' => '',
        ]);
        $dataStoreRelationsTable->addColumn('position', 'string', [
            'length' => 70,
            'notnull' => true,
            'default' => '0',
        ]);
        $dataStoreRelationsTable->setPrimaryKey([
            'src_id',
            'dest_id',
            'ownertype',
            'ownername',
            'fieldname',
            'type',
            'position',
        ]);
        $dataStoreRelationsTable->addIndex(['index']);
        $dataStoreRelationsTable->addIndex(['src_id']);
        $dataStoreRelationsTable->addIndex(['dest_id']);
        $dataStoreRelationsTable->addIndex(['fieldname']);
        $dataStoreRelationsTable->addIndex(['position']);
        $dataStoreRelationsTable->addIndex(['ownertype']);
        $dataStoreRelationsTable->addIndex(['type']);
        $dataStoreRelationsTable->addIndex(['ownername']);

        return $dataStoreRelationsTable;
    }
}
