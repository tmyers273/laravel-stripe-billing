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

    public static function wrongType($card, $correctType = null)
    {
        $type = get_class($card);
        $correctType = is_string($correctType) ? "Expected type {$correctType}." : "";

        return new static("Type {$type} is not allowed. " . $correctType);

    }
}