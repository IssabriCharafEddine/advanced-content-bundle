<?php

namespace Sherlockode\AdvancedContentBundle\Export;

use Sherlockode\AdvancedContentBundle\FieldType\AbstractEntity;
use Sherlockode\AdvancedContentBundle\FieldType\Boolean;
use Sherlockode\AdvancedContentBundle\FieldType\File;
use Sherlockode\AdvancedContentBundle\Manager\FieldManager;
use Sherlockode\AdvancedContentBundle\Model\ContentInterface;
use Sherlockode\AdvancedContentBundle\Model\FieldGroupValueInterface;
use Sherlockode\AdvancedContentBundle\Model\FieldValueInterface;

class ContentExport
{
    /**
     * @var FieldManager
     */
    private $fieldManager;

    /**
     * @param FieldManager $fieldManager
     */
    public function __construct(FieldManager $fieldManager)
    {
        $this->fieldManager = $fieldManager;
    }

    /**
     * @param ContentInterface $content
     *
     * @return array
     */
    public function exportData(ContentInterface $content)
    {
        $data = [];
        $data['name'] = $content->getName();
        $data['contentType'] = $content->getContentType()->getSlug();

        $fieldValues = $content->getFieldValues();
        $data = array_merge($data, $this->exportFieldValues($fieldValues));

        $data = [
            'contents' => [
                $content->getSlug() => $data,
            ],
        ];

        return $data;
    }

    /**
     * @param array|FieldValueInterface[] $fieldValues
     *
     * @return array
     */
    public function exportFieldValues($fieldValues)
    {
        if (count($fieldValues) === 0) {
            return [];
        }

        $data = ['children' => []];
        foreach ($fieldValues as $fieldValue) {
            $data['children'][] = $this->exportFieldValue($fieldValue);
        }

        return $data;
    }

    /**
     * @param FieldValueInterface $fieldValue
     *
     * @return array
     */
    private function exportFieldValue(FieldValueInterface $fieldValue)
    {
        $fieldValueData = [];
        $fieldValueData['slug'] = $fieldValue->getField()->getSlug();

        $children = $fieldValue->getChildren();
        if (count($children) > 0) {
            $fieldValueData['children'] = [];
            foreach ($children as $child) {
                /** @var FieldGroupValueInterface $child */
                $childData = [];
                $childData['name'] = $child->getLayout()->getName();
                $childData = array_merge($childData, $this->exportFieldValues($child->getChildren()));
                $fieldValueData['children'][] = $childData;
            }
        } else {
            $fieldType = $this->fieldManager->getFieldType($fieldValue->getField());
            $rawValue = $fieldType->getRawValue($fieldValue);

            if ($fieldType instanceof File) {
                if (is_array($rawValue) && isset($rawValue['url'])) {
                    unset($rawValue['url']);
                }
            } elseif ($fieldType instanceof Boolean) {
                $rawValue = (int) $rawValue;
            } elseif ($fieldType instanceof AbstractEntity) {
                if ($fieldType->getIsMultipleChoice($fieldValue->getField())) {
                    $rawValues = [];
                    foreach ($rawValue as $value) {
                        $rawValues[] = $value['value'];
                    }
                    $rawValue = $rawValues;
                } else {
                    $rawValue = $rawValue['value'];
                }
            }

            $fieldValueData['value'] = $rawValue;
        }

        return $fieldValueData;
    }
}