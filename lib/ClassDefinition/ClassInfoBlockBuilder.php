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

use Pimcore\Model;

class ClassInfoBlockBuilder implements ClassInfoBlockBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildInfoBlock(Model\DataObject\ClassDefinition $classDefinition)
    {
        $cd = '';

        $cd .= '/** ';
        $cd .= "\n";
        $cd .= '* Generated at: '.date('c')."\n";
        $cd .= '* Inheritance: '.($classDefinition->getAllowInherit() ? 'yes' : 'no')."\n";
        $cd .= '* Variants: '.($classDefinition->getAllowVariants() ? 'yes' : 'no')."\n";

        $user = Model\User::getById($classDefinition->getUserModification());
        if ($user) {
            $cd .= '* Changed by: '.$user->getName().' ('.$user->getId().')'."\n";
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $cd .= '* IP: '.$_SERVER['REMOTE_ADDR']."\n";
        }

        if ($classDefinition->getDescription()) {
            $description = str_replace(['/**', '*/', '//'], '', $classDefinition->getDescription());
            $description = str_replace("\n", "\n* ", $description);

            $cd .= '* '.$description."\n";
        }

        $cd .= "\n\n";
        $cd .= "Fields Summary: \n";

        $cd = $this->getInfoDocBlockForFields($classDefinition, $cd, 1);

        $cd .= '*/ ';

        return $cd;
    }

    /**
     * @param Model\DataObject\ClassDefinition $definition
     * @param                                  $text
     * @param                                  $level
     * @return string
     */
    private function getInfoDocBlockForFields(Model\DataObject\ClassDefinition $definition, $text, $level)
    {
        foreach ($definition->getFieldDefinitions() as $fd) {
            $text .= str_pad('', $level, '-').' '.$fd->getName().' ['.$fd->getFieldtype()."]\n";

            if (method_exists($fd, 'getFieldDefinitions')) {
                $text = $this->getInfoDocBlockForFields($fd, $text, $level + 1);
            }
        }

        return $text;
    }
}
