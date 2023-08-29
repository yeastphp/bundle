<?php

namespace Yeast\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Types;


class BinaryStringType extends BinaryType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        if ( ! is_resource($value)) {
            throw ConversionException::conversionFailed($value, Types::BINARY);
        }

        $valueContents = stream_get_contents($value);

        if ($valueContents === false) {
            throw ConversionException::conversionFailed($value, Types::BINARY);
        }

        return $valueContents;
    }
}