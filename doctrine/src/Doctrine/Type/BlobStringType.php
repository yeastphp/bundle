<?php

namespace Yeast\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Types;


class BlobStringType extends BlobType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        if ( ! is_resource($value)) {
            throw ConversionException::conversionFailed($value, Types::BLOB);
        }

        $valueContents = stream_get_contents($value);

        if ($valueContents === false) {
            throw ConversionException::conversionFailed($value, Types::BLOB);
        }

        return $valueContents;
    }
}