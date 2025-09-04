<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use Closure;
use MoonShine\Contracts\UI\Collection\TableCellsContract;
use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make()
 */
final class tableDoubleRow extends MoonShineComponent
{
    protected string $view = 'admin.components.table-double-row';

    public function __construct(
        protected TableCellsContract $cells,
        protected int|string|null $key = null
    ) {
        parent::__construct();
    }

    public function hasKey(): bool
    {
        return ! \is_null($this->key);
    }

    public function setKey(int|string|null $value): self
    {
        $this->key = $value;

        return $this;
    }

    public function getKey(): int|string|null
    {
        return $this->key;
    }

    public function getCells(): TableCellsContract
    {
        return $this->cells;
    }

    protected function prepareBeforeRender(): void
    {
        $this->customAttributes([
            'data-row-key' => $this->hasKey() ? $this->getKey() : null,
        ]);
    }

    protected function viewData(): array
    {
        return [
            'cells' => $this->getCells(),
        ];
    }
}
