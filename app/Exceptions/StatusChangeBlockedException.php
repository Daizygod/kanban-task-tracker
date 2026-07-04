<?php

namespace App\Exceptions;

use App\Models\Task;
use Exception;
use Illuminate\Support\Collection;

class StatusChangeBlockedException extends Exception
{
    /**
     * @param  Collection<int, Task>  $blockers  незакрытые задачи, от которых зависит $task
     */
    public function __construct(
        public readonly Task $task,
        public readonly Collection $blockers,
    ) {
        $numbers = $blockers->map(fn (Task $t) => $t->full_number)->implode(', ');

        parent::__construct("Нельзя завершить: задача блокируется {$numbers}");
    }
}
