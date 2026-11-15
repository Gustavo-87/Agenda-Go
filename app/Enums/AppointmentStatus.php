<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';

    case Confirmed = 'confirmed';

    case Cancelled = 'cancelled';

    case Expired = 'expired';

    case Completed = 'completed';

    case Absent = 'absent';
}
