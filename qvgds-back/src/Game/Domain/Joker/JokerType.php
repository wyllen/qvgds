<?php
declare(strict_types=1);

namespace QVGDS\Game\Domain\Joker;

enum JokerType: string
{
    case FIFTY_FIFTY = "FIFTY_FIFTY";
    case CALL_A_FRIEND = "CALL_A_FRIEND";
    case AUDIENCE_HELP = "AUDIENCE_HELP";
}
