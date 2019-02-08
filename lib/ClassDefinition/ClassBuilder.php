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

use Pimcore\File;
use Pimcore\Model;

final class ClassBuilder implements ClassBuilderInterface
{
    /**
     * @var string
     */
    private $classDirectory;

    /**
     * @var ClassInfoBlockBuilderInterface
     */
    private $infoBlockBuilder;

    /**
     * @param string                         $classDirectory
     * @param ClassInfoBlockBuilderInterface $infoBlockBuilder
     */
    public function __construct(string $classDirectory, ClassInfoBlockBuilderInterface $infoBlockBuilder)
    {
        $this->classDirectory = $classDirectory;
        $this->infoBlockBuilder = $infoBlockBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function buildPhpClass(Model\DataObject\ClassDefinition $classDefinition)
    {
        if (!is_dir($this->classDirectory.'/DataObject')) {
            File::mkdir($this->classDirectory.'/DataObject');
        }

        $extendClass = 'Concrete';
        if ($classDefinition->getParentClass()) {
            $extendClass = $classDefinition->getParentClass();
            $extendClass = '\\'.ltrim($extendClass, '\\');
        }

        $infoDocBlock = $this->infoBlockBuilder->buildInfoBlock($classDefinition);

        $cd = '<?php ';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject;';
        $cd .= "\n\n";
        $cd .= "\n\n";
        $cd .= "/**\n";

        $fieldDefinitions = $classDefinition->getFieldDefinitions();

        if (is_array($fieldDefinitions) && count($fieldDefinitions)) {
            foreach ($fieldDefinitions as $key => $def) {
                if (!(method_exists($def, 'isRemoteOwner') and $def->isRemoteOwner())) {
                    if ($def instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
                        $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                                $classDefinition->getName()
                            ).'\Listing getBy'.ucfirst(
                                $classDefinition->getName()
                            ).' ($field, $value, $locale = null, $limit = 0) '."\n";
                    } else {
                        $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                                $classDefinition->getName()
                            ).'\Listing getBy'.ucfirst($def->getName()).' ($value, $limit = 0) '."\n";
                    }
                }
            }
        }
        $cd .= "*/\n\n";

        $cd .= 'class '.ucfirst($classDefinition->getName()).' extends '.$extendClass.' implements \\Pimcore\\Model\\DataObject\\DirtyIndicatorInterface {';
        $cd .= "\n\n";

        $cd .= "\n\n";
        $cd .= 'use \\Pimcore\\Model\\DataObject\\Traits\\DirtyIndicatorTrait;';
        $cd .= "\n\n";

        if ($classDefinition->getUseTraits()) {
            $cd .= 'use '.$classDefinition->getUseTraits().";\n";
            $cd .= "\n";
        }

        $cd .= 'protected $o_classId = "'.$classDefinition->getId()."\";\n";
        $cd .= 'protected $o_className = "'.$classDefinition->getName().'"'.";\n";

        if (is_array($fieldDefinitions) && count($fieldDefinitions)) {
            foreach ($fieldDefinitions as $key => $def) {
                if (!(method_exists($def,
                            'isRemoteOwner') && $def->isRemoteOwner()) && !$def instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue
                ) {
                    $cd .= 'protected $'.$key.";\n";
                }
            }
        }

        $cd .= "\n\n";
        $cd .= '/**'."\n";
        $cd .= '* @param array $values'."\n";
        $cd .= '* @return \\Pimcore\\Model\\DataObject\\'.ucfirst($classDefinition->getName())."\n";
        $cd .= '*/'."\n";
        $cd .= 'public static function create($values = array()) {';
        $cd .= "\n";
        $cd .= "\t".'$object = new static();'."\n";
        $cd .= "\t".'$object->setValues($values);'."\n";
        $cd .= "\t".'return $object;'."\n";
        $cd .= '}';

        $cd .= "\n\n";

        if (is_array($fieldDefinitions) && count($fieldDefinitions)) {
            $relationTypes = [];
            $lazyLoadedFields = [];

            foreach ($fieldDefinitions as $key => $def) {
                if (method_exists($def, 'isRemoteOwner') && $def->isRemoteOwner()) {
                    continue;
                }

                // get setter and getter code
                $cd .= $def->getGetterCode($this);
                $cd .= $def->getSetterCode($this);

                // call the method "classSaved" if exists, this is used to create additional data tables or whatever which depends on the field definition, for example for localizedfields
                if (method_exists($def, 'classSaved')) {
                    $def->classSaved($this);
                }

                if ($def->isRelationType()) {
                    $relationTypes[$key] = ['type' => $def->getFieldType()];
                }

                // collect lazyloaded fields
                if (method_exists($def, 'getLazyLoading') && $def->getLazyLoading()) {
                    $lazyLoadedFields[] = $key;
                }
            }

            $cd .= 'protected static $_relationFields = '.var_export($relationTypes, true).";\n\n";
            $cd .= 'protected $lazyLoadedFields = '.var_export($lazyLoadedFields, true).";\n\n";
        }

        $cd .= "}\n";
        $cd .= "\n";

        $classFile = $this->classDirectory.'/DataObject/'.ucfirst($classDefinition->getName()).'.php';

        if (!is_writable(dirname($classFile)) || (is_file($classFile) && !is_writable($classFile))) {
            throw new \Exception('Cannot write class file in '.$classFile.' please check the rights on this directory');
        }

        File::put($classFile, $cd);
    }
}
