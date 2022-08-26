<?php

namespace Sherlockode\AdvancedContentBundle\FieldType;

use Sherlockode\AdvancedContentBundle\Model\FieldInterface;
use Sherlockode\AdvancedContentBundle\Model\FieldValueInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Form;

abstract class AbstractFieldType implements FieldTypeInterface
{
    /**
     * @return string
     */
    public function getFormFieldLabel()
    {
        return 'field_type.' . $this->getCode() . '.label';
    }

    /**
     * Get field's options
     *
     * @param FieldInterface $field
     *
     * @return array
     */
    public function getFieldOptions(FieldInterface $field)
    {
        return $field->getOptions();
    }

    /**
     * Add field value's field(s) to content form
     *
     * @param FormBuilderInterface $builder
     *
     * @return void
     */
    public function buildContentFieldValue(FormBuilderInterface $builder)
    {
        $builder->add('fieldType', HiddenType::class);
        $builder->add('value', $this->getFormFieldType(), array_merge(
            $this->getDefaultFormFieldValueOptions(),
            $this->getFormFieldValueOptions()
        ));

        $modelTransformer = $this->getValueModelTransformer();
        if ($modelTransformer !== null) {
            $builder->get('value')
                ->addModelTransformer($modelTransformer);
        }
    }

    /**
     * Add field's options
     *
     * @param Form|FormBuilderInterface $builder
     *
     * @return void
     */
    public function addFieldOptions($builder)
    {
    }

    /**
     * Cleanup field options (in case of field type change)
     *
     * @param FieldInterface $field
     */
    public function clearOptions(FieldInterface $field)
    {
        $options = $field->getOptions();

        $optionNames = $this->getFieldOptionNames();
        foreach ($options as $key => $value) {
            if (in_array($key, $optionNames)) {
                continue;
            }
            unset($options[$key]);
        }

        $field->setOptions($options);
    }

    /**
     * Get Field option names
     *
     * @return array
     */
    public function getFieldOptionNames()
    {
        return [];
    }

    /**
     * Get options to apply on field value
     *
     * @return array
     */
    public function getFormFieldValueOptions()
    {
        return [];
    }

    /**
     * Render field value
     *
     * @param FieldValueInterface $fieldValue
     *
     * @return mixed
     */
    public function render(FieldValueInterface $fieldValue)
    {
        return $fieldValue->getValue();
    }

    /**
     * Get model transformer for value field
     *
     * @return null
     */
    public function getValueModelTransformer()
    {
        return null;
    }

    /**
     * Add field hint to field value form
     *
     * @return array
     */
    public function getDefaultFormFieldValueOptions()
    {
        $defaultOptions = ['label' => false];
        if ($this->getHint()) {
            $defaultOptions['attr']['help'] = $this->getHint();
        }
        return $defaultOptions;
    }

    public function getHint()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getFieldGroup()
    {
        return 'other';
    }

    /**
     * @param FieldValueInterface $fieldValue
     *
     * @return mixed
     */
    public function getRawValue(FieldValueInterface $fieldValue)
    {
        return $fieldValue->getValue();
    }

    /**
     * Get form field type
     *
     * @return string
     */
    abstract public function getFormFieldType();
}
