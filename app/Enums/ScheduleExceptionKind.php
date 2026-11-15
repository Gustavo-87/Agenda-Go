<?php

namespace App\Enums;

enum ScheduleExceptionKind: string
{
    case Vacation = 'vacation';

    case Block = 'block';

    case Extra = 'extra';
}
