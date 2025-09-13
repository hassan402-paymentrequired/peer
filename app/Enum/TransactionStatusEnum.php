<?php

namespace App\Enum;

enum TransactionStatusEnum: int
{
    case PENDING = 1;
    case SUCCESSFUL = 2;
    case FAILED = 3;
    case CANCELLED = 4;
}
