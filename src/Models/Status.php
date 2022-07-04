<?php

namespace Karpack\Statusable\Models;

use Illuminate\Database\Eloquent\Model;
use Karpack\Contracts\Translations\Translatable;
use Karpack\Translations\Traits\HasTranslations;

class Status extends Model implements Translatable
{
    use HasTranslations;
    
    /**
     * A status model contains two fields `statusable_type` and `identifier`
     * where `statusable_type` holds the class name of the model for which 
     * this status corresponds to and `identifier` is the unique identifier 
     * for the status. For example, an Order can have status `Placed`, so a
     * statuses table row contain `Order::class` as statusable_type and 
     * `Placed` as the unique identifier.
     * 
     * We can't define a relation on this model as it's sort of a morph relation
     * but the id field can be different for different parent.
     * 
     * But the parent relation can load them as hasMany relation.
     */

    /**
     * Returns the keys/properties that can have different translations.
     * 
     * @return array
     */
    public function translationKeys()
    {
        return ['name', 'description'];
    }
}