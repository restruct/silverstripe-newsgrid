<?php

namespace Restruct\SilverStripe\NewsGrid\Tests\NewsGridTemplateGuardTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Stands in for filterablearchive's ItemExtension: gives a news item the getDateField() method the
 * module's templates guard their FilterableProperties includes on.
 */
class DateFieldExtension extends Extension implements TestOnly
{
    # Same return as filterablearchive's ItemExtension::getDateField() with managed_object_date_field: Date
    public function getDateField()
    {
        return $this->getOwner()->dbObject('Date');
    }
}
