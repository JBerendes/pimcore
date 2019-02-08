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
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geo\AbstractGeo;

class Geobounds extends AbstractGeo implements ResourcePersistenceAwareInterface, ResourceSchemaColumnsAwareInterface, QueryResourcePersistenceAwareInterface, QueryResourceSchemaColumnsAwareInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'geobounds';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\Geobounds';

    /**
     * {@inheritdoc}
     */
    public function getSchemaColumns(): array
    {
        return [
            new Column($this->getName() . '__NElongitude', Type::getType('float'), [
                'notnull' => false
            ]),
            new Column($this->getName() . '__NElatitude', Type::getType('float'), [
                'notnull' => false
            ]),
            new Column($this->getName() . '__SWlongitude', Type::getType('float'), [
                'notnull' => false
            ]),
            new Column($this->getName() . '__SWlatitude', Type::getType('float'), [
                'notnull' => false
            ])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getQuerySchemaColumns(): array
    {
        return $this->getSchemaColumns();
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\Geobounds $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Geobounds) {
            return [
                $this->getName() . '__NElongitude' => $data->getNorthEast()->getLongitude(),
                $this->getName() . '__NElatitude' => $data->getNorthEast()->getLatitude(),
                $this->getName() . '__SWlongitude' => $data->getSouthWest()->getLongitude(),
                $this->getName() . '__SWlatitude' => $data->getSouthWest()->getLatitude()
            ];
        }

        return [
            $this->getName() . '__NElongitude' => null,
            $this->getName() . '__NElatitude' => null,
            $this->getName() . '__SWlongitude' => null,
            $this->getName() . '__SWlatitude' => null
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return DataObject\Data\Geobounds|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__NElongitude'] && $data[$this->getName() . '__NElatitude'] && $data[$this->getName() . '__SWlongitude'] && $data[$this->getName() . '__SWlatitude']) {
            $ne = new DataObject\Data\Geopoint($data[$this->getName() . '__NElongitude'], $data[$this->getName() . '__NElatitude']);
            $sw = new DataObject\Data\Geopoint($data[$this->getName() . '__SWlongitude'], $data[$this->getName() . '__SWlatitude']);

            $geobounds = new DataObject\Data\Geobounds($ne, $sw);

            if (isset($params['owner'])) {
                $geobounds->setOwner($params['owner'], $params['fieldname'], $params['language']);
            }

            return $geobounds;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\Geobounds $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Data\Geobounds $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Geobounds) {
            return [
                'NElongitude' => $data->getNorthEast()->getLongitude(),
                'NElatitude' => $data->getNorthEast()->getLatitude(),
                'SWlongitude' => $data->getSouthWest()->getLongitude(),
                'SWlatitude' => $data->getSouthWest()->getLatitude()
            ];
        }

        return null;
    }

    /**
     * @param $data
     * @param null $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return DataObject\Data\Geobounds
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data['NElongitude'] !== null && $data['NElatitude'] !== null && $data['SWlongitude'] !== null && $data['SWlatitude'] !== null) {
            $ne = new DataObject\Data\Geopoint($data['NElongitude'], $data['NElatitude']);
            $sw = new DataObject\Data\Geopoint($data['SWlongitude'], $data['SWlatitude']);

            return new DataObject\Data\Geobounds($ne, $sw);
        }

        return;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\Geobounds $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Geobounds) {
            return $data->getNorthEast()->getLongitude() . ',' . $data->getNorthEast()->getLatitude() . ' ' . $data->getSouthWest()->getLongitude() . ',' . $data->getSouthWest()->getLatitude();
        }

        return '';
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Geobounds) {
            return  $data->getNorthEast()->getLongitude().','.$data->getNorthEast()->getLatitude().'|'.$data->getSouthWest()->getLongitude().','.$data->getSouthWest()->getLatitude();
        } else {
            return null;
        }
    }

    /**
     * @param string $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return null|DataObject\ClassDefinition\Data|DataObject\Data\Geobounds
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $points = explode('|', $importValue);
        $value = null;
        if (is_array($points) and count($points) == 2) {
            $northEast = explode(',', $points[0]);
            $southWest = explode(',', $points[1]);
            if ($northEast[0] && $northEast[1] && $southWest[0] && $southWest[1]) {
                $value = new DataObject\Data\Geobounds(new DataObject\Data\Geopoint($northEast[0], $northEast[1]), new DataObject\Data\Geopoint($southWest[0], $southWest[1]));
            }
        }

        return $value;
    }

    /**
     * @param $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Geobounds) {
            return [
                'NElongitude' => $data->getNorthEast()->getLongitude(),
                'NElatitude' => $data->getNorthEast()->getLatitude(),
                'SWlongitude' => $data->getSouthWest()->getLongitude(),
                'SWlatitude' => $data->getSouthWest()->getLatitude()
            ];
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } else {
            $value = (array) $value;
            if ($value['NElongitude'] !== null && $value['NElatitude'] !== null && $value['SWlongitude'] !== null && $value['SWlatitude'] !== null) {
                $ne = new DataObject\Data\Geopoint($value['NElongitude'], $value['NElatitude']);
                $sw = new DataObject\Data\Geopoint($value['SWlongitude'], $value['SWlatitude']);

                return new DataObject\Data\Geobounds($ne, $sw);
            } else {
                throw new \Exception('cannot get values from web service import - invalid data');
            }
        }
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value) {
            return [
                'value' => json_encode([$value[$this->getName() . '__NElatitude'], $value[$this->getName() . '__NElongitude']]),
                'value2' => json_encode([$value[$this->getName() . '__SWlatitude'], $value[$this->getName() . '__SWlongitude']])
            ];
        }
    }

    /** See marshal
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if ($value && $value['value'] && $value['value2']) {
            $dataNE = json_decode($value['value']);
            $dataSW = json_decode($value['value2']);

            $result = [];
            $result[$this->getName() . '__NElatitude'] = $dataNE[0];
            $result[$this->getName() . '__NElongitude'] = $dataNE[1];
            $result[$this->getName() . '__SWlatitude'] = $dataSW[0];
            $result[$this->getName() . '__SWlongitude'] = $dataSW[1];

            return $result;
        }
    }
}
