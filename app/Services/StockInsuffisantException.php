<?php

namespace App\Services;

/** Levée lorsqu'un article n'est plus disponible en quantité suffisante. */
class StockInsuffisantException extends \RuntimeException
{
}
