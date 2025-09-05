<?php

namespace App\Helpers;

use Closure;
use MoonShine\Contracts\UI\Collection\TableRowsContract;
use MoonShine\Contracts\UI\TableRowContract;
use MoonShine\UI\Collections\TableRows;
use MoonShine\UI\Components\Table\TableRow;
use MoonShine\UI\Components\Table\TableTh;

/**
 * Разбивает строки tr на две строки в таблице ModelResource на индексной странице.
 *
 * Добавить в ModelResource данный трейт и статическое свойство $double_row_fields с массивом имен столбцов,
 * которые необходимо перенести на вторую строку:
 * ```
 * static protected array $double_row_fields = [
 *  'field_1',
 *  'field_2',
 *  'field_3',
 *  //...
 * ];
 * ```
 */
trait DoubleRowFields
{
    /**
     * Переопределение тела таблицы. Строкам TR назначить кастомное представление, в котором строка разбивается на две строки.
     * @return TableRowsContract|Closure|null
     */
    protected function tbody(): null|TableRowsContract|Closure
    {
        return static function (TableRowsContract $default) {
            if (property_exists(static::class, 'double_row_fields')) {
                foreach ($default as $item) {
                    $item->onBeforeRender(function (TableRow $field) {
                        return $field->customView('admin.components.table-double-row')->customAttributes(
                            ['data-double-row' => implode(',', static::$double_row_fields)]
                        );
                    });
                }
            }
            return $default;
        };
    }

    /**
     * Переопределение шапки таблицы. Скрытие наименований полей второй строки.
     * @return TableRowsContract|Closure|null
     */
    protected function thead(): null|TableRowsContract|Closure
    {
        return static function (TableRowContract $default) {
            if (property_exists(static::class, 'double_row_fields')) {
                foreach ($default->getCells() as $item) {
                    $item->onBeforeRender(function (TableTh $field) {
                        if (in_array($field->getAttribute('data-column-selection'), static::$double_row_fields)) {
                            $field->class('hidden');
                        }
                        return $field;
                    }
                    );
                }
            }
            return TableRows::make([$default]);
        };
    }
}
