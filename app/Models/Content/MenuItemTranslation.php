<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $menu_item_id
 * @property string $locale
 * @property string $label
 */
class MenuItemTranslation extends Model
{
    public $timestamps = true;

    protected $fillable = ['label'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
