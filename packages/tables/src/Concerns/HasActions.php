<?php

namespace Filament\Tables\Concerns;

use Filament\Forms\ComponentContainer;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;

trait HasActions
{
    public $mountedTableAction = null;

    public $mountedTableActionData = [];

    public $mountedTableActionRecord = null;

    protected array $cachedTableActions;

    public function cacheTableActions(): void
    {
        $this->cachedTableActions = collect($this->getTableActions())
            ->filter(fn (Action $action): bool => ! $action->isHidden())
            ->mapWithKeys(function (Action $action): array {
                $action->table($this->getCachedTable());

                return [$action->getName() => $action];
            })
            ->toArray();
    }

    public function callMountedTableAction()
    {
        $action = $this->getMountedTableAction();

        if (! $action) {
            return;
        }

        $record = $this->resolveTableRecord($this->mountedTableActionRecord);

        $data = $this->getMountedTableActionForm()->getState();

        try {
            return $action->record($record)->call($data);
        } finally {
            $this->dispatchBrowserEvent('close-modal', [
                'id' => 'action',
            ]);
        }
    }

    public function mountTableAction(string $name, ?string $record = null)
    {
        $this->mountedTableAction = $name;

        $action = $this->getMountedTableAction();

        if (! $action) {
            return;
        }

        $this->mountedTableActionRecord = $record;

        if (! $action->shouldOpenModal()) {
            return $this->callMountedTableAction();
        }

        $this->getMountedTableActionForm()->fill();

        $this->dispatchBrowserEvent('open-modal', [
            'id' => 'action',
        ]);
    }

    public function getCachedTableActions(): array
    {
        return $this->cachedTableActions;
    }

    public function getMountedTableAction(): ?Action
    {
        if (! $this->mountedTableAction) {
            return null;
        }

        return $this->getCachedTableAction($this->mountedTableAction);
    }

    public function getMountedTableActionForm(): ComponentContainer
    {
        return $this->mountedTableActionForm
            ->schema($this->getMountedTableAction()->getFormSchema())
            ->model($this->getTableQuery()->find($this->mountedTableActionRecord));
    }

    protected function getCachedTableAction(string $name): ?Action
    {
        return $this->getCachedTableActions()[$name] ?? null;
    }

    protected function getTableActions(): array
    {
        return [];
    }
}
