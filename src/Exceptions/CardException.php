<?php

namespace TMyers\StripeBilling\Exceptions;


class CardException extends \Exception
{
    public static function notOwnedBy($owner): self
    {
        $type = get_class($owner);
        $id = $owner->id ?? null;

        return new static("Card is not owned by the owner with ID {$id} and type {$type}");
    }

    public static function wrongType($card, string $correctType)
    {
        $type = get_class($card);

        return new static("Expected card to be of type {$correctType}, instead got {$type}");

    }
}