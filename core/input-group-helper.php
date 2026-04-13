<?php

declare(strict_types=1);

/**
 * Bootstrap Input Group Component Helper
 * 
 * Utilities for creating Bootstrap input groups with delete/remove icons
 */

namespace Catmin\UI;

class InputGroupHelper
{
    /**
     * Render a text input with delete icon
     * 
     * @param array $options = [
     *   'name' => string,
     *   'value' => string,
     *   'placeholder' => string,
     *   'icon' => 'trash|x|close',
     *   'removable' => bool,
     *   'required' => bool,
     *   'readonly' => bool,
     * ]
     */
    public static function textWithDelete(array $options): string
    {
        $name = htmlspecialchars($options['name'] ?? 'field', ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars($options['value'] ?? '', ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars($options['placeholder'] ?? '', ENT_QUOTES, 'UTF-8');
        $icon = $options['icon'] ?? 'trash';
        $removable = (bool) ($options['removable'] ?? true);
        $required = (bool) ($options['required'] ?? false);
        $readonly = (bool) ($options['readonly'] ?? false);

        $iconClass = $icon === 'x' ? 'icon-x' : 'icon-trash-2';
        
        $requiredAttr = $required ? 'required' : '';
        $readonlyAttr = $readonly ? 'readonly' : '';
        
        $buttonHtml = '';
        if ($removable) {
            $buttonHtml = '<button class="btn btn-outline-danger btn-sm" type="button" data-remove-input title="Remove"><i class="' . $iconClass . '"></i></button>';
        }

        return '<div class="input-group" role="group">' .
            '<input type="text" class="form-control" name="' . $name . '" value="' . $value . '" placeholder="' . $placeholder . '" ' . $requiredAttr . ' ' . $readonlyAttr . '>' .
            $buttonHtml .
            '</div>';
    }

    /**
     * Render a select with delete icon
     */
    public static function selectWithDelete(array $options): string
    {
        $name = htmlspecialchars($options['name'] ?? 'select', ENT_QUOTES, 'UTF-8');
        $items = (array) ($options['items'] ?? []);
        $selected = $options['selected'] ?? null;
        $icon = $options['icon'] ?? 'trash';
        $removable = (bool) ($options['removable'] ?? true);
        $required = (bool) ($options['required'] ?? false);

        $iconClass = $icon === 'x' ? 'icon-x' : 'icon-trash-2';
        $requiredAttr = $required ? 'required' : '';

        $optionsHtml = '';
        foreach ($items as $value => $label) {
            $selected_attr = $value === $selected ? 'selected' : '';
            $optionsHtml .= sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                $selected_attr,
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            );
        }

        $buttonHtml = '';
        if ($removable) {
            $buttonHtml = '<button class="btn btn-outline-danger btn-sm" type="button" data-remove-input title="Remove"><i class="' . $iconClass . '"></i></button>';
        }

        return '<div class="input-group" role="group">' .
            '<select class="form-select" name="' . $name . '" ' . $requiredAttr . '>' .
            $optionsHtml .
            '</select>' .
            $buttonHtml .
            '</div>';
    }

    /**
     * Render a date input with delete icon
     */
    public static function dateWithDelete(array $options): string
    {
        $name = htmlspecialchars($options['name'] ?? 'date', ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars($options['value'] ?? '', ENT_QUOTES, 'UTF-8');
        $icon = $options['icon'] ?? 'trash';
        $removable = (bool) ($options['removable'] ?? true);
        $required = (bool) ($options['required'] ?? false);

        $iconClass = $icon === 'x' ? 'icon-x' : 'icon-trash-2';
        $requiredAttr = $required ? 'required' : '';

        $buttonHtml = '';
        if ($removable) {
            $buttonHtml = '<button class="btn btn-outline-danger btn-sm" type="button" data-remove-input title="Remove"><i class="' . $iconClass . '"></i></button>';
        }

        return '<div class="input-group" role="group">' .
            '<input type="date" class="form-control" name="' . $name . '" value="' . $value . '" ' . $requiredAttr . '>' .
            $buttonHtml .
            '</div>';
    }

    /**
     * Render a checkbox with delete icon
     */
    public static function checkboxWithDelete(array $options): string
    {
        $name = htmlspecialchars($options['name'] ?? 'checkbox', ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars($options['value'] ?? '1', ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($options['label'] ?? '', ENT_QUOTES, 'UTF-8');
        $checked = (bool) ($options['checked'] ?? false);
        $icon = $options['icon'] ?? 'trash';
        $removable = (bool) ($options['removable'] ?? true);

        $iconClass = $icon === 'x' ? 'icon-x' : 'icon-trash-2';
        $checkedAttr = $checked ? 'checked' : '';

        $buttonHtml = '';
        if ($removable) {
            $buttonHtml = '<button class="btn btn-outline-danger btn-sm" type="button" data-remove-input title="Remove"><i class="' . $iconClass . '"></i></button>';
        }

        return '<div class="input-group" role="group">' .
            '<div class="input-group-text">' .
            '<input class="form-check-input" type="checkbox" name="' . $name . '" value="' . $value . '" ' . $checkedAttr . '>' .
            '<label class="form-check-label ms-2" style="margin-bottom: 0;">' . $label . '</label>' .
            '</div>' .
            $buttonHtml .
            '</div>';
    }
}
